<?php
// /api/import_espo.php — reprise des données EspoCRM dans le CRM Webina (admin uniquement)
//   GET  → vérifie la connexion à la base EspoCRM et compte ce qui sera importé
//   POST → importe (peut être relancé sans créer de doublons : chaque fiche garde son identifiant Espo)
// config.php : 'espo_db_name' => 'webe5acomtr_webina'
//   (+ 'espo_db_user' / 'espo_db_pass' si l'utilisateur du site n'a pas accès à cette base)
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require_admin();

function espo_pdo(): PDO {
    global $CONFIG;
    $name = $CONFIG['espo_db_name'] ?? '';
    if ($name === '') fail("Ajoutez 'espo_db_name' dans api/config.php (nom de la base EspoCRM).", 422);
    try {
        return new PDO("mysql:host=" . ($CONFIG['espo_db_host'] ?? $CONFIG['db_host']) . ";dbname=$name;charset=utf8mb4",
            $CONFIG['espo_db_user'] ?? $CONFIG['db_user'], $CONFIG['espo_db_pass'] ?? $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Throwable $e) {
        error_log('[webina] ESPO DB: ' . $e->getMessage());
        fail("Connexion à la base EspoCRM impossible : vérifiez que l'utilisateur a accès à « $name ».", 500);
    }
}

$espo = espo_pdo();
$crm = crm_db();
$tables = array_flip($espo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
foreach (['account', 'contact', 'lead', 'opportunity', 'task'] as $t) {
    if (!isset($tables[$t])) fail("La base indiquée ne ressemble pas à EspoCRM (table « $t » absente).", 422);
}
$count = fn(string $t, string $w = 'deleted = 0') => isset($tables[$t]) ? (int)$espo->query("SELECT COUNT(*) FROM `$t` WHERE $w")->fetchColumn() : 0;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['ok' => true, 'counts' => [
        'accounts' => $count('account'), 'contacts' => $count('contact'),
        'leads' => $count('lead', "deleted = 0 AND (status IS NULL OR status <> 'Converted')"),
        'opportunities' => $count('opportunity'), 'tasks' => $count('task'),
        'meetings' => $count('meeting'), 'calls' => $count('call'),
        'notes' => $count('note', "deleted = 0 AND type = 'Post'"),
        'users' => $count('user', "deleted = 0 AND type IN ('admin', 'regular')"),
    ]]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Méthode non autorisée.', 405);

@set_time_limit(300);

// E-mail et téléphone principaux de chaque fiche (tables de liaison Espo)
function espo_contacts(PDO $espo, string $type): array {
    $out = [];
    foreach ($espo->query("SELECT eea.entity_id, ea.name FROM entity_email_address eea JOIN email_address ea ON ea.id = eea.email_address_id
        WHERE eea.entity_type = '$type' AND eea.deleted = 0 AND ea.deleted = 0 ORDER BY eea.primary DESC") as $r) {
        $out[$r['entity_id']]['email'] = $out[$r['entity_id']]['email'] ?? $r['name'];
    }
    foreach ($espo->query("SELECT epn.entity_id, pn.name FROM entity_phone_number epn JOIN phone_number pn ON pn.id = epn.phone_number_id
        WHERE epn.entity_type = '$type' AND epn.deleted = 0 AND pn.deleted = 0 ORDER BY epn.primary DESC") as $r) {
        $out[$r['entity_id']]['phone'] = $out[$r['entity_id']]['phone'] ?? $r['name'];
    }
    return $out;
}
function full_name(array $r): string {
    return trim(preg_replace('/\s+/', ' ', ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?: '(sans nom)';
}
function dt(?string $v): ?string { return $v ? date('Y-m-d H:i:s', strtotime($v)) : null; }

$ins = $crm->prepare('INSERT IGNORE INTO crm_clients (id, kind, parent_id, name, company, email, phone, sector, city, website, source, status, notes, espo_id, created_at, updated_at, last_activity_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()), COALESCE(?, NOW()), ?)');
$map = []; // identifiant Espo (Type:id) → identifiant CRM
$existing = $crm->query("SELECT espo_id, id FROM crm_clients WHERE espo_id IS NOT NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
$done = ['clients' => 0, 'deals' => 0, 'tasks' => 0, 'notes' => 0];
$add = function (string $key, array $row) use ($ins, &$map, $existing, &$done) {
    if (isset($existing[$key])) { $map[$key] = $existing[$key]; return; }
    $id = uuid4();
    $ins->execute([$id, $row['kind'], $row['parent'] ?? null, mb_substr($row['name'], 0, 160), $row['company'] ?? null, $row['email'] ?? null, $row['phone'] ?? null,
        $row['sector'] ?? null, $row['city'] ?? null, $row['website'] ?? null, $row['source'] ?? 'EspoCRM', $row['status'], $row['notes'] ?? null, $key,
        dt($row['created'] ?? null), dt($row['modified'] ?? null), dt($row['modified'] ?? null)]);
    $map[$key] = $id;
    $done['clients']++;
};

$crm->beginTransaction();
try {
    // Sociétés (Account)
    $won = array_flip($espo->query("SELECT DISTINCT account_id FROM opportunity WHERE deleted = 0 AND stage = 'Closed Won' AND account_id IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN));
    $info = espo_contacts($espo, 'Account');
    foreach ($espo->query('SELECT * FROM account WHERE deleted = 0') as $a) {
        $add('Account:' . $a['id'], ['kind' => 'company', 'name' => $a['name'] ?: '(sans nom)', 'email' => $info[$a['id']]['email'] ?? null, 'phone' => $info[$a['id']]['phone'] ?? null,
            'sector' => $a['industry'], 'city' => $a['billing_address_city'], 'website' => $a['website'], 'notes' => $a['description'],
            'status' => (isset($won[$a['id']]) || $a['type'] === 'Customer') ? 'client' : 'lead', 'created' => $a['created_at'], 'modified' => $a['modified_at']]);
    }
    // Contacts, rattachés à leur société
    $info = espo_contacts($espo, 'Contact');
    $accName = $espo->query('SELECT id, name FROM account WHERE deleted = 0')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($espo->query('SELECT * FROM contact WHERE deleted = 0') as $c) {
        $acc = $c['account_id'];
        $add('Contact:' . $c['id'], ['kind' => 'person', 'parent' => $acc ? ($map['Account:' . $acc] ?? null) : null, 'name' => full_name($c),
            'company' => $acc ? ($accName[$acc] ?? null) : null, 'email' => $info[$c['id']]['email'] ?? null, 'phone' => $info[$c['id']]['phone'] ?? null,
            'city' => $c['address_city'], 'notes' => $c['description'], 'status' => 'client', 'created' => $c['created_at'], 'modified' => $c['modified_at']]);
    }
    // Prospects non convertis
    $info = espo_contacts($espo, 'Lead');
    $leadDeals = [];
    foreach ($espo->query("SELECT * FROM lead WHERE deleted = 0 AND (status IS NULL OR status <> 'Converted')") as $l) {
        $add('Lead:' . $l['id'], ['kind' => 'person', 'name' => full_name($l), 'company' => $l['account_name'], 'email' => $info[$l['id']]['email'] ?? null,
            'phone' => $info[$l['id']]['phone'] ?? null, 'sector' => $l['industry'], 'city' => $l['address_city'], 'website' => $l['website'],
            'source' => $l['source'] ? 'EspoCRM · ' . $l['source'] : 'EspoCRM', 'notes' => $l['description'],
            'status' => $l['status'] === 'Dead' ? 'archived' : 'lead', 'created' => $l['created_at'], 'modified' => $l['modified_at']]);
        if ((float)$l['opportunity_amount'] > 0) $leadDeals[] = $l;
    }

    // Ventes (Opportunity)
    $stage = function (?string $s): string {
        if ($s === 'Closed Won') return 'won';
        if ($s === 'Closed Lost') return 'lost';
        if ($s === 'Prospecting' || !$s) return 'new';
        if ($s === 'Qualification') return 'contacted';
        return 'proposal';
    };
    $dIns = $crm->prepare('INSERT IGNORE INTO crm_deals (id, client_id, title, amount, currency, stage, close_date, notes, espo_id, created_at, updated_at, stage_changed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $dealMap = $crm->query("SELECT espo_id, id FROM crm_deals WHERE espo_id IS NOT NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($espo->query('SELECT * FROM opportunity WHERE deleted = 0') as $o) {
        $key = 'Opportunity:' . $o['id'];
        $client = ($o['account_id'] ? ($map['Account:' . $o['account_id']] ?? null) : null) ?? ($o['contact_id'] ? ($map['Contact:' . $o['contact_id']] ?? null) : null);
        if (!$client) {
            // Vente sans société : fiche créée à son nom
            $add('OppClient:' . $o['id'], ['kind' => 'company', 'name' => $o['name'] ?: '(sans nom)', 'status' => 'lead', 'created' => $o['created_at']]);
            $client = $map['OppClient:' . $o['id']];
        }
        if (isset($dealMap[$key])) continue;
        $id = uuid4();
        $dIns->execute([$id, $client, mb_substr($o['name'] ?: 'Vente', 0, 200), (float)$o['amount'], in_array($o['amount_currency'], ['TRY', 'USD', 'EUR'], true) ? $o['amount_currency'] : 'TRY',
            $stage($o['stage']), $o['close_date'], $o['description'], $key, dt($o['created_at']) ?? date('Y-m-d H:i:s'), dt($o['modified_at']) ?? date('Y-m-d H:i:s'), dt($o['modified_at']) ?? date('Y-m-d H:i:s')]);
        $dealMap[$key] = $id;
        $done['deals']++;
        if ($stage($o['stage']) === 'won') {
            $crm->prepare("UPDATE crm_clients SET status = 'client' WHERE id = ?")->execute([$client]);
            // Les paiements ne sont pas dans EspoCRM : le projet est marqué « à vérifier » et n'entre pas dans les relances de solde
            $crm->prepare("INSERT IGNORE INTO crm_projects (id, client_id, deal_id, name, status, start_date, delivery_date, amount, currency, notes) VALUES (?, ?, ?, ?, 'delivered', ?, ?, ?, ?, 'Repris d''EspoCRM : paiements à vérifier')")
                ->execute([uuid4(), $client, $id, mb_substr($o['name'] ?: 'Projet', 0, 200), $o['close_date'], $o['close_date'], (float)$o['amount'], in_array($o['amount_currency'], ['TRY', 'USD', 'EUR'], true) ? $o['amount_currency'] : 'TRY']);
        }
    }
    foreach ($leadDeals as $l) {
        $key = 'LeadDeal:' . $l['id'];
        if (isset($dealMap[$key]) || !isset($map['Lead:' . $l['id']])) continue;
        $dIns->execute([uuid4(), $map['Lead:' . $l['id']], 'Opportunité (prospect EspoCRM)', (float)$l['opportunity_amount'],
            in_array($l['opportunity_amount_currency'], ['TRY', 'USD', 'EUR'], true) ? $l['opportunity_amount_currency'] : 'TRY', 'new', null, null, $key,
            dt($l['created_at']) ?? date('Y-m-d H:i:s'), dt($l['modified_at']) ?? date('Y-m-d H:i:s'), dt($l['modified_at']) ?? date('Y-m-d H:i:s')]);
        $done['deals']++;
    }

    // Tâches, réunions et appels
    $parent = function (?string $type, ?string $pid, ?string $accountId = null) use ($map, $dealMap): array {
        $client = null; $deal = null;
        if ($type === 'Opportunity' && $pid) { $deal = $dealMap['Opportunity:' . $pid] ?? null; }
        if ($type && $pid && isset($map[$type . ':' . $pid])) $client = $map[$type . ':' . $pid];
        if (!$client && $accountId) $client = $map['Account:' . $accountId] ?? null;
        return [$client, $deal];
    };
    $tIns = $crm->prepare('INSERT IGNORE INTO crm_tasks (id, client_id, deal_id, title, kind, due_at, done_at, notes, espo_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()))');
    $sources = [
        ['task', 'todo', fn($r) => $r['date_end'] ?? ($r['date_end_date'] ? $r['date_end_date'] . ' 10:00:00' : null),
            fn($r) => in_array($r['status'], ['Completed', 'Canceled'], true) ? ($r['date_completed'] ?? $r['modified_at']) : null],
        ['meeting', 'meeting', fn($r) => $r['date_start'], fn($r) => in_array($r['status'], ['Held', 'Not Held'], true) ? $r['modified_at'] : null],
        ['call', 'call', fn($r) => $r['date_start'], fn($r) => in_array($r['status'], ['Held', 'Not Held'], true) ? $r['modified_at'] : null],
    ];
    foreach ($sources as [$table, $kind, $due, $doneAt]) {
        if (!isset($tables[$table])) continue;
        foreach ($espo->query("SELECT * FROM `$table` WHERE deleted = 0") as $t) {
            [$client, $deal] = $parent($t['parent_type'], $t['parent_id'], $t['account_id'] ?? null);
            $tIns->execute([uuid4(), $client, $deal, mb_substr($t['name'] ?: 'Tâche', 0, 200), $kind, dt($due($t)), dt($doneAt($t)), $t['description'], ucfirst($table) . ':' . $t['id'], dt($t['created_at'])]);
            $done['tasks'] += $tIns->rowCount();
        }
    }

    // Messages du fil d'activité (posts)
    if (isset($tables['note'])) {
        $nIns = $crm->prepare("INSERT IGNORE INTO crm_activity (id, client_id, deal_id, kind, text, espo_id, created_at) VALUES (?, ?, ?, 'note', ?, ?, COALESCE(?, NOW()))");
        foreach ($espo->query("SELECT * FROM note WHERE deleted = 0 AND type = 'Post' AND post IS NOT NULL AND post <> ''") as $n) {
            [$client, $deal] = $parent($n['parent_type'], $n['parent_id']);
            if (!$client) continue;
            $nIns->execute([uuid4(), $client, $deal, $n['post'], 'Note:' . $n['id'], dt($n['created_at'])]);
            $done['notes'] += $nIns->rowCount();
        }
    }
    $crm->commit();
} catch (Throwable $e) {
    $crm->rollBack();
    error_log('[webina] ESPO IMPORT: ' . $e->getMessage());
    fail('Import interrompu : ' . $e->getMessage(), 500);
}

json_out(['ok' => true, 'imported' => $done]);
