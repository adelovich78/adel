<?php
// /api/clientarea.php?r=… — gestion de l'espace client depuis l'admin
//   access   POST {client_id, enabled}            ouvre / ferme l'accès
//   link     POST {client_id}                     lien de connexion à usage unique (7 jours)
//   client   GET  &id=                            factures, avis, demandes d'un client
//   invoices POST {client_id, project_id, label, amount, currency, issued_on, due_on} | PATCH &id= {paid} | DELETE &id=
//   notices  POST {client_id|null, level, title, body, expires_on} | DELETE &id=
//   requests GET (demandes ouvertes) | PATCH &id= {status: done}
//   welcome  GET &client_id= [&lang=] (brouillon) | POST {client_id, lang, subject, body} (envoi + ouverture de l'accès)
//   chat     POST multipart {client_id, body, kind: message|proposal, file} → message au client
//   docs     POST multipart {client_id, kind, title, visible, file} | GET &id= [&view=1] | PATCH &id= {visible, title} | DELETE &id=
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

case 'welcome':
    // Mail « Merci d'avoir choisi Webina » avec le lien vers l'espace client
    $cid = $uuid($method === 'GET' ? (string)($_GET['client_id'] ?? '') : (string)($in['client_id'] ?? ''));
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$cid]);
    if (!$c) fail('Client introuvable.', 404);
    if (!filter_var((string)$c['email'], FILTER_VALIDATE_EMAIL)) fail('Ajoutez d\'abord une adresse e-mail valide à la fiche.', 422);
    $lang = in_array($_GET['lang'] ?? $in['lang'] ?? '', ['tr', 'en', 'fr', 'ar'], true) ? ($_GET['lang'] ?? $in['lang']) : ($c['lang'] ?: 'tr');
    if ($method === 'GET') json_out(portal_welcome_draft($c, $lang));
    if ($method !== 'POST') break;
    $subject = mb_substr(trim((string)($in['subject'] ?? '')), 0, 200);
    $text = mb_substr(trim((string)($in['body'] ?? '')), 0, 8000);
    if ($subject === '' || $text === '') fail('Objet et message requis.', 422);
    // L'envoi ouvre l'accès s'il était fermé, et retient la langue du client
    $pdo->prepare('UPDATE crm_clients SET portal_enabled = 1, lang = COALESCE(lang, ?) WHERE id = ?')->execute([$lang, $cid]);
    if (!portal_send_welcome($pdo, $c, $lang, $subject, $text)) fail('E-mail non envoyé : vérifiez la configuration SMTP.', 502);
    crm_log($pdo, $cid, null, 'portal', 'Mail de bienvenue envoyé à ' . $c['email'] . ' avec le lien de l\'espace client');
    json_out(['ok' => true]);

case 'client':
    $cid = $uuid($id);
    // Ouvrir la fiche = messages du client lus
    $pdo->prepare("UPDATE portal_chat SET read_by_admin = 1 WHERE client_id = ? AND sender = 'client'")->execute([$cid]);
    json_out([
        'invoices' => portal_all($pdo, 'SELECT * FROM crm_invoices WHERE client_id = ? ORDER BY issued_on DESC', [$cid]),
        'notices' => portal_all($pdo, 'SELECT * FROM crm_notices WHERE client_id = ? ORDER BY created_at DESC', [$cid]),
        'requests' => portal_all($pdo, 'SELECT * FROM portal_requests WHERE client_id = ? ORDER BY created_at DESC LIMIT 20', [$cid]),
        'documents' => portal_all($pdo, 'SELECT id, project_id, kind, title, filename, mime, size, visible, source, note, created_at FROM crm_documents WHERE client_id = ? AND kind <> \'logo\' ORDER BY created_at DESC', [$cid]),
        'chat' => portal_chat_list($pdo, $cid),
    ]);

case 'chat':
    // Webina écrit au client : message ou proposition à valider, avec fichier joint facultatif (multipart)
    if ($method !== 'POST') break;
    $cid = $uuid((string)($_POST['client_id'] ?? ''));
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$cid]);
    if (!$c) fail('Client introuvable.', 404);
    json_out(portal_chat_post($pdo, $c, 'webina', (string)($_POST['body'] ?? ''), $_FILES['file'] ?? null, (string)($_POST['kind'] ?? 'message')), 201);

case 'logo':
    // Logo de la société du client : affiché devant son nom (fiche admin + espace client)
    if ($method !== 'POST') break;
    $cid = $uuid((string)($_POST['client_id'] ?? ''));
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$cid]);
    if (!$c) fail('Client introuvable.', 404);
    $f = $_FILES['file'] ?? [];
    $mime = !empty($f['tmp_name']) ? ((new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '') : '';
    if (!in_array($mime, ['image/png', 'image/jpeg'], true)) fail('Logo : image PNG ou JPG.', 422);
    $doc = crm_doc_upload($pdo, $cid, $f, 'logo', 'Logo', true);
    if ($c['logo_doc']) {
        $old = portal_one($pdo, 'SELECT * FROM crm_documents WHERE id = ?', [$c['logo_doc']]);
        if ($old) { @unlink(crm_files_dir() . '/' . basename($old['stored'])); $pdo->prepare('DELETE FROM crm_documents WHERE id = ?')->execute([$old['id']]); }
    }
    $pdo->prepare('UPDATE crm_clients SET logo_doc = ? WHERE id = ?')->execute([$doc['id'], $cid]);
    json_out(['ok' => true, 'logo' => $doc['id']]);

case 'docs':
    // Contrats et documents du client : envoi (multipart), téléchargement, visibilité, suppression
    if ($method === 'GET') {
        $doc = portal_one($pdo, 'SELECT * FROM crm_documents WHERE id = ?', [$uuid($id)]);
        if (!$doc) fail('Document introuvable.', 404);
        crm_doc_stream($doc, !empty($_GET['view']));
    }
    if ($method === 'POST') {
        $cid = $uuid((string)($_POST['client_id'] ?? ''));
        if (!portal_one($pdo, 'SELECT 1 x FROM crm_clients WHERE id = ?', [$cid])) fail('Client introuvable.', 404);
        $doc = crm_doc_upload($pdo, $cid, $_FILES['file'] ?? [], (string)($_POST['kind'] ?? 'contract'), (string)($_POST['title'] ?? ''), ($_POST['visible'] ?? '1') === '1');
        unset($doc['stored']);
        json_out(['document' => $doc], 201);
    }
    if ($method === 'PATCH') {
        $doc = portal_one($pdo, 'SELECT * FROM crm_documents WHERE id = ?', [$uuid($id)]);
        if (!$doc) fail('Document introuvable.', 404);
        if (array_key_exists('visible', $in)) $pdo->prepare('UPDATE crm_documents SET visible = ? WHERE id = ?')->execute([!empty($in['visible']) ? 1 : 0, $id]);
        if (isset($in['title']) && trim((string)$in['title']) !== '') $pdo->prepare('UPDATE crm_documents SET title = ? WHERE id = ?')->execute([mb_substr(trim((string)$in['title']), 0, 200), $id]);
        json_out(['ok' => true]);
    }
    if ($method === 'DELETE') {
        $doc = portal_one($pdo, 'SELECT * FROM crm_documents WHERE id = ?', [$uuid($id)]);
        if (!$doc) fail('Document introuvable.', 404);
        @unlink(crm_files_dir() . '/' . basename($doc['stored']));
        $pdo->prepare('DELETE FROM crm_documents WHERE id = ?')->execute([$id]);
        crm_log($pdo, $doc['client_id'], null, 'document', 'Document supprimé : ' . $doc['title']);
        json_out(['ok' => true]);
    }
    break;

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
        $inv = crm_invoice_create($pdo, $cid, $pid, $label, $amount, $cur, $issued, $due);
        json_out(['invoice' => $inv], 201);
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
        // Clôture « Traité » : ferme la demande, la tâche de suivi, et prévient le client (message final facultatif)
        $req = portal_one($pdo, 'SELECT * FROM portal_requests WHERE id = ?', [$uuid($id)]);
        if (!$req) fail('Demande introuvable.', 404);
        $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$req['client_id']]);
        if (!$c) fail('Client introuvable.', 404);
        json_out(portal_admin_close($pdo, $req, $c, mb_substr(trim((string)($in['body'] ?? '')), 0, 2000)));
    }
    break;

case 'thread':
    // Fil d'une demande, côté admin
    $req = portal_one($pdo, 'SELECT * FROM portal_requests WHERE id = ?', [$uuid($id)]);
    if (!$req) fail('Demande introuvable.', 404);
    $c = portal_one($pdo, 'SELECT id, name, email, phone, lang FROM crm_clients WHERE id = ?', [$req['client_id']]);
    $p = $req['project_id'] ? portal_one($pdo, 'SELECT name, domain FROM crm_projects WHERE id = ?', [$req['project_id']]) : null;
    json_out([
        'id' => $req['id'], 'kind' => $req['kind'], 'status' => $req['status'], 'created_at' => $req['created_at'], 'done_at' => $req['done_at'],
        'client' => $c, 'project' => $p, 'messages' => portal_thread_messages($pdo, $req),
    ]);

case 'reply':
    // Webina répond au client
    if ($method !== 'POST') break;
    $req = portal_one($pdo, 'SELECT * FROM portal_requests WHERE id = ?', [$uuid((string)($in['request'] ?? ''))]);
    if (!$req) fail('Demande introuvable.', 404);
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ?', [$req['client_id']]);
    if (!$c) fail('Client introuvable.', 404);
    json_out(portal_admin_reply($pdo, $req, $c, mb_substr(trim((string)($in['body'] ?? '')), 0, 2000)));
}
fail('Requête inconnue.', 404);
