<?php
// /api/crm.php?r=<ressource> — CRM (admin uniquement)
//   dashboard | clients | deals | tasks | projects | payments | notes | search
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require_admin();

$pdo = crm_db();
$r = (string)($_GET['r'] ?? '');
$id = (string)($_GET['id'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$in = in_array($method, ['POST', 'PATCH', 'PUT'], true) ? body() : [];

function need_id(string $id): string {
    if (!preg_match('/^[0-9a-f-]{36}$/', $id)) fail('Identifiant invalide.', 422);
    return $id;
}
// Champ texte facultatif (null si vide)
function opt_str(array $d, string $k, int $max): ?string {
    if (!array_key_exists($k, $d) || $d[$k] === null) return null;
    $v = trim((string)$d[$k]);
    return $v === '' ? null : mb_substr($v, 0, $max);
}
function opt_date(array $d, string $k): ?string {
    $v = trim((string)($d[$k] ?? ''));
    if ($v === '') return null;
    $t = strtotime($v);
    if ($t === false) fail("Date invalide : $k", 422);
    return date(strlen($v) > 10 ? 'Y-m-d H:i:s' : 'Y-m-d', $t);
}
function one(PDO $pdo, string $sql, array $p) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetch(); }
function all(PDO $pdo, string $sql, array $p = []) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); }
// Mise à jour partielle : seuls les champs autorisés et présents sont modifiés
function patch(PDO $pdo, string $table, string $id, array $in, array $fields, bool $touch = true): void {
    $set = []; $vals = [];
    foreach ($fields as $f => $type) {
        if (!array_key_exists($f, $in)) continue;
        $v = $in[$f];
        if ($type === 'date') $v = opt_date($in, $f);
        elseif ($type === 'num') $v = is_numeric($v) ? (float)$v : 0;
        elseif (is_array($type)) { if (!in_array($v, $type, true)) fail("Valeur invalide : $f", 422); }
        else $v = opt_str($in, $f, (int)$type);
        $set[] = "$f = ?"; $vals[] = $v;
    }
    if (!$set) return;
    if ($touch) $set[] = 'updated_at = NOW()';
    $vals[] = $id;
    $pdo->prepare("UPDATE $table SET " . implode(', ', $set) . ' WHERE id = ?')->execute($vals);
}

$CLIENT_FIELDS = ['kind' => ['person', 'company'], 'parent_id' => 36, 'name' => 160, 'company' => 160, 'email' => 255, 'phone' => 60,
    'sector' => 80, 'city' => 80, 'website' => 255, 'source' => 40, 'status' => ['lead', 'client', 'archived'], 'notes' => 5000];
$DEAL_FIELDS = ['title' => 200, 'amount' => 'num', 'currency' => ['TRY', 'USD', 'EUR'], 'close_date' => 'date', 'notes' => 5000];
$TASK_FIELDS = ['title' => 200, 'kind' => CRM_TASK_KINDS, 'due_at' => 'date', 'notes' => 5000, 'client_id' => 36, 'deal_id' => 36, 'project_id' => 36];
$PROJECT_FIELDS = ['name' => 200, 'status' => CRM_PROJECT_STATUS, 'start_date' => 'date', 'delivery_date' => 'date', 'amount' => 'num',
    'currency' => ['TRY', 'USD', 'EUR'], 'hosting_renewal' => 'date', 'hosting_amount' => 'num', 'notes' => 5000];

switch ($r) {

/* ---------- Tableau de bord ---------- */
case 'dashboard':
    $pipeline = all($pdo, "SELECT stage, COUNT(*) n, COALESCE(SUM(amount), 0) total, currency FROM crm_deals GROUP BY stage, currency");
    $tasks = all($pdo, "SELECT t.*, c.name client_name FROM crm_tasks t LEFT JOIN crm_clients c ON c.id = t.client_id
        WHERE t.done_at IS NULL AND (t.due_at IS NULL OR t.due_at < (CURDATE() + INTERVAL 8 DAY)) ORDER BY t.due_at IS NULL, t.due_at LIMIT 30");
    $recent = all($pdo, "SELECT a.*, c.name client_name FROM crm_activity a LEFT JOIN crm_clients c ON c.id = a.client_id ORDER BY a.created_at DESC LIMIT 12");
    $renew = all($pdo, "SELECT p.id, p.name, p.hosting_renewal, p.hosting_amount, p.currency, c.name client_name, c.id client_id FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.hosting_renewal IS NOT NULL AND p.status <> 'cancelled' AND p.hosting_renewal <= (CURDATE() + INTERVAL 60 DAY) ORDER BY p.hosting_renewal");
    $month = one($pdo, "SELECT COALESCE(SUM(amount), 0) s FROM crm_payments WHERE currency = 'TRY' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", []);
    $due = one($pdo, "SELECT COALESCE(SUM(p.amount - COALESCE((SELECT SUM(amount) FROM crm_payments WHERE project_id = p.id), 0)), 0) s
        FROM crm_projects p WHERE p.status IN ('in_progress', 'delivered') AND p.currency = 'TRY'
          AND NOT EXISTS (SELECT 1 FROM crm_deals d WHERE d.id = p.deal_id AND d.espo_id IS NOT NULL)", []);
    $counts = one($pdo, "SELECT SUM(status = 'lead') leads, SUM(status = 'client') clients FROM crm_clients", []);
    $spentMonth = 0.0;
    foreach (all($pdo, "SELECT amount, currency FROM crm_expenses WHERE spent_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01')") as $a) $spentMonth += crm_to_try((float)$a['amount'], $a['currency']);
    json_out(['pipeline' => $pipeline, 'tasks' => $tasks, 'recent' => $recent, 'renewals' => $renew,
        'paidThisMonth' => (float)$month['s'], 'toCollect' => max(0, (float)$due['s']), 'counts' => $counts, 'spentThisMonth' => round($spentMonth)]);

/* ---------- Clients ---------- */
case 'clients':
    if ($method === 'GET' && $id === '') {
        $q = '%' . trim((string)($_GET['q'] ?? '')) . '%';
        $status = (string)($_GET['status'] ?? '');
        $where = '(c.name LIKE ? OR c.company LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
        $p = [$q, $q, $q, $q];
        if (in_array($status, ['lead', 'client', 'archived'], true)) { $where .= ' AND c.status = ?'; $p[] = $status; }
        else $where .= " AND c.status <> 'archived'";
        json_out(['items' => all($pdo, "SELECT c.*,
            (SELECT COUNT(*) FROM crm_deals d WHERE d.client_id = c.id AND d.stage NOT IN ('won', 'lost')) open_deals,
            (SELECT COUNT(*) FROM crm_tasks t WHERE t.client_id = c.id AND t.done_at IS NULL) open_tasks
            FROM crm_clients c WHERE $where ORDER BY COALESCE(c.last_activity_at, c.created_at) DESC LIMIT 500", $p)]);
    }
    if ($method === 'GET') {
        need_id($id);
        $c = one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$id]);
        if (!$c) fail('Client introuvable.', 404);
        $ids = array_merge([$id], array_column(all($pdo, 'SELECT id FROM crm_clients WHERE parent_id = ?', [$id]), 'id'));
        $ph = implode(',', array_fill(0, count($ids), '?'));
        json_out([
            'client' => $c,
            'parent' => $c['parent_id'] ? one($pdo, 'SELECT id, name FROM crm_clients WHERE id = ?', [$c['parent_id']]) : null,
            'contacts' => all($pdo, 'SELECT id, name, email, phone FROM crm_clients WHERE parent_id = ? ORDER BY name', [$id]),
            'deals' => all($pdo, "SELECT * FROM crm_deals WHERE client_id IN ($ph) ORDER BY created_at DESC", $ids),
            'tasks' => all($pdo, "SELECT * FROM crm_tasks WHERE client_id IN ($ph) ORDER BY done_at IS NOT NULL, due_at", $ids),
            'projects' => all($pdo, "SELECT p.*, (SELECT COALESCE(SUM(amount), 0) FROM crm_payments WHERE project_id = p.id) paid FROM crm_projects p WHERE client_id IN ($ph) ORDER BY created_at DESC", $ids),
            'payments' => all($pdo, "SELECT pay.*, p.name project_name FROM crm_payments pay JOIN crm_projects p ON p.id = pay.project_id WHERE p.client_id IN ($ph) ORDER BY paid_at DESC", $ids),
            'activity' => all($pdo, "SELECT * FROM crm_activity WHERE client_id IN ($ph) ORDER BY created_at DESC LIMIT 200", $ids),
        ]);
    }
    if ($method === 'POST') {
        $name = opt_str($in, 'name', 160);
        if (!$name) fail('Nom requis.', 422);
        $cid = uuid4();
        $pdo->prepare("INSERT INTO crm_clients (id, kind, name, status, source) VALUES (?, 'person', ?, 'lead', 'Ajout manuel')")->execute([$cid, $name]);
        patch($pdo, 'crm_clients', $cid, $in, $CLIENT_FIELDS);
        crm_log($pdo, $cid, null, 'created', 'Fiche créée');
        json_out(['client' => one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$cid])], 201);
    }
    if ($method === 'PATCH') {
        need_id($id);
        $before = one($pdo, 'SELECT status FROM crm_clients WHERE id = ?', [$id]);
        patch($pdo, 'crm_clients', $id, $in, $CLIENT_FIELDS);
        if (isset($in['status']) && $before && $before['status'] !== $in['status']) crm_log($pdo, $id, null, 'status', 'Statut : ' . $in['status']);
        json_out(['client' => one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$id])]);
    }
    if ($method === 'DELETE') {
        need_id($id);
        // Suppression complète de la fiche et de tout ce qui y est rattaché
        foreach (['crm_tasks', 'crm_activity', 'crm_deals'] as $t) $pdo->prepare("DELETE FROM $t WHERE client_id = ?")->execute([$id]);
        $pdo->prepare('DELETE pay FROM crm_payments pay JOIN crm_projects p ON p.id = pay.project_id WHERE p.client_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM crm_projects WHERE client_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE crm_clients SET parent_id = NULL WHERE parent_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM crm_clients WHERE id = ?')->execute([$id]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Ventes (pipeline) ---------- */
case 'deals':
    if ($method === 'GET') {
        json_out(['items' => all($pdo, "SELECT d.*, c.name client_name, c.company client_company FROM crm_deals d JOIN crm_clients c ON c.id = d.client_id
            WHERE d.stage NOT IN ('won', 'lost') OR d.stage_changed_at > (NOW() - INTERVAL 60 DAY) ORDER BY d.stage_changed_at DESC")]);
    }
    if ($method === 'POST') {
        $cid = need_id((string)($in['client_id'] ?? ''));
        $title = opt_str($in, 'title', 200) ?? 'Nouvelle vente';
        $did = uuid4();
        $pdo->prepare("INSERT INTO crm_deals (id, client_id, title, stage) VALUES (?, ?, ?, 'new')")->execute([$did, $cid, $title]);
        patch($pdo, 'crm_deals', $did, $in, $DEAL_FIELDS);
        crm_log($pdo, $cid, $did, 'deal', 'Vente créée : ' . $title);
        json_out(['deal' => one($pdo, 'SELECT * FROM crm_deals WHERE id = ?', [$did])], 201);
    }
    if ($method === 'PATCH') {
        need_id($id);
        $deal = one($pdo, 'SELECT * FROM crm_deals WHERE id = ?', [$id]);
        if (!$deal) fail('Vente introuvable.', 404);
        patch($pdo, 'crm_deals', $id, $in, $DEAL_FIELDS);
        if (isset($in['stage']) && $in['stage'] !== $deal['stage']) {
            if (!in_array($in['stage'], CRM_STAGES, true)) fail('Étape invalide.', 422);
            $from = $deal['stage'];
            $pdo->prepare('UPDATE crm_deals SET stage = ?, stage_changed_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$in['stage'], $id]);
            crm_on_stage_change($pdo, one($pdo, 'SELECT * FROM crm_deals WHERE id = ?', [$id]), $from, $in['stage']);
        }
        json_out(['deal' => one($pdo, 'SELECT * FROM crm_deals WHERE id = ?', [$id])]);
    }
    if ($method === 'DELETE') {
        need_id($id);
        $pdo->prepare('DELETE FROM crm_tasks WHERE deal_id = ? AND done_at IS NULL')->execute([$id]);
        $pdo->prepare('DELETE FROM crm_deals WHERE id = ?')->execute([$id]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Tâches ---------- */
case 'tasks':
    if ($method === 'GET') {
        $done = ($_GET['done'] ?? '0') === '1';
        json_out(['items' => all($pdo, 'SELECT t.*, c.name client_name, d.title deal_title, p.name project_name FROM crm_tasks t
            LEFT JOIN crm_clients c ON c.id = t.client_id LEFT JOIN crm_deals d ON d.id = t.deal_id LEFT JOIN crm_projects p ON p.id = t.project_id
            WHERE t.done_at IS ' . ($done ? 'NOT NULL ORDER BY t.done_at DESC LIMIT 200' : 'NULL ORDER BY t.due_at IS NULL, t.due_at'))]);
    }
    if ($method === 'POST') {
        $title = opt_str($in, 'title', 200);
        if (!$title) fail('Titre requis.', 422);
        $tid = uuid4();
        $pdo->prepare('INSERT INTO crm_tasks (id, title) VALUES (?, ?)')->execute([$tid, $title]);
        patch($pdo, 'crm_tasks', $tid, $in, $TASK_FIELDS, false);
        $t = one($pdo, 'SELECT * FROM crm_tasks WHERE id = ?', [$tid]);
        if ($t['client_id']) crm_log($pdo, $t['client_id'], $t['deal_id'], 'task', 'Tâche ajoutée : ' . $title);
        json_out(['task' => $t], 201);
    }
    if ($method === 'PATCH') {
        need_id($id);
        patch($pdo, 'crm_tasks', $id, $in, $TASK_FIELDS, false);
        if (array_key_exists('done', $in)) {
            $pdo->prepare('UPDATE crm_tasks SET done_at = ' . ($in['done'] ? 'NOW()' : 'NULL') . ' WHERE id = ?')->execute([$id]);
            $t = one($pdo, 'SELECT * FROM crm_tasks WHERE id = ?', [$id]);
            if ($in['done'] && $t['client_id']) crm_log($pdo, $t['client_id'], $t['deal_id'], 'task', 'Fait : ' . $t['title']);
        }
        json_out(['task' => one($pdo, 'SELECT * FROM crm_tasks WHERE id = ?', [$id])]);
    }
    if ($method === 'DELETE') {
        $pdo->prepare('DELETE FROM crm_tasks WHERE id = ?')->execute([need_id($id)]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Projets ---------- */
case 'projects':
    if ($method === 'GET') {
        json_out(['items' => all($pdo, "SELECT p.*, c.name client_name, (SELECT COALESCE(SUM(amount), 0) FROM crm_payments WHERE project_id = p.id) paid
            FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id ORDER BY FIELD(p.status, 'in_progress', 'delivered', 'paused', 'cancelled'), p.created_at DESC")]);
    }
    if ($method === 'POST') {
        $cid = need_id((string)($in['client_id'] ?? ''));
        $pid = uuid4();
        $pdo->prepare("INSERT INTO crm_projects (id, client_id, name, start_date) VALUES (?, ?, ?, CURDATE())")->execute([$pid, $cid, opt_str($in, 'name', 200) ?? 'Nouveau projet']);
        patch($pdo, 'crm_projects', $pid, $in, $PROJECT_FIELDS);
        crm_log($pdo, $cid, null, 'project', 'Projet créé : ' . (opt_str($in, 'name', 200) ?? 'Nouveau projet'));
        json_out(['project' => one($pdo, 'SELECT * FROM crm_projects WHERE id = ?', [$pid])], 201);
    }
    if ($method === 'PATCH') {
        need_id($id);
        $before = one($pdo, 'SELECT * FROM crm_projects WHERE id = ?', [$id]);
        patch($pdo, 'crm_projects', $id, $in, $PROJECT_FIELDS);
        if (isset($in['status']) && $before && $before['status'] !== $in['status']) {
            if ($in['status'] === 'delivered' && !$before['delivery_date']) $pdo->prepare('UPDATE crm_projects SET delivery_date = CURDATE() WHERE id = ?')->execute([$id]);
            crm_log($pdo, $before['client_id'], $before['deal_id'], 'project', 'Projet « ' . $before['name'] . ' » : ' . $in['status']);
        }
        json_out(['project' => one($pdo, 'SELECT * FROM crm_projects WHERE id = ?', [$id])]);
    }
    if ($method === 'DELETE') {
        need_id($id);
        $pdo->prepare('DELETE FROM crm_payments WHERE project_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM crm_projects WHERE id = ?')->execute([$id]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Paiements ---------- */
case 'payments':
    if ($method === 'POST') {
        $pid = need_id((string)($in['project_id'] ?? ''));
        $p = one($pdo, 'SELECT * FROM crm_projects WHERE id = ?', [$pid]);
        if (!$p) fail('Projet introuvable.', 404);
        $amount = (float)($in['amount'] ?? 0);
        if ($amount <= 0) fail('Montant invalide.', 422);
        $pdo->prepare('INSERT INTO crm_payments (id, project_id, amount, currency, paid_at, method, note) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([uuid4(), $pid, $amount, $p['currency'], opt_date($in, 'paid_at') ?? date('Y-m-d'), opt_str($in, 'method', 40), opt_str($in, 'note', 255)]);
        crm_log($pdo, $p['client_id'], $p['deal_id'], 'payment', 'Paiement reçu : ' . money((int)$amount, $p['currency']) . ' — ' . $p['name']);
        json_out(['ok' => true], 201);
    }
    if ($method === 'DELETE') {
        $pdo->prepare('DELETE FROM crm_payments WHERE id = ?')->execute([need_id($id)]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Notes (historique) ---------- */
case 'notes':
    if ($method === 'POST') {
        $cid = need_id((string)($in['client_id'] ?? ''));
        $text = opt_str($in, 'text', 5000);
        if (!$text) fail('Note vide.', 422);
        crm_log($pdo, $cid, opt_str($in, 'deal_id', 36), 'note', $text);
        json_out(['ok' => true], 201);
    }
    if ($method === 'DELETE') {
        $pdo->prepare("DELETE FROM crm_activity WHERE id = ? AND kind = 'note'")->execute([need_id($id)]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Dépenses ---------- */
case 'expenses':
    $EXP_FIELDS = ['category' => CRM_EXPENSE_CATEGORIES, 'label' => 160, 'supplier' => 120, 'client_id' => 36, 'spent_on' => 'date',
        'amount' => 'num', 'currency' => ['TRY', 'USD', 'EUR'], 'impressions' => 'num', 'clicks' => 'num', 'leads' => 'num',
        'recurrence' => CRM_RECURRENCES, 'note' => 255];
    if ($method === 'GET') {
        $months = max(1, min(24, (int)($_GET['months'] ?? 6)));
        $from = date('Y-m-01', strtotime('-' . ($months - 1) . ' month', strtotime(date('Y-m-01'))));
        $rows = all($pdo, "SELECT e.*, c.name client_name FROM crm_expenses e LEFT JOIN crm_clients c ON c.id = e.client_id
            WHERE e.spent_on >= ? ORDER BY e.spent_on DESC, e.created_at DESC", [$from]);
        $months_ = [];
        for ($i = $months - 1; $i >= 0; $i--) $months_[date('Y-m', strtotime("-$i month", strtotime(date('Y-m-01'))))] = ['total' => 0, 'byCat' => [], 'ads' => [], 'leads' => 0, 'siteLeads' => 0, 'revenue' => 0];
        foreach ($rows as &$r) {
            $r['amount_try'] = round(crm_to_try((float)$r['amount'], $r['currency']), 2);
            $m = substr($r['spent_on'], 0, 7);
            if (!isset($months_[$m])) continue;
            $months_[$m]['total'] += $r['amount_try'];
            $months_[$m]['byCat'][$r['category']] = ($months_[$m]['byCat'][$r['category']] ?? 0) + $r['amount_try'];
            if ($r['category'] === 'ads') {
                $months_[$m]['ads'][$r['platform'] ?: 'other'] = ($months_[$m]['ads'][$r['platform'] ?: 'other'] ?? 0) + $r['amount_try'];
                $months_[$m]['leads'] += (int)$r['leads'];
            }
        }
        unset($r);
        foreach (all($pdo, "SELECT DATE_FORMAT(created_at, '%Y-%m') m, COUNT(*) n FROM crm_clients
            WHERE created_at >= ? AND source IN ('Devis en ligne', 'Formulaire de contact') GROUP BY m", [$from]) as $l) {
            if (isset($months_[$l['m']])) $months_[$l['m']]['siteLeads'] = (int)$l['n'];
        }
        foreach (all($pdo, "SELECT DATE_FORMAT(paid_at, '%Y-%m') m, currency, SUM(amount) s FROM crm_payments WHERE paid_at >= ? GROUP BY m, currency", [$from]) as $pp) {
            if (isset($months_[$pp['m']])) $months_[$pp['m']]['revenue'] += crm_to_try((float)$pp['s'], $pp['currency']);
        }
        $subs = all($pdo, "SELECT e.*, c.name client_name FROM crm_expenses e LEFT JOIN crm_clients c ON c.id = e.client_id
            WHERE e.recurrence <> 'none' ORDER BY e.next_due");
        foreach ($subs as &$r) $r['amount_try'] = round(crm_to_try((float)$r['amount'], $r['currency']), 2);
        unset($r);
        json_out(['items' => $rows, 'months' => $months_, 'subscriptions' => $subs]);
    }
    if ($method === 'POST') {
        if (!in_array($in['category'] ?? '', CRM_EXPENSE_CATEGORIES, true)) fail('Catégorie invalide.', 422);
        if (!is_numeric($in['amount'] ?? null) || (float)$in['amount'] <= 0) fail('Montant invalide.', 422);
        $platform = ($in['category'] === 'ads' && in_array($in['platform'] ?? '', CRM_AD_PLATFORMS, true)) ? $in['platform'] : null;
        $label = opt_str($in, 'label', 160) ?? ($platform ? ucfirst($platform) : 'Dépense');
        $eid = uuid4();
        $pdo->prepare('INSERT INTO crm_expenses (id, category, platform, label, spent_on, amount) VALUES (?, ?, ?, ?, CURDATE(), ?)')
            ->execute([$eid, $in['category'], $platform, $label, (float)$in['amount']]);
        patch($pdo, 'crm_expenses', $eid, $in, $EXP_FIELDS, false);
        $pdo->prepare('UPDATE crm_expenses SET impressions = NULLIF(impressions, 0), clicks = NULLIF(clicks, 0), leads = NULLIF(leads, 0) WHERE id = ?')->execute([$eid]);
        // Abonnement : la prochaine échéance part de la date de la dépense
        $e = one($pdo, 'SELECT * FROM crm_expenses WHERE id = ?', [$eid]);
        if ($e['recurrence'] !== 'none') {
            $step = ['monthly' => '+1 month', 'quarterly' => '+3 month', 'yearly' => '+1 year'][$e['recurrence']];
            $pdo->prepare('UPDATE crm_expenses SET next_due = ? WHERE id = ?')->execute([date('Y-m-d', strtotime($step, strtotime($e['spent_on']))), $eid]);
        }
        json_out(['item' => one($pdo, 'SELECT * FROM crm_expenses WHERE id = ?', [$eid])], 201);
    }
    if ($method === 'PATCH') {
        need_id($id);
        patch($pdo, 'crm_expenses', $id, $in, $EXP_FIELDS, false);
        if (array_key_exists('platform', $in)) $pdo->prepare('UPDATE crm_expenses SET platform = ? WHERE id = ?')->execute([in_array($in['platform'], CRM_AD_PLATFORMS, true) ? $in['platform'] : null, $id]);
        if (($in['recurrence'] ?? null) === 'none') $pdo->prepare('UPDATE crm_expenses SET next_due = NULL WHERE id = ?')->execute([$id]);
        json_out(['item' => one($pdo, 'SELECT * FROM crm_expenses WHERE id = ?', [$id])]);
    }
    if ($method === 'DELETE') {
        need_id($id);
        // Arrêter un abonnement ne supprime pas les dépenses déjà passées
        if (($_GET['stop'] ?? '') === '1') $pdo->prepare("UPDATE crm_expenses SET recurrence = 'none', next_due = NULL WHERE id = ?")->execute([$id]);
        else $pdo->prepare('DELETE FROM crm_expenses WHERE id = ?')->execute([$id]);
        json_out(['ok' => true]);
    }
    break;

/* ---------- Recherche rapide ---------- */
case 'search':
    $q = '%' . trim((string)($_GET['q'] ?? '')) . '%';
    json_out(['items' => all($pdo, "SELECT id, name, company, email, status FROM crm_clients WHERE status <> 'archived'
        AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY name LIMIT 15", [$q, $q, $q, $q])]);
}

fail('Requête inconnue.', 404);
