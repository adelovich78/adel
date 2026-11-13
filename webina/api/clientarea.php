<?php
// /api/clientarea.php?r=… — gestion de l'espace client depuis l'admin
//   access   POST {client_id, enabled}            ouvre / ferme l'accès
//   link     POST {client_id}                     lien de connexion à usage unique (7 jours)
//   client   GET  &id=                            factures, avis, demandes d'un client
//   invoices POST {client_id, project_id, label, amount, currency, issued_on, due_on} | PATCH &id= {paid} | DELETE &id=
//   notices  POST {client_id|null, level, title, body, expires_on} | DELETE &id=
//   requests GET (demandes ouvertes) | PATCH &id= {status: done}
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_portal.php';
require_admin();

$pdo = crm_db();
$r = (string)($_GET['r'] ?? '');
$id = (string)($_GET['id'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$in = in_array($method, ['POST', 'PATCH'], true) ? body() : [];
$uuid = function (string $v): string { if (!preg_match('/^[0-9a-f-]{36}$/', $v)) fail('Identifiant invalide.', 422); return $v; };
$date = function ($v, bool $required = false): ?string {
    $v = trim((string)$v);
    if ($v === '') { if ($required) fail('Date requise.', 422); return null; }
    $t = strtotime($v); if ($t === false) fail('Date invalide.', 422);
    return date('Y-m-d', $t);
};

switch ($r) {
case 'access':
    if ($method !== 'POST') break;
    $cid = $uuid((string)($in['client_id'] ?? ''));
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$cid]);
    if (!$c) fail('Client introuvable.', 404);
    $on = !empty($in['enabled']);
    if ($on && !filter_var((string)$c['email'], FILTER_VALIDATE_EMAIL)) fail('Ajoutez d\'abord une adresse e-mail valide à la fiche.', 422);
    $pdo->prepare('UPDATE crm_clients SET portal_enabled = ? WHERE id = ?')->execute([$on ? 1 : 0, $cid]);
    crm_log($pdo, $cid, null, 'portal', $on ? 'Accès à l\'espace client ouvert' : 'Accès à l\'espace client fermé');
    json_out(['ok' => true]);

case 'link':
    if ($method !== 'POST') break;
    $cid = $uuid((string)($in['client_id'] ?? ''));
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ? AND portal_enabled = 1', [$cid]);
    if (!$c) fail('Ouvrez d\'abord l\'accès à l\'espace client.', 422);
    $url = portal_url() . '/?t=' . portal_token($pdo, $cid, 'link');
    crm_log($pdo, $cid, null, 'portal', 'Lien de connexion généré (valable ' . PORTAL_LINK_TTL . ' jours)');
    json_out(['url' => $url, 'phone' => preg_replace('/\D/', '', (string)$c['phone'])]);

case 'client':
    $cid = $uuid($id);
    json_out([
        'invoices' => portal_all($pdo, 'SELECT * FROM crm_invoices WHERE client_id = ? ORDER BY issued_on DESC', [$cid]),
        'notices' => portal_all($pdo, 'SELECT * FROM crm_notices WHERE client_id = ? ORDER BY created_at DESC', [$cid]),
        'requests' => portal_all($pdo, 'SELECT * FROM portal_requests WHERE client_id = ? ORDER BY created_at DESC LIMIT 20', [$cid]),
    ]);

case 'invoices':
    if ($method === 'POST') {
        $cid = $uuid((string)($in['client_id'] ?? ''));
        $pid = trim((string)($in['project_id'] ?? '')) !== '' ? $uuid((string)$in['project_id']) : null;
        $label = mb_substr(trim((string)($in['label'] ?? '')), 0, 200);
        $amount = (float)($in['amount'] ?? 0);
        if ($label === '' || $amount <= 0) fail('Libellé et montant requis.', 422);
        $cur = in_array($in['currency'] ?? '', ['TRY', 'USD', 'EUR'], true) ? $in['currency'] : 'TRY';
        $issued = $date($in['issued_on'] ?? date('Y-m-d')) ?? date('Y-m-d');
        $due = $date($in['due_on'] ?? '') ?? date('Y-m-d', strtotime($issued . ' +7 day'));
        // Numéro lisible : WB-2026-0001, à la suite dans l'année
        $year = substr($issued, 0, 4);
        $n = (int)portal_one($pdo, 'SELECT COUNT(*) n FROM crm_invoices WHERE number LIKE ?', ["WB-$year-%"])['n'] + 1;
        do { $number = sprintf('WB-%s-%04d', $year, $n++); } while (portal_one($pdo, 'SELECT 1 x FROM crm_invoices WHERE number = ?', [$number]));
        $iid = uuid4();
        $pdo->prepare('INSERT INTO crm_invoices (id, client_id, project_id, number, label, amount, currency, issued_on, due_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$iid, $cid, $pid, $number, $label, $amount, $cur, $issued, $due]);
        crm_log($pdo, $cid, null, 'invoice', 'Facture ' . $number . ' : ' . money((int)$amount, $cur) . ' — ' . $label);
        json_out(['invoice' => portal_one($pdo, 'SELECT * FROM crm_invoices WHERE id = ?', [$iid])], 201);
    }
    if ($method === 'PATCH') {
        $i = portal_one($pdo, 'SELECT * FROM crm_invoices WHERE id = ?', [$uuid($id)]);
        if (!$i) fail('Facture introuvable.', 404);
        if (array_key_exists('paid', $in)) {
            if ($in['paid'] && !$i['paid_at']) {
                $pdo->prepare('UPDATE crm_invoices SET paid_at = CURDATE() WHERE id = ?')->execute([$i['id']]);
                // Le paiement apparaît aussi dans le projet du CRM
                if ($i['project_id']) $pdo->prepare('INSERT INTO crm_payments (id, project_id, amount, currency, paid_at, method, note) VALUES (?, ?, ?, ?, CURDATE(), ?, ?)')
                    ->execute([uuid4(), $i['project_id'], $i['amount'], $i['currency'], 'Facture', $i['number']]);
                crm_log($pdo, $i['client_id'], null, 'payment', 'Facture ' . $i['number'] . ' payée');
            } elseif (!$in['paid'] && $i['paid_at']) {
                $pdo->prepare('UPDATE crm_invoices SET paid_at = NULL WHERE id = ?')->execute([$i['id']]);
                $pdo->prepare("DELETE FROM crm_payments WHERE note = ? AND method = 'Facture'")->execute([$i['number']]);
            }
        }
        json_out(['ok' => true]);
    }
    if ($method === 'DELETE') {
        $pdo->prepare('DELETE FROM crm_invoices WHERE id = ? AND paid_at IS NULL')->execute([$uuid($id)]);
        json_out(['ok' => true]);
    }
    break;

case 'notices':
    if ($method === 'GET') json_out(['items' => portal_all($pdo, 'SELECT * FROM crm_notices WHERE client_id IS NULL ORDER BY created_at DESC')]);
    if ($method === 'POST') {
        $cid = trim((string)($in['client_id'] ?? '')) !== '' ? $uuid((string)$in['client_id']) : null;
        $title = mb_substr(trim((string)($in['title'] ?? '')), 0, 160);
        if ($title === '') fail('Titre requis.', 422);
        $level = in_array($in['level'] ?? '', ['info', 'warning', 'critical'], true) ? $in['level'] : 'info';
        $pdo->prepare('INSERT INTO crm_notices (id, client_id, level, title, body, expires_on) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([uuid4(), $cid, $level, $title, mb_substr(trim((string)($in['body'] ?? '')), 0, 2000) ?: null, $date($in['expires_on'] ?? '')]);
        json_out(['ok' => true], 201);
    }
    if ($method === 'DELETE') {
        $pdo->prepare('DELETE FROM crm_notices WHERE id = ?')->execute([$uuid($id)]);
        json_out(['ok' => true]);
    }
    break;

case 'requests':
    if ($method === 'GET') {
        json_out(['items' => portal_all($pdo, "SELECT r.*, c.name client_name, p.name project_name, p.domain FROM portal_requests r
            JOIN crm_clients c ON c.id = r.client_id LEFT JOIN crm_projects p ON p.id = r.project_id
            WHERE r.status = 'open' ORDER BY r.created_at DESC"),
            'clients' => portal_all($pdo, 'SELECT id, name, email, portal_last_login FROM crm_clients WHERE portal_enabled = 1 ORDER BY name')]);
    }
    if ($method === 'PATCH') {
        $pdo->prepare("UPDATE portal_requests SET status = 'done', done_at = NOW() WHERE id = ?")->execute([$uuid($id)]);
        json_out(['ok' => true]);
    }
    break;
}
fail('Requête inconnue.', 404);
