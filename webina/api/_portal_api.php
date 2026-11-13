<?php
// API de l'espace client, appelée depuis my.webina.com.tr/api/portal.php (qui inclut ce fichier).
//   POST code   {email, lang}        → envoie un code à 6 chiffres (réponse identique que le compte existe ou non)
//   POST verify {email, code}        → ouvre la session
//   POST link   {token}              → ouvre la session avec le lien envoyé par Webina
//   GET  me                          → tableau de bord complet
//   POST request {kind, service, detail}
//   PATCH account {name, phone, company, lang}
//   POST logout
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_portal.php';

$pdo = crm_db();
$r = (string)($_GET['r'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$in = in_array($method, ['POST', 'PATCH'], true) ? body() : [];

switch ($r) {
case 'code':
    if ($method !== 'POST') break;
    rate_limit('portal_code', 6, 900);
    $email = strtolower(trim((string)($in['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('E-posta geçersiz.', 422);
    $lang = in_array($in['lang'] ?? '', ['tr', 'en', 'fr', 'ar'], true) ? $in['lang'] : 'tr';
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE LOWER(email) = ? AND portal_enabled = 1 ORDER BY created_at LIMIT 1', [$email]);
    if ($c) portal_send_code($c, portal_token($pdo, $c['id'], 'code'), $c['lang'] ?: $lang);
    json_out(['ok' => true]);

case 'verify':
    if ($method !== 'POST') break;
    rate_limit('portal_verify', 10, 900);
    $code = preg_replace('/\D/', '', (string)($in['code'] ?? ''));
    $cid = strlen($code) === 6 ? portal_check_code($pdo, trim((string)($in['email'] ?? '')), $code) : null;
    if (!$cid) fail('Kod hatalı veya süresi dolmuş.', 401);
    portal_login($pdo, $cid);
    json_out(['ok' => true]);

case 'link':
    if ($method !== 'POST') break;
    rate_limit('portal_link', 10, 900);
    $cid = portal_check_link($pdo, (string)($in['token'] ?? ''));
    if (!$cid) fail('Bağlantı geçersiz veya süresi dolmuş.', 401);
    portal_login($pdo, $cid);
    json_out(['ok' => true]);

case 'me':
    $c = portal_client($pdo);
    if (!$c) fail('Oturum yok.', 401);
    json_out(portal_dashboard($pdo, $c));

case 'request':
    if ($method !== 'POST') break;
    $c = portal_client($pdo);
    if (!$c) fail('Oturum yok.', 401);
    rate_limit('portal_request', 10, 3600);
    $sid = (string)($in['service'] ?? '');
    json_out(portal_request($pdo, $c, (string)($in['kind'] ?? ''), preg_match('/^[0-9a-f-]{36}$/', $sid) ? $sid : null, mb_substr(trim((string)($in['detail'] ?? '')), 0, 2000)));

case 'account':
    if ($method !== 'PATCH') break;
    $c = portal_client($pdo);
    if (!$c) fail('Oturum yok.', 401);
    $name = mb_substr(trim((string)($in['name'] ?? $c['name'])), 0, 160);
    if ($name === '') fail('Ad gerekli.', 422);
    $lang = in_array($in['lang'] ?? '', ['tr', 'en', 'fr', 'ar'], true) ? $in['lang'] : $c['lang'];
    $pdo->prepare('UPDATE crm_clients SET name = ?, phone = ?, company = ?, lang = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$name, mb_substr(trim((string)($in['phone'] ?? '')), 0, 60) ?: null, mb_substr(trim((string)($in['company'] ?? '')), 0, 160) ?: null, $lang, $c['id']]);
    crm_log($pdo, $c['id'], null, 'portal', 'Coordonnées modifiées depuis l\'espace client');
    json_out(['ok' => true]);

case 'logout':
    portal_session();
    $_SESSION = [];
    session_destroy();
    json_out(['ok' => true]);
}
fail('Requête inconnue.', 404);
