<?php
// Passage quotidien du CRM : relances, renouvellements, soldes + résumé par e-mail.
// cPanel → Tâches Cron, une fois par jour, par exemple à 8 h :
//   php /home/UTILISATEUR/public_html/api/cron.php
// ou, si PHP en ligne de commande n'est pas disponible :
//   curl -s "https://webina.com.tr/api/cron.php?key=CLE"   (CLE = 'cron_key' dans config.php)
$cli = PHP_SAPI === 'cli';
if ($cli) { $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? ''; }
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_prospect.php';

if (!$cli) {
    $key = (string)($CONFIG['cron_key'] ?? '');
    if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) fail('Accès refusé.', 403);
}

$pdo = crm_db();
$made = crm_daily($pdo);
$sent = false;
if (($html = crm_digest_html($pdo)) && ($to = email_list($CONFIG['notify_email'] ?? ''))) {
    $sent = send_html_mail($to[0], 'Webina CRM — votre journée du ' . date('d/m/Y'), mail_layout($html));
}
// Prospection : envoie la suite de la file, en respectant l'espacement et la limite du jour.
// En ligne de commande on attend entre deux envois (10 au plus par passage) ; par URL, un seul.
prospect_schema($pdo);
$mailed = 0;
for ($i = 0; $i < ($cli ? 10 : 1); $i++) {
    $r = prospect_send_next($pdo);
    if (!empty($r['sent'])) { $mailed++; continue; }
    if (($r['reason'] ?? '') !== 'wait' || !$cli) break;
    sleep(max(1, (int)$r['wait']));
}
json_out(['ok' => true, 'created' => $made, 'digest' => $sent, 'prospectMails' => $mailed]);
