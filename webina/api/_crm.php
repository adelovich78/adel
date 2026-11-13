<?php
// Mini-CRM Webina : schéma, outils communs et automatisations
// (remplace EspoCRM : clients, ventes, tâches, projets, paiements, historique)

const CRM_STAGES = ['new', 'contacted', 'proposal', 'won', 'lost'];
const CRM_PROJECT_STATUS = ['in_progress', 'delivered', 'paused', 'cancelled'];
const CRM_TASK_KINDS = ['todo', 'call', 'meeting', 'email'];
const CRM_EXPENSE_CATEGORIES = ['ads', 'domain', 'hosting', 'software', 'freelance', 'salary', 'office', 'equipment', 'travel', 'bank', 'tax', 'other'];
const CRM_AD_PLATFORMS = ['instagram', 'facebook', 'youtube', 'google', 'tiktok', 'linkedin'];
const CRM_RECURRENCES = ['none', 'monthly', 'quarterly', 'yearly'];

// Délais des relances automatiques (jours)
const CRM_FOLLOWUP_DAYS = 5;      // vente sans nouvelle depuis X jours → tâche « Relancer »
const CRM_RENEWAL_NOTICE = 30;    // renouvellement d'hébergement dans X jours → tâche

function crm_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_clients (
        id CHAR(36) NOT NULL PRIMARY KEY,
        kind VARCHAR(10) NOT NULL DEFAULT 'person',
        parent_id CHAR(36) NULL,
        name VARCHAR(160) NOT NULL,
        company VARCHAR(160) NULL,
        email VARCHAR(255) NULL,
        phone VARCHAR(60) NULL,
        sector VARCHAR(80) NULL,
        city VARCHAR(80) NULL,
        website VARCHAR(255) NULL,
        source VARCHAR(40) NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'lead',
        notes TEXT NULL,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_activity_at DATETIME NULL,
        INDEX idx_email (email), INDEX idx_status (status), INDEX idx_parent (parent_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_deals (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        title VARCHAR(200) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        stage VARCHAR(12) NOT NULL DEFAULT 'new',
        submission_id CHAR(36) NULL,
        close_date DATE NULL,
        notes TEXT NULL,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        stage_changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id), INDEX idx_stage (stage)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_tasks (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NULL,
        deal_id CHAR(36) NULL,
        project_id CHAR(36) NULL,
        title VARCHAR(200) NOT NULL,
        kind VARCHAR(10) NOT NULL DEFAULT 'todo',
        due_at DATETIME NULL,
        done_at DATETIME NULL,
        notes TEXT NULL,
        auto_key VARCHAR(120) NULL UNIQUE,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_due (done_at, due_at), INDEX idx_client (client_id), INDEX idx_deal (deal_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_projects (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        deal_id CHAR(36) NULL UNIQUE,
        name VARCHAR(200) NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'in_progress',
        start_date DATE NULL,
        delivery_date DATE NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        hosting_renewal DATE NULL,
        hosting_amount DECIMAL(12,2) NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id), INDEX idx_renewal (hosting_renewal)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_payments (
        id CHAR(36) NOT NULL PRIMARY KEY,
        project_id CHAR(36) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        paid_at DATE NOT NULL,
        method VARCHAR(40) NULL,
        note VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_project (project_id)
    ) $o");
    // Dépenses : une ligne par dépense. « recurrence » + « next_due » = modèle d'abonnement
    // qui recrée la dépense à chaque échéance (cron quotidien).
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_expenses (
        id CHAR(36) NOT NULL PRIMARY KEY,
        category VARCHAR(20) NOT NULL,
        platform VARCHAR(12) NULL,
        label VARCHAR(160) NOT NULL,
        supplier VARCHAR(120) NULL,
        client_id CHAR(36) NULL,
        spent_on DATE NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        impressions INT NULL,
        clicks INT NULL,
        leads INT NULL,
        recurrence VARCHAR(10) NOT NULL DEFAULT 'none',
        next_due DATE NULL,
        parent_id CHAR(36) NULL,
        note VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (spent_on), INDEX idx_cat (category), INDEX idx_due (recurrence, next_due),
        UNIQUE KEY uq_occurrence (parent_id, spent_on)
    ) $o");
    // ---------- Espace client (my.webina.com.tr) ----------
    // Un projet = un service suivi par le client : domaine, hébergement, abonnement, statut du site et SSL
    $pcols = array_column($pdo->query('SHOW COLUMNS FROM crm_projects')->fetchAll(), 'Field');
    if (!in_array('domain', $pcols, true)) {
        $pdo->exec("ALTER TABLE crm_projects ADD COLUMN domain VARCHAR(190) NULL, ADD COLUMN plan VARCHAR(20) NULL,
            ADD COLUMN site_status VARCHAR(12) NOT NULL DEFAULT 'building', ADD COLUMN preview_url VARCHAR(255) NULL,
            ADD COLUMN domain_expires DATE NULL, ADD COLUMN ssl_status VARCHAR(10) NOT NULL DEFAULT 'pending', ADD COLUMN ssl_expires DATE NULL");
    }
    $ccols = array_column($pdo->query('SHOW COLUMNS FROM crm_clients')->fetchAll(), 'Field');
    if (!in_array('portal_enabled', $ccols, true)) {
        $pdo->exec("ALTER TABLE crm_clients ADD COLUMN portal_enabled TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN portal_last_login DATETIME NULL, ADD COLUMN lang VARCHAR(5) NULL");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_invoices (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        project_id CHAR(36) NULL,
        number VARCHAR(20) NOT NULL UNIQUE,
        label VARCHAR(200) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        issued_on DATE NOT NULL,
        due_on DATE NOT NULL,
        paid_at DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id), INDEX idx_due (paid_at, due_on)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_notices (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NULL,
        level VARCHAR(10) NOT NULL DEFAULT 'info',
        title VARCHAR(160) NOT NULL,
        body TEXT NULL,
        expires_on DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_requests (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        project_id CHAR(36) NULL,
        kind VARCHAR(12) NOT NULL,
        detail TEXT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        done_at DATETIME NULL,
        INDEX idx_status (status, created_at), INDEX idx_client (client_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_tokens (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        kind VARCHAR(6) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        attempts TINYINT NOT NULL DEFAULT 0,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_hash (token_hash), INDEX idx_client (client_id, kind)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_activity (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NULL,
        deal_id CHAR(36) NULL,
        kind VARCHAR(20) NOT NULL DEFAULT 'note',
        text TEXT NOT NULL,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id, created_at)
    ) $o");
}

function crm_db(): PDO {
    $pdo = db();
    crm_schema($pdo);
    return $pdo;
}

// Historique : chaque événement met aussi à jour la « dernière activité » du client
function crm_log(PDO $pdo, ?string $clientId, ?string $dealId, string $kind, string $text, ?string $at = null): void {
    $pdo->prepare('INSERT INTO crm_activity (id, client_id, deal_id, kind, text, created_at) VALUES (?, ?, ?, ?, ?, COALESCE(?, NOW()))')
        ->execute([uuid4(), $clientId, $dealId, $kind, mb_substr($text, 0, 5000), $at]);
    if ($clientId) $pdo->prepare('UPDATE crm_clients SET last_activity_at = NOW() WHERE id = ?')->execute([$clientId]);
}

// Tâche automatique : la clé évite de créer deux fois la même relance
function crm_auto_task(PDO $pdo, string $key, string $title, string $due, ?string $clientId, ?string $dealId = null, ?string $projectId = null, string $kind = 'todo'): bool {
    $st = $pdo->prepare('INSERT IGNORE INTO crm_tasks (id, client_id, deal_id, project_id, title, kind, due_at, auto_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $st->execute([uuid4(), $clientId, $dealId, $projectId, mb_substr($title, 0, 200), $kind, $due, $key]);
    return $st->rowCount() === 1;
}

// Prochain jour ouvré à 10 h (la demande arrive le vendredi soir → lundi)
function crm_next_workday(int $days = 1): string {
    $t = strtotime("+$days day");
    while ((int)date('N', $t) >= 6) $t = strtotime('+1 day', $t);
    return date('Y-m-d 10:00:00', $t);
}

/* ---------- Automatisation 1 : chaque demande du site entre dans le CRM ---------- */
function crm_ingest_submission(PDO $pdo, array $s): void {
    try {
        crm_schema($pdo);
        $email = strtolower(trim((string)($s['email'] ?? '')));
        $st = $pdo->prepare("SELECT id, status FROM crm_clients WHERE LOWER(email) = ? AND status <> 'archived' ORDER BY created_at LIMIT 1");
        $st->execute([$email]);
        $client = $st->fetch();
        $isQuote = ($s['kind'] ?? '') === 'quote';
        if (!$client) {
            $cid = uuid4();
            $pdo->prepare("INSERT INTO crm_clients (id, kind, name, company, email, phone, sector, city, source, status) VALUES (?, 'person', ?, ?, ?, ?, ?, ?, ?, 'lead')")
                ->execute([$cid, $s['name'], $s['company'] ?: null, $email, $s['phone'] ?: null, $s['sector'] ?: null, $s['city'] ?: null, $isQuote ? 'Devis en ligne' : 'Formulaire de contact']);
        } else {
            $cid = $client['id'];
            // Complète la fiche sans écraser ce qui existe déjà
            $pdo->prepare('UPDATE crm_clients SET phone = COALESCE(phone, ?), company = COALESCE(company, ?), sector = COALESCE(sector, ?), city = COALESCE(city, ?), updated_at = NOW() WHERE id = ?')
                ->execute([$s['phone'] ?: null, $s['company'] ?: null, $s['sector'] ?: null, $s['city'] ?: null, $cid]);
        }
        $title = $isQuote ? 'Site web — devis en ligne' : ('Demande : ' . ($s['service'] ?: 'contact'));
        $did = uuid4();
        $pdo->prepare("INSERT INTO crm_deals (id, client_id, title, amount, currency, stage, submission_id, notes) VALUES (?, ?, ?, ?, ?, 'new', ?, ?)")
            ->execute([$did, $cid, $title, (float)($s['amount'] ?? 0), $s['currency'] ?? 'TRY', $s['submission_id'] ?? null, $s['budget'] ? 'Budget indiqué : ' . $s['budget'] : null]);
        $msg = $isQuote ? 'Devis en ligne reçu' . (!empty($s['amount']) ? ' : ' . money((int)$s['amount'], $s['currency'] ?? 'TRY') : '') . (!empty($s['promo']) ? ' (code ' . $s['promo'] . ')' : '')
                        : 'Message reçu via le formulaire de contact';
        crm_log($pdo, $cid, $did, 'submission', $msg . (trim((string)($s['message'] ?? '')) !== '' ? "\n\n" . $s['message'] : ''));
        crm_auto_task($pdo, 'first-contact:' . $did, 'Rappeler ' . $s['name'], crm_next_workday(), $cid, $did, null, 'call');
    } catch (Throwable $e) {
        error_log('[webina] CRM ingest: ' . $e->getMessage());
    }
}

/* ---------- Automatisation 2 : changement d'étape d'une vente ---------- */
function crm_on_stage_change(PDO $pdo, array $deal, string $from, string $to): void {
    $labels = ['new' => 'Nouveau', 'contacted' => 'Contacté', 'proposal' => 'Proposition', 'won' => 'Gagné', 'lost' => 'Perdu'];
    crm_log($pdo, $deal['client_id'], $deal['id'], 'stage', 'Étape : ' . ($labels[$from] ?? $from) . ' → ' . ($labels[$to] ?? $to));
    if ($to === 'won') {
        $pdo->prepare("UPDATE crm_clients SET status = 'client', updated_at = NOW() WHERE id = ?")->execute([$deal['client_id']]);
        // Projet créé automatiquement à partir de la vente gagnée
        $pid = uuid4();
        $st = $pdo->prepare("INSERT IGNORE INTO crm_projects (id, client_id, deal_id, name, status, start_date, amount, currency, hosting_renewal, hosting_amount)
            VALUES (?, ?, ?, ?, 'in_progress', CURDATE(), ?, ?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)");
        $st->execute([$pid, $deal['client_id'], $deal['id'], $deal['title'], $deal['amount'], $deal['currency'], crm_renewal_price($deal['currency'])]);
        if ($st->rowCount()) {
            crm_log($pdo, $deal['client_id'], $deal['id'], 'project', 'Projet créé : ' . $deal['title']);
            crm_auto_task($pdo, 'kickoff:' . $deal['id'], 'Lancer le projet « ' . $deal['title'] . ' »', crm_next_workday(), $deal['client_id'], $deal['id'], $pid);
        }
    }
    if (in_array($to, ['won', 'lost'], true)) {
        // Les relances automatiques ouvertes n'ont plus lieu d'être
        $pdo->prepare("UPDATE crm_tasks SET done_at = NOW() WHERE deal_id = ? AND done_at IS NULL AND (auto_key LIKE 'followup:%' OR auto_key LIKE 'first-contact:%')")->execute([$deal['id']]);
    }
}

// Prix du renouvellement d'hébergement depuis le barème du site
function crm_renewal_price(string $currency): ?float {
    $file = dirname(__DIR__) . '/pricing.json';
    $p = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    if (!is_array($p) || empty($p['prices']['renewalYearly'])) return null;
    $per = (float)($p['tlPer'][$currency] ?? 1);
    $step = (float)($p['rounding'][$currency] ?? 1);
    return round(((float)$p['prices']['renewalYearly'] / $per) / $step) * $step;
}

/* ---------- Automatisation 3 : passage quotidien (cron) ---------- */
function crm_daily(PDO $pdo): array {
    crm_schema($pdo);
    $created = ['followup' => 0, 'renewal' => 0, 'balance' => 0];

    // Ventes en cours sans nouvelle depuis X jours → « Relancer »
    $st = $pdo->prepare("SELECT d.id, d.title, d.client_id, c.name FROM crm_deals d JOIN crm_clients c ON c.id = d.client_id
        WHERE d.stage IN ('new', 'contacted', 'proposal')
          AND GREATEST(d.stage_changed_at, COALESCE(c.last_activity_at, d.created_at)) < (NOW() - INTERVAL " . CRM_FOLLOWUP_DAYS . " DAY)
          AND NOT EXISTS (SELECT 1 FROM crm_tasks t WHERE t.deal_id = d.id AND t.done_at IS NULL)");
    $st->execute();
    foreach ($st->fetchAll() as $d) {
        if (crm_auto_task($pdo, 'followup:' . $d['id'] . ':' . date('Y-m-d'), 'Relancer ' . $d['name'] . ' — ' . $d['title'], date('Y-m-d 10:00:00'), $d['client_id'], $d['id'], null, 'call')) {
            $created['followup']++;
            crm_log($pdo, $d['client_id'], $d['id'], 'auto', 'Relance automatique programmée (sans nouvelle depuis ' . CRM_FOLLOWUP_DAYS . ' jours)');
        }
    }

    // Renouvellements d'hébergement à venir
    $st = $pdo->prepare("SELECT p.id, p.name, p.client_id, p.hosting_renewal, c.name AS client FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.hosting_renewal IS NOT NULL AND p.status <> 'cancelled' AND p.hosting_renewal <= (CURDATE() + INTERVAL " . CRM_RENEWAL_NOTICE . " DAY)");
    $st->execute();
    foreach ($st->fetchAll() as $p) {
        if (crm_auto_task($pdo, 'renewal:' . $p['id'] . ':' . $p['hosting_renewal'], 'Renouvellement hébergement ' . $p['client'] . ' (' . date('d/m/Y', strtotime($p['hosting_renewal'])) . ')', date('Y-m-d 10:00:00', strtotime($p['hosting_renewal'] . ' -7 day') < time() ? time() : strtotime($p['hosting_renewal'] . ' -7 day')), $p['client_id'], null, $p['id'], 'email')) {
            $created['renewal']++;
        }
    }

    // Projets livrés avec un solde à encaisser
    $st = $pdo->query("SELECT p.id, p.name, p.client_id, p.amount, p.currency, c.name AS client,
            (SELECT COALESCE(SUM(amount), 0) FROM crm_payments WHERE project_id = p.id) AS paid
        FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id WHERE p.status = 'delivered'
          AND NOT EXISTS (SELECT 1 FROM crm_deals d WHERE d.id = p.deal_id AND d.espo_id IS NOT NULL)");
    foreach ($st->fetchAll() as $p) {
        $rest = (float)$p['amount'] - (float)$p['paid'];
        if ($rest > 0 && crm_auto_task($pdo, 'balance:' . $p['id'] . ':' . date('Y-W'), 'Encaisser le solde ' . money((int)$rest, $p['currency']) . ' — ' . $p['client'], date('Y-m-d 10:00:00'), $p['client_id'], null, $p['id'])) {
            $created['balance']++;
        }
    }
    // Dépenses récurrentes : la dépense de l'échéance est ajoutée automatiquement
    $created['expenses'] = 0;
    $step = ['monthly' => '+1 month', 'quarterly' => '+3 month', 'yearly' => '+1 year'];
    $due = $pdo->query("SELECT * FROM crm_expenses WHERE recurrence <> 'none' AND next_due IS NOT NULL AND next_due <= CURDATE()")->fetchAll();
    $ins = $pdo->prepare("INSERT IGNORE INTO crm_expenses (id, category, platform, label, supplier, client_id, spent_on, amount, currency, parent_id, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ajoutée automatiquement (abonnement)')");
    foreach ($due as $e) {
        $next = $e['next_due'];
        $guard = 0;
        while ($next <= date('Y-m-d') && $guard++ < 36) {
            $ins->execute([uuid4(), $e['category'], $e['platform'], $e['label'], $e['supplier'], $e['client_id'], $next, $e['amount'], $e['currency'], $e['id']]);
            $created['expenses'] += $ins->rowCount();
            $next = date('Y-m-d', strtotime($step[$e['recurrence']] ?? '+1 month', strtotime($next)));
        }
        $pdo->prepare('UPDATE crm_expenses SET next_due = ? WHERE id = ?')->execute([$next, $e['id']]);
    }
    // Domaines et hébergements annuels : rappel 14 jours avant le renouvellement
    $soon = $pdo->query("SELECT * FROM crm_expenses WHERE recurrence = 'yearly' AND category IN ('domain', 'hosting')
        AND next_due BETWEEN CURDATE() AND (CURDATE() + INTERVAL 14 DAY)")->fetchAll();
    foreach ($soon as $e) {
        crm_auto_task($pdo, 'expense:' . $e['id'] . ':' . $e['next_due'], 'Renouveler ' . $e['label'] . ' (' . date('d/m/Y', strtotime($e['next_due'])) . ', ' . money((int)$e['amount'], $e['currency']) . ')',
            date('Y-m-d 10:00:00'), $e['client_id'], null, null, 'todo');
    }
    return $created;
}

// Résumé du jour envoyé par e-mail (tâches en retard / du jour, nouveaux prospects, renouvellements)
function crm_digest_html(PDO $pdo): ?string {
    $tasks = $pdo->query("SELECT t.title, t.due_at, c.name FROM crm_tasks t LEFT JOIN crm_clients c ON c.id = t.client_id
        WHERE t.done_at IS NULL AND t.due_at < (CURDATE() + INTERVAL 1 DAY) ORDER BY t.due_at")->fetchAll();
    $leads = $pdo->query("SELECT name, email, source FROM crm_clients WHERE created_at >= (NOW() - INTERVAL 1 DAY) ORDER BY created_at DESC")->fetchAll();
    $renew = $pdo->query("SELECT p.name, p.hosting_renewal, c.name AS client FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.hosting_renewal BETWEEN CURDATE() AND (CURDATE() + INTERVAL " . CRM_RENEWAL_NOTICE . " DAY) AND p.status <> 'cancelled' ORDER BY p.hosting_renewal")->fetchAll();
    if (!$tasks && !$leads && !$renew) return null;
    $row = fn($a, $b) => '<tr><td style="padding:8px 0;border-bottom:1px solid #eee">' . $a . '</td><td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;color:#666;white-space:nowrap">' . $b . '</td></tr>';
    $html = '<h2 style="margin:0 0 4px;font-size:20px">Votre journée</h2><p style="margin:0 0 20px;color:#666;font-size:13px">' . date('d/m/Y') . '</p>';
    if ($tasks) {
        $html .= '<h3 style="font-size:15px;margin:18px 0 6px">À faire (' . count($tasks) . ')</h3><table width="100%" style="font-size:14px">';
        foreach ($tasks as $t) $html .= $row(h($t['title']) . ($t['name'] ? ' <span style="color:#888">· ' . h($t['name']) . '</span>' : ''), strtotime($t['due_at']) < strtotime('today') ? '<b style="color:#c4321f">en retard</b>' : date('H:i', strtotime($t['due_at'])));
        $html .= '</table>';
    }
    if ($leads) {
        $html .= '<h3 style="font-size:15px;margin:18px 0 6px">Nouveaux prospects (' . count($leads) . ')</h3><table width="100%" style="font-size:14px">';
        foreach ($leads as $l) $html .= $row(h($l['name']) . ' <span style="color:#888">· ' . h((string)$l['email']) . '</span>', h((string)$l['source']));
        $html .= '</table>';
    }
    if ($renew) {
        $html .= '<h3 style="font-size:15px;margin:18px 0 6px">Renouvellements à venir</h3><table width="100%" style="font-size:14px">';
        foreach ($renew as $r) $html .= $row(h($r['client']) . ' <span style="color:#888">· ' . h($r['name']) . '</span>', date('d/m/Y', strtotime($r['hosting_renewal'])));
        $html .= '</table>';
    }
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? ($GLOBALS['CONFIG']['website'] ?? 'webina.com.tr'));
    return $html . '<p style="margin-top:24px"><a href="https://' . h($host) . '/admin" style="background:#23772a;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:700">Ouvrir le CRM</a></p>';
}

// Montant converti en TL avec les taux du barème (pricing.json), pour additionner $ / € / TL
function crm_to_try(float $amount, string $currency): float {
    if ($currency === 'TRY') return $amount;
    static $rates = null;
    if ($rates === null) {
        $file = dirname(__DIR__) . '/pricing.json';
        $p = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
        $rates = is_array($p['tlPer'] ?? null) ? $p['tlPer'] : [];
    }
    return $amount * (float)($rates[$currency] ?? 1);
}
