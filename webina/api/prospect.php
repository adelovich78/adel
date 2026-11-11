<?php
// /api/prospect.php?r=<ressource> — prospection (admin uniquement)
//   list                GET   prospects (fiches « lead ») avec métier, e-mail, site, dernier envoi
//   import              POST  { rows: [{ name, sector, district, email, phone, website, source, notes }] }
//   templates           GET | POST | PUT ?id= | DELETE ?id=
//   preview             POST  { client_id, template_id } → objet et texte personnalisés
//   queue               GET   état de la file · POST { client_ids, template_id, auto } → met en file
//   send                POST  envoie le prochain e-mail si l'espacement le permet
//   clear               POST  vide la file (e-mails pas encore partis)
//   search              GET   ?trade=&district= → recherche OpenStreetMap
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_prospect.php';
require_admin();

$pdo = crm_db();
prospect_schema($pdo);
$r = (string)($_GET['r'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$in = in_array($method, ['POST', 'PUT', 'PATCH'], true) ? body() : [];
$id = (string)($_GET['id'] ?? '');
function pid(string $id): string { if (!preg_match('/^[0-9a-f-]{36}$/', $id)) fail('Identifiant invalide.', 422); return $id; }
function clean_text($v, int $max): string { return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$v)), 0, $max); }

switch ($r) {

case 'list':
    $rows = $pdo->query("SELECT id, name, company, email, phone, sector, city, district, website, source, notes, email_optout,
        DATE_FORMAT(last_mailed_at, '%Y-%m-%dT%H:%i:%s') last_mailed_at, DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%s') created_at,
        (SELECT status FROM crm_emails e WHERE e.client_id = c.id ORDER BY e.created_at DESC LIMIT 1) mail_status
        FROM crm_clients c WHERE status = 'lead' ORDER BY created_at DESC LIMIT 2000")->fetchAll();
    foreach ($rows as &$x) $x['trade'] = prospect_trade($x['sector']);
    $trades = [];
    foreach (PROSPECT_TRADES as $k => $t) $trades[] = ['key' => $k, 'label' => $t['label'], 'search' => (bool)$t['osm']];
    json_out(['items' => $rows, 'trades' => $trades, 'queue' => prospect_queue_state($pdo)]);

case 'import':
    $rows = is_array($in['rows'] ?? null) ? array_slice($in['rows'], 0, 2000) : [];
    $added = 0; $skipped = 0;
    $byEmail = $pdo->prepare('SELECT id FROM crm_clients WHERE email = ? LIMIT 1');
    $byName = $pdo->prepare('SELECT id FROM crm_clients WHERE name = ? AND (phone <=> ?) LIMIT 1');
    $ins = $pdo->prepare("INSERT INTO crm_clients (id, kind, name, company, email, phone, sector, city, district, website, source, notes, status)
        VALUES (?, 'company', ?, ?, ?, ?, ?, 'İstanbul', ?, ?, ?, ?, 'lead')");
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $name = clean_text($row['name'] ?? '', 160);
        if ($name === '') { $skipped++; continue; }
        $email = strtolower(clean_text($row['email'] ?? '', 255));
        // Plusieurs adresses dans une cellule : on garde la première valide
        if ($email !== '' && preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $email, $m)) $email = strtolower($m[0]); else $email = '';
        $phone = clean_text($row['phone'] ?? '', 60) ?: null;
        $web = clean_text($row['website'] ?? '', 255);
        // « HAYIR », « Aucun site », « KONTROL EDİLMELİ » : pas un vrai site
        if (!preg_match('~^(https?://|www\.)|\.[a-z]{2,}(/|$)~i', $web)) $web = '';
        if ($email !== '') { $byEmail->execute([$email]); if ($byEmail->fetch()) { $skipped++; continue; } }
        $byName->execute([$name, $phone]); if ($byName->fetch()) { $skipped++; continue; }
        $ins->execute([uuid4(), $name, $name, $email ?: null, $phone, clean_text($row['sector'] ?? '', 80) ?: null,
            clean_text($row['district'] ?? '', 80) ?: null, $web ?: null, clean_text($row['source'] ?? 'Import', 40),
            mb_substr(trim((string)($row['notes'] ?? '')), 0, 2000) ?: null]);
        $added++;
    }
    json_out(['added' => $added, 'skipped' => $skipped]);

case 'templates':
    if ($method === 'GET') {
        $items = $pdo->query("SELECT id, trade, name, subject, body, DATE_FORMAT(updated_at, '%Y-%m-%dT%H:%i:%s') updated_at FROM crm_templates ORDER BY name")->fetchAll();
        json_out(['items' => $items]);
    }
    if ($method === 'DELETE') { $pdo->prepare('DELETE FROM crm_templates WHERE id = ?')->execute([pid($id)]); json_out(['ok' => true]); }
    $trade = array_key_exists($in['trade'] ?? '', PROSPECT_TRADES) ? $in['trade'] : 'general';
    $name = clean_text($in['name'] ?? '', 120); $subject = clean_text($in['subject'] ?? '', 200);
    $body = mb_substr(str_replace("\r", '', trim((string)($in['body'] ?? ''))), 0, 8000);
    if ($name === '' || $subject === '' || $body === '') fail('Nom, objet et texte sont obligatoires.', 422);
    if ($method === 'POST') {
        $new = uuid4();
        $pdo->prepare('INSERT INTO crm_templates (id, trade, name, subject, body) VALUES (?, ?, ?, ?, ?)')->execute([$new, $trade, $name, $subject, $body]);
        json_out(['id' => $new], 201);
    }
    $pdo->prepare('UPDATE crm_templates SET trade = ?, name = ?, subject = ?, body = ?, updated_at = NOW() WHERE id = ?')->execute([$trade, $name, $subject, $body, pid($id)]);
    json_out(['ok' => true]);

case 'preview':
    $c = $pdo->prepare('SELECT * FROM crm_clients WHERE id = ?'); $c->execute([pid((string)($in['client_id'] ?? ''))]);
    $t = $pdo->prepare('SELECT * FROM crm_templates WHERE id = ?'); $t->execute([pid((string)($in['template_id'] ?? ''))]);
    $client = $c->fetch(); $tpl = $t->fetch();
    if (!$client || !$tpl) fail('Introuvable.', 404);
    json_out(['to' => $client['email'], 'subject' => prospect_fill($tpl['subject'], $client), 'body' => prospect_fill($tpl['body'], $client)]);

case 'queue':
    if ($method === 'GET') {
        $recent = $pdo->query("SELECT e.to_email, e.subject, e.status, e.error, DATE_FORMAT(e.sent_at, '%Y-%m-%dT%H:%i:%s') sent_at, c.name
            FROM crm_emails e LEFT JOIN crm_clients c ON c.id = e.client_id ORDER BY COALESCE(e.sent_at, e.created_at) DESC LIMIT 30")->fetchAll();
        json_out(prospect_queue_state($pdo) + ['recent' => $recent]);
    }
    $ids = array_values(array_filter(is_array($in['client_ids'] ?? null) ? $in['client_ids'] : [], fn($x) => is_string($x) && preg_match('/^[0-9a-f-]{36}$/', $x)));
    $auto = !empty($in['auto']);   // modèle choisi d'après le métier de chaque fiche
    $tplById = []; $tplByTrade = [];
    foreach ($pdo->query('SELECT * FROM crm_templates ORDER BY updated_at DESC')->fetchAll() as $t) {
        $tplById[$t['id']] = $t;
        $tplByTrade[$t['trade']] = $tplByTrade[$t['trade']] ?? $t;
    }
    $chosen = $tplById[(string)($in['template_id'] ?? '')] ?? null;
    if (!$auto && !$chosen) fail('Choisissez un modèle.', 422);
    $get = $pdo->prepare('SELECT * FROM crm_clients WHERE id = ?');
    $ins = $pdo->prepare('INSERT INTO crm_emails (id, client_id, template_id, to_email, subject, body) VALUES (?, ?, ?, ?, ?, ?)');
    $pending = $pdo->prepare("SELECT 1 FROM crm_emails WHERE client_id = ? AND status IN ('queued', 'sending') LIMIT 1");
    $done = 0; $skip = ['email' => 0, 'optout' => 0, 'already' => 0];
    $seen = [];
    foreach (array_slice($ids, 0, 1000) as $cid) {
        $get->execute([$cid]); $c = $get->fetch();
        if (!$c) continue;
        $email = strtolower(trim((string)$c['email']));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) { $skip['email']++; continue; }
        if ((int)$c['email_optout'] === 1) { $skip['optout']++; continue; }
        $pending->execute([$cid]);
        if ($pending->fetch() || (!empty($c['last_mailed_at']) && empty($in['again']))) { $skip['already']++; continue; }
        $tpl = $auto ? ($tplByTrade[prospect_trade($c['sector'])] ?? $tplByTrade['general'] ?? $chosen) : $chosen;
        if (!$tpl) { $skip['email']++; continue; }
        $ins->execute([uuid4(), $cid, $tpl['id'], $email, mb_substr(prospect_fill($tpl['subject'], $c), 0, 200), prospect_fill($tpl['body'], $c)]);
        $seen[$email] = true;
        $done++;
    }
    json_out(['queued' => $done, 'skipped' => $skip] + prospect_queue_state($pdo));

case 'send':
    json_out(prospect_send_next($pdo));

case 'clear':
    $n = $pdo->exec("DELETE FROM crm_emails WHERE status = 'queued'");
    json_out(['removed' => $n] + prospect_queue_state($pdo));

case 'search':
    $trade = (string)($_GET['trade'] ?? '');
    if (!isset(PROSPECT_TRADES[$trade])) fail('Métier inconnu.', 422);
    rate_limit('osm', 30);
    $items = prospect_osm_search($trade, mb_substr((string)($_GET['district'] ?? ''), 0, 60));
    // Déjà dans le CRM ? (même nom ou même e-mail)
    $known = [];
    foreach ($pdo->query('SELECT LOWER(name) n, LOWER(COALESCE(email, \'\')) e FROM crm_clients')->fetchAll() as $k) { $known['n:' . $k['n']] = 1; if ($k['e']) $known['e:' . $k['e']] = 1; }
    foreach ($items as &$it) $it['known'] = isset($known['n:' . mb_strtolower($it['name'])]) || ($it['email'] !== '' && isset($known['e:' . strtolower($it['email'])]));
    json_out(['items' => $items, 'label' => PROSPECT_TRADES[$trade]['label']]);
}
fail('Ressource inconnue.', 404);
