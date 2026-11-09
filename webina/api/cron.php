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
json_out(['ok' => true, 'created' => $made, 'digest' => $sent]);
