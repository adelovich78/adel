<?php
// Tâches planifiées du CRM. Deux cron à configurer dans cPanel → Tâches Cron :
//
//   1) Passage quotidien (relances, renouvellements, rappels clients, résumé, le 1er du mois les rapports) :
//        php /home/UTILISATEUR/public_html/api/cron.php
//      ou par URL :  curl -s "https://webina.com.tr/api/cron.php?key=CLE"
//      → une fois par jour (ex. 8 h).
//
//   2) Prospection autonome (fonctionne même si aucune page n'est ouverte) :
//        php /home/UTILISATEUR/public_html/api/cron.php prospect
//      ou par URL :  curl -s "https://webina.com.tr/api/cron.php?key=CLE&only=prospect"
//      → toutes les 5 minutes. Chaque passage envoie un lot si l'espacement, le plafond
//        du jour et la plage horaire (9 h–19 h) le permettent. Court : jamais de pause longue.
//
//   CLE = 'cron_key' dans config.php.
$cli = PHP_SAPI === 'cli';
if ($cli) { $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? ''; }
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_portal.php';
require __DIR__ . '/_prospect.php';

if (!$cli) {
    $key = (string)($CONFIG['cron_key'] ?? '');
    if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) fail('Accès refusé.', 403);
}

$pdo = crm_db();
$only = (string)($_GET['only'] ?? ($cli ? ($argv[1] ?? '') : ''));

// Un lot de prospection (e-mail + WhatsApp) si l'espacement / le plafond / la plage horaire le permettent
function run_prospect(PDO $pdo): array {
    prospect_schema($pdo);
    $r = prospect_send_next($pdo);
    $wa = wa_enabled() ? prospect_wa_send_next($pdo) : ['sent' => null];
    return ['prospectMails' => !empty($r['sent']) ? 1 : 0,
            'waSent' => (!empty($wa['sent']) && !empty($wa['sent']['ok'])) ? 1 : 0, 'reason' => $r['reason'] ?? null];
}

// Mode « prospection seule » : appelé fréquemment, ne renvoie ni résumé ni rappels
if ($only === 'prospect') {
    json_out(['ok' => true, 'only' => 'prospect'] + run_prospect($pdo));
}

// Passage quotidien complet
$made = crm_daily($pdo);
$sent = false;
if (($html = crm_digest_html($pdo)) && ($to = email_list($CONFIG['notify_email'] ?? ''))) {
    $sent = send_html_mail($to[0], 'Webina CRM — votre journée du ' . date('d/m/Y'), mail_layout($html));
}
// Rappels d'échéance au client (domaine + hébergement) : J-30, J-15, puis chaque jour dès J-7 et en retard
$reminders = crm_client_reminders($pdo);
// Abonnements mensuels : facture créée 5 jours avant l'échéance ; puis rappels des factures impayées (J-3, J0, tous les 3 jours de retard)
$billed = crm_monthly_billing($pdo);
$payReminders = crm_invoice_reminders($pdo);
// Le 1er du mois (ou forcé via ?monthly=1) : compte rendu à l'admin + rapport « état du site » à chaque client
$monthly = false;
$clientReports = 0;
if (date('j') === '1' || !empty($_GET['monthly'])) {
    if (($html = crm_monthly_html($pdo)) && ($to = email_list($CONFIG['notify_email'] ?? ''))) {
        $monthly = send_html_mail($to[0], 'Webina — compte rendu mensuel ' . date('m/Y'), mail_layout($html));
    }
    $clientReports = crm_client_monthly_reports($pdo);
}
// Un lot de prospection au passage (le gros du travail est fait par la tâche fréquente ?only=prospect)
$prospect = run_prospect($pdo);
json_out(['ok' => true, 'created' => $made, 'digest' => $sent, 'reminders' => $reminders, 'billed' => $billed, 'payReminders' => $payReminders, 'monthly' => $monthly,
    'clientReports' => $clientReports, 'prospectMails' => $prospect['prospectMails'], 'waSent' => $prospect['waSent']]);
