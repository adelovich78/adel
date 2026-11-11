<?php
// /api/unsubscribe.php?t=<jeton> — désinscription des e-mails de prospection (lien en bas de chaque e-mail)
// GET : page de confirmation · POST : désinscription en un clic (en-tête List-Unsubscribe-Post)
define('WEBINA_ONE_CLICK', true);
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_prospect.php';

$tok = (string)($_GET['t'] ?? '');
$ok = false;
if (preg_match('/^[0-9a-f]{32}$/', $tok) && ($pdo = db_or_null())) {
    prospect_schema($pdo);
    $st = $pdo->prepare('SELECT id FROM crm_clients WHERE unsub_token = ?');
    $st->execute([$tok]);
    if ($id = $st->fetchColumn()) {
        $pdo->prepare('UPDATE crm_clients SET email_optout = 1 WHERE id = ?')->execute([$id]);
        $pdo->prepare("UPDATE crm_emails SET status = 'skipped', error = 'désinscrit' WHERE client_id = ? AND status = 'queued'")->execute([$id]);
        crm_log($pdo, $id, null, 'email', 'Désinscrit des e-mails de prospection');
        $ok = true;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') json_out(['ok' => $ok]);
header('Content-Type: text/html; charset=utf-8');
$msg = $ok ? 'Aboneliğiniz iptal edildi. Size bir daha tanıtım e-postası göndermeyeceğiz.' : 'Bu bağlantı geçersiz veya süresi dolmuş.';
echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Webina</title></head>'
    . '<body style="margin:0;font-family:Arial,Helvetica,sans-serif;background:#f7f8f7;color:#111412"><div style="max-width:520px;margin:12vh auto;padding:32px;background:#fff;border-radius:16px;box-shadow:0 0 0 1px rgba(17,20,18,.1)">'
    . '<p style="font-weight:700;font-size:20px;margin:0 0 12px">Webina</p><p style="margin:0;line-height:1.6">' . h($msg) . '</p></div></body></html>';
