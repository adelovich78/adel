<?php
// Espace client (my.webina.com.tr) : connexion, état des services, factures, avis, demandes.
// Les données sont celles du CRM : un client = crm_clients, un service = crm_projects.

const PORTAL_SOON_DAYS = 30;          // renouvellement à moins de 30 jours : action demandée
const PORTAL_CODE_TTL = 15;           // minutes de validité du code envoyé par e-mail
const PORTAL_LINK_TTL = 7;            // jours de validité du lien de connexion généré par l'admin
const PORTAL_PLANS = ['vitrin', 'randevu', 'eticaret', 'ozel'];
const PORTAL_SITE_STATUS = ['building', 'preview', 'live', 'suspended'];
const PORTAL_SSL_STATUS = ['active', 'pending', 'expired'];
const PORTAL_REQUEST_KINDS = ['renew', 'upgrade', 'support', 'approve'];

function portal_one(PDO $pdo, string $sql, array $p = []) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetch(); }
function portal_all(PDO $pdo, string $sql, array $p = []) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); }

/* ---------- Session du client (distincte de celle de l'admin) ---------- */
function portal_session(): void {
    global $CONFIG;
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('webina_client');
    session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function portal_client(PDO $pdo): ?array {
    portal_session();
    if (empty($_SESSION['cid'])) return null;
    $c = portal_one($pdo, 'SELECT * FROM crm_clients WHERE id = ? AND portal_enabled = 1', [$_SESSION['cid']]);
    return $c ?: null;
}

function portal_login(PDO $pdo, string $cid): void {
    portal_session();
    session_regenerate_id(true);
    $_SESSION['cid'] = $cid;
    $pdo->prepare('UPDATE crm_clients SET portal_last_login = NOW() WHERE id = ?')->execute([$cid]);
    crm_log($pdo, $cid, null, 'portal', 'Connexion à l\'espace client');
}

/* ---------- Jetons : code à 6 chiffres (e-mail) ou lien à usage unique (admin) ---------- */
function portal_token(PDO $pdo, string $cid, string $kind): string {
    $raw = $kind === 'code' ? str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT) : bin2hex(random_bytes(24));
    $ttl = $kind === 'code' ? 'INTERVAL ' . PORTAL_CODE_TTL . ' MINUTE' : 'INTERVAL ' . PORTAL_LINK_TTL . ' DAY';
    // Un seul jeton actif par type : l'ancien code ne sert plus
    $pdo->prepare('UPDATE portal_tokens SET used_at = NOW() WHERE client_id = ? AND kind = ? AND used_at IS NULL')->execute([$cid, $kind]);
    $pdo->prepare("INSERT INTO portal_tokens (id, client_id, kind, token_hash, expires_at) VALUES (?, ?, ?, ?, NOW() + $ttl)")
        ->execute([uuid4(), $cid, $kind, hash('sha256', $raw)]);
    return $raw;
}

function portal_check_code(PDO $pdo, string $email, string $code): ?string {
    $c = portal_one($pdo, 'SELECT id FROM crm_clients WHERE LOWER(email) = ? AND portal_enabled = 1 ORDER BY created_at LIMIT 1', [strtolower($email)]);
    if (!$c) return null;
    $t = portal_one($pdo, "SELECT * FROM portal_tokens WHERE client_id = ? AND kind = 'code' AND used_at IS NULL AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1", [$c['id']]);
    if (!$t) return null;
    if (!hash_equals($t['token_hash'], hash('sha256', $code))) {
        // 5 erreurs : le code est annulé
        $pdo->prepare('UPDATE portal_tokens SET attempts = attempts + 1, used_at = IF(attempts + 1 >= 5, NOW(), used_at) WHERE id = ?')->execute([$t['id']]);
        return null;
    }
    $pdo->prepare('UPDATE portal_tokens SET used_at = NOW() WHERE id = ?')->execute([$t['id']]);
    return $c['id'];
}

function portal_check_link(PDO $pdo, string $token): ?string {
    if (!preg_match('/^[a-f0-9]{48}$/', $token)) return null;
    $t = portal_one($pdo, "SELECT t.* FROM portal_tokens t JOIN crm_clients c ON c.id = t.client_id
        WHERE t.kind = 'link' AND t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW() AND c.portal_enabled = 1", [hash('sha256', $token)]);
    if (!$t) return null;
    $pdo->prepare('UPDATE portal_tokens SET used_at = NOW() WHERE id = ?')->execute([$t['id']]);
    return $t['client_id'];
}

function portal_url(): string {
    global $CONFIG;
    return rtrim($CONFIG['portal_url'] ?? 'https://my.webina.com.tr', '/');
}

/* ---------- Tableau de bord : statuts calculés ---------- */
function portal_days(?string $date): ?int {
    if (!$date) return null;
    return (int)floor((strtotime($date . ' 00:00:00') - strtotime(date('Y-m-d') . ' 00:00:00')) / 86400);
}
// ok | soon (moins de 30 jours) | expired | none
function portal_date_status(?string $date): string {
    $d = portal_days($date);
    if ($d === null) return 'none';
    if ($d < 0) return 'expired';
    return $d <= PORTAL_SOON_DAYS ? 'soon' : 'ok';
}

function portal_dashboard(PDO $pdo, array $c): array {
    $projects = portal_all($pdo, "SELECT * FROM crm_projects WHERE client_id = ? AND status <> 'cancelled' ORDER BY created_at", [$c['id']]);
    $invoices = portal_all($pdo, 'SELECT * FROM crm_invoices WHERE client_id = ? ORDER BY issued_on DESC, number DESC', [$c['id']]);
    $today = date('Y-m-d');
    foreach ($invoices as &$i) {
        $i['status'] = $i['paid_at'] ? 'paid' : ($i['due_on'] < $today ? 'overdue' : 'unpaid');
        $i['amount'] = (float)$i['amount'];
    }
    unset($i);
    $actions = [];
    $services = [];
    foreach ($projects as $p) {
        // Une facture sans service rattaché compte pour le service quand le client n'en a qu'un
        $single = count($projects) === 1;
        $unpaid = array_values(array_filter($invoices, fn($i) => ($i['project_id'] === $p['id'] || ($single && !$i['project_id'])) && $i['status'] !== 'paid'));
        $ssl = $p['ssl_status'];
        if ($p['ssl_expires'] && portal_days($p['ssl_expires']) < 0) $ssl = 'expired';
        $sub = portal_date_status($p['hosting_renewal']);
        $svc = [
            'id' => $p['id'], 'name' => $p['name'], 'plan' => $p['plan'], 'domain' => $p['domain'],
            'site' => ['status' => $p['site_status'], 'preview' => $p['preview_url']],
            'domainInfo' => ['expires' => $p['domain_expires'], 'days' => portal_days($p['domain_expires']), 'status' => portal_date_status($p['domain_expires'])],
            'hosting' => ['expires' => $p['hosting_renewal'], 'days' => portal_days($p['hosting_renewal']), 'status' => $sub],
            'ssl' => ['status' => $ssl, 'expires' => $p['ssl_expires']],
            'subscription' => ['plan' => $p['plan'], 'price' => $p['hosting_amount'] !== null ? (float)$p['hosting_amount'] : null, 'currency' => $p['currency'],
                               'next' => $p['hosting_renewal'], 'days' => portal_days($p['hosting_renewal']), 'start' => $p['start_date']],
            'payment' => ['status' => $unpaid ? (in_array('overdue', array_column($unpaid, 'status'), true) ? 'overdue' : 'due') : 'paid',
                          'amount' => array_sum(array_column($unpaid, 'amount')), 'due' => $unpaid ? min(array_column($unpaid, 'due_on')) : null],
        ];
        // Actions demandées au client, de la plus urgente à la moins urgente
        foreach ($unpaid as $u) $actions[] = ['level' => $u['status'] === 'overdue' ? 'critical' : 'warning', 'code' => 'pay', 'service' => $p['id'], 'number' => $u['number'], 'amount' => $u['amount'], 'currency' => $u['currency'], 'date' => $u['due_on']];
        if (in_array($sub, ['soon', 'expired'], true)) $actions[] = ['level' => $sub === 'expired' ? 'critical' : 'warning', 'code' => 'renew', 'service' => $p['id'], 'date' => $p['hosting_renewal'], 'days' => portal_days($p['hosting_renewal'])];
        $dom = portal_date_status($p['domain_expires']);
        if (in_array($dom, ['soon', 'expired'], true) && $p['domain_expires'] !== $p['hosting_renewal']) $actions[] = ['level' => $dom === 'expired' ? 'critical' : 'warning', 'code' => 'domain', 'service' => $p['id'], 'date' => $p['domain_expires'], 'days' => portal_days($p['domain_expires'])];
        if ($p['site_status'] === 'preview' && $p['preview_url']) $actions[] = ['level' => 'info', 'code' => 'preview', 'service' => $p['id'], 'url' => $p['preview_url']];
        if ($ssl === 'expired') $actions[] = ['level' => 'critical', 'code' => 'ssl', 'service' => $p['id']];
        $services[] = $svc;
    }
    // Factures sans service rattaché (client à plusieurs services, ou aucun service) : action à part
    if (count($projects) !== 1) {
        foreach ($invoices as $u) if (!$u['project_id'] && $u['status'] !== 'paid') $actions[] = ['level' => $u['status'] === 'overdue' ? 'critical' : 'warning', 'code' => 'pay', 'service' => null, 'number' => $u['number'], 'amount' => $u['amount'], 'currency' => $u['currency'], 'date' => $u['due_on']];
    }
    $rank = ['critical' => 0, 'warning' => 1, 'info' => 2];
    usort($actions, fn($a, $b) => $rank[$a['level']] <=> $rank[$b['level']]);
    $notices = portal_all($pdo, "SELECT id, level, title, body, created_at FROM crm_notices
        WHERE (client_id = ? OR client_id IS NULL) AND (expires_on IS NULL OR expires_on >= CURDATE()) ORDER BY created_at DESC LIMIT 20", [$c['id']]);
    $requests = portal_all($pdo, "SELECT r.id, r.project_id, r.kind, r.status, r.created_at,
            (SELECT COUNT(*) FROM portal_messages m WHERE m.request_id = r.id AND m.sender = 'webina' AND m.read_by_client = 0) unread,
            (SELECT COUNT(*) FROM portal_messages m WHERE m.request_id = r.id) replies
        FROM portal_requests r WHERE r.client_id = ? ORDER BY r.created_at DESC LIMIT 10", [$c['id']]);
    foreach ($requests as &$q) { $q['unread'] = (int)$q['unread']; $q['replies'] = (int)$q['replies']; }
    unset($q);
    // Contrats et documents partagés par Webina
    $documents = portal_all($pdo, 'SELECT id, kind, title, filename, mime, size, created_at FROM crm_documents WHERE client_id = ? AND visible = 1 ORDER BY created_at DESC', [$c['id']]);
    $pricing = load_pricing();
    return [
        'client' => ['name' => $c['name'], 'company' => $c['company'], 'email' => $c['email'], 'phone' => $c['phone'], 'lang' => $c['lang']],
        'services' => $services, 'actions' => $actions, 'invoices' => $invoices, 'notices' => $notices, 'requests' => $requests, 'documents' => $documents,
        'pricing' => ['prices' => array_intersect_key($pricing['prices'], array_flip(['base', 'bookingCalendar', 'shop', 'renewalYearly'])),
                      'currencyByLang' => $pricing['currencyByLang'], 'tlPer' => $pricing['tlPer'], 'rounding' => $pricing['rounding']],
        'bank' => array_filter(['name' => $GLOBALS['CONFIG']['bank_name'] ?? null, 'iban' => $GLOBALS['CONFIG']['bank_iban'] ?? null, 'holder' => $GLOBALS['CONFIG']['bank_holder'] ?? null]),
        'whatsapp' => preg_replace('/\D/', '', $GLOBALS['CONFIG']['whatsapp_site'] ?? $GLOBALS['CONFIG']['whatsapp'] ?? '905399662557'),
    ];
}

/* ---------- Demande du client : renouvellement, changement de formule, assistance, validation ---------- */
function portal_request(PDO $pdo, array $c, string $kind, ?string $projectId, string $detail): array {
    global $CONFIG;
    if (!in_array($kind, PORTAL_REQUEST_KINDS, true)) fail('Demande invalide.', 422);
    $p = null;
    if ($projectId) {
        $p = portal_one($pdo, 'SELECT * FROM crm_projects WHERE id = ? AND client_id = ?', [$projectId, $c['id']]);
        if (!$p) fail('Service introuvable.', 404);
    }
    $labels = ['renew' => 'Renouvellement demandé', 'upgrade' => 'Changement de formule demandé', 'support' => 'Demande d\'assistance', 'approve' => 'Site validé par le client'];
    $id = uuid4();
    $pdo->prepare('INSERT INTO portal_requests (id, client_id, project_id, kind, detail) VALUES (?, ?, ?, ?, ?)')->execute([$id, $c['id'], $projectId, $kind, $detail !== '' ? $detail : null]);
    $text = $labels[$kind] . ($p ? ' — ' . $p['name'] . ($p['domain'] ? ' (' . $p['domain'] . ')' : '') : '') . ($detail !== '' ? "\n\n" . $detail : '');
    crm_log($pdo, $c['id'], $p['deal_id'] ?? null, 'portal', $text);
    crm_auto_task($pdo, 'portal:' . $id, $labels[$kind] . ' — ' . $c['name'], date('Y-m-d H:i:s'), $c['id'], $p['deal_id'] ?? null, $p['id'] ?? null, $kind === 'support' ? 'call' : 'todo');
    if ($kind === 'approve' && $p) $pdo->prepare("UPDATE crm_projects SET notes = CONCAT(COALESCE(notes, ''), ?) WHERE id = ?")->execute(["\n" . date('d/m/Y') . ' : site validé par le client depuis l\'espace client.', $p['id']]);
    if ($to = email_list($CONFIG['notify_email'] ?? '')) {
        send_html_mail($to[0], 'Espace client — ' . $labels[$kind] . ' : ' . $c['name'],
            mail_layout('<h2 style="margin:0 0 12px;font-size:20px">' . h($labels[$kind]) . '</h2><p style="margin:0 0 6px"><b>' . h($c['name']) . '</b> · ' . h((string)$c['email']) . ' · ' . h((string)$c['phone']) . '</p>'
                . ($p ? '<p style="margin:0 0 6px;color:#555">' . h($p['name']) . ($p['domain'] ? ' — ' . h($p['domain']) : '') . '</p>' : '')
                . ($detail !== '' ? '<p style="white-space:pre-wrap;background:#f4f6f2;padding:12px;border-radius:8px">' . h($detail) . '</p>' : '')),
            $c['email'] ?: null);
    }
    return ['ok' => true, 'id' => $id];
}

/* ---------- Fil de discussion d'une demande (client ⇄ Webina) ---------- */
const PORTAL_REQUEST_LABELS = ['renew' => 'Renouvellement', 'upgrade' => 'Changement de formule', 'support' => 'Assistance', 'approve' => 'Validation du site'];

// Messages d'un fil : le texte initial de la demande compte comme premier message du client
function portal_thread_messages(PDO $pdo, array $req): array {
    $msgs = [];
    if (($req['detail'] ?? '') !== '' && $req['detail'] !== null)
        $msgs[] = ['sender' => 'client', 'body' => (string)$req['detail'], 'created_at' => $req['created_at']];
    foreach (portal_all($pdo, 'SELECT sender, body, created_at FROM portal_messages WHERE request_id = ? ORDER BY created_at', [$req['id']]) as $m)
        $msgs[] = ['sender' => $m['sender'], 'body' => (string)$m['body'], 'created_at' => $m['created_at']];
    return $msgs;
}

// Portail : le client ouvre un fil ; les réponses de Webina passent alors en « lu »
function portal_thread(PDO $pdo, array $c, string $reqId): array {
    $req = portal_one($pdo, 'SELECT * FROM portal_requests WHERE id = ? AND client_id = ?', [$reqId, $c['id']]);
    if (!$req) fail('Demande introuvable.', 404);
    $pdo->prepare("UPDATE portal_messages SET read_by_client = 1 WHERE request_id = ? AND sender = 'webina'")->execute([$reqId]);
    return ['id' => $req['id'], 'kind' => $req['kind'], 'status' => $req['status'], 'created_at' => $req['created_at'],
            'messages' => portal_thread_messages($pdo, $req)];
}

// Portail : le client répond dans un fil encore ouvert
function portal_reply(PDO $pdo, array $c, string $reqId, string $body): array {
    global $CONFIG;
    if ($body === '') fail('Message vide.', 422);
    $req = portal_one($pdo, 'SELECT * FROM portal_requests WHERE id = ? AND client_id = ?', [$reqId, $c['id']]);
    if (!$req) fail('Demande introuvable.', 404);
    if ($req['status'] !== 'open') fail('Cette demande est déjà clôturée.', 409);
    $pdo->prepare("INSERT INTO portal_messages (id, request_id, client_id, sender, body, read_by_client) VALUES (?, ?, ?, 'client', ?, 1)")
        ->execute([uuid4(), $reqId, $c['id'], $body]);
    $label = PORTAL_REQUEST_LABELS[$req['kind']] ?? $req['kind'];
    crm_log($pdo, $c['id'], null, 'portal', $label . ' — réponse du client :' . "\n\n" . $body);
    // Rouvre la tâche de suivi pour que Webina reprenne l'échange
    crm_auto_task($pdo, 'portal:' . $reqId, $label . ' — ' . $c['name'], date('Y-m-d H:i:s'), $c['id'], null, $req['project_id'], 'call');
    $pdo->prepare("UPDATE crm_tasks SET done_at = NULL WHERE auto_key = ?")->execute(['portal:' . $reqId]);
    if ($to = email_list($CONFIG['notify_email'] ?? '')) {
        send_html_mail($to[0], 'Espace client — réponse : ' . $c['name'],
            mail_layout('<h2 style="margin:0 0 12px;font-size:20px">' . h($label) . ' — réponse du client</h2>'
                . '<p style="margin:0 0 6px"><b>' . h($c['name']) . '</b> · ' . h((string)$c['email']) . '</p>'
                . '<p style="white-space:pre-wrap;background:#f4f6f2;padding:12px;border-radius:8px">' . h($body) . '</p>'),
            $c['email'] ?: null);
    }
    return ['ok' => true];
}

// Admin : Webina répond au client dans un fil ouvert (le message reste non lu jusqu'à sa prochaine visite)
function portal_admin_reply(PDO $pdo, array $req, array $c, string $body): array {
    if ($body === '') fail('Message vide.', 422);
    if ($req['status'] !== 'open') fail('Cette demande est clôturée.', 409);
    $pdo->prepare("INSERT INTO portal_messages (id, request_id, client_id, sender, body, read_by_client) VALUES (?, ?, ?, 'webina', ?, 0)")
        ->execute([uuid4(), $req['id'], $c['id'], $body]);
    $label = PORTAL_REQUEST_LABELS[$req['kind']] ?? $req['kind'];
    crm_log($pdo, $c['id'], null, 'portal', $label . ' — réponse de Webina au client :' . "\n\n" . $body);
    portal_mail_reply($c, $c['lang'] ?: 'tr', $label, $body);
    return ['ok' => true];
}

// Admin : clôture la demande (« Traité ») + ferme la tâche de suivi + prévient le client
function portal_admin_close(PDO $pdo, array $req, array $c, string $body = ''): array {
    $pdo->prepare("UPDATE portal_requests SET status = 'done', done_at = NOW() WHERE id = ?")->execute([$req['id']]);
    if ($body !== '')
        $pdo->prepare("INSERT INTO portal_messages (id, request_id, client_id, sender, body, read_by_client) VALUES (?, ?, ?, 'webina', ?, 0)")
            ->execute([uuid4(), $req['id'], $c['id'], $body]);
    $pdo->prepare("UPDATE crm_tasks SET done_at = NOW() WHERE auto_key = ? AND done_at IS NULL")->execute(['portal:' . $req['id']]);
    $label = PORTAL_REQUEST_LABELS[$req['kind']] ?? $req['kind'];
    crm_log($pdo, $c['id'], null, 'portal', $label . ' — demande clôturée (traitée)' . ($body !== '' ? " :\n\n" . $body : ''));
    portal_mail_resolved($c, $c['lang'] ?: 'tr', $label, $body);
    return ['ok' => true];
}

// E-mail au client : Webina a répondu
function portal_mail_reply(array $c, string $lang, string $label, string $body): bool {
    if (!filter_var((string)$c['email'], FILTER_VALIDATE_EMAIL)) return false;
    $L = [
        'tr' => ['Webina size yanıt verdi', 'Talebinizle ilgili bir yanıt aldınız:', 'Yanıtlamak için müşteri panelinize girin:'],
        'en' => ['Webina replied to you', 'You have a reply about your request:', 'Open your client area to reply:'],
        'fr' => ['Webina vous a répondu', 'Vous avez une réponse à votre demande :', 'Ouvrez votre espace client pour répondre :'],
        'ar' => ['ردّت Webina عليك', 'لديك ردّ على طلبك:', 'افتح منطقة العملاء للرد:'],
    ][$lang] ?? null;
    if (!$L) $L = ['Webina size yanıt verdi', 'Talebinizle ilgili bir yanıt aldınız:', 'Müşteri panelinize girin:'];
    $url = portal_url();
    $html = '<h2 style="margin:0 0 12px;font-size:20px">' . h($L[0]) . '</h2>'
        . '<p style="margin:0 0 6px;color:#555">' . h($L[1]) . ' <b>' . h($label) . '</b></p>'
        . '<p style="white-space:pre-wrap;background:#f4f6f2;padding:12px;border-radius:8px;margin:0 0 16px">' . h($body) . '</p>'
        . '<p style="margin:0"><a href="' . h($url) . '" style="color:#1f6f2a">' . h($L[2]) . ' ' . h(preg_replace('#^https?://#', '', $url)) . '</a></p>';
    return send_html_mail((string)$c['email'], $L[0], mail_layout($html, $lang === 'ar'));
}

// E-mail au client : demande traitée / clôturée
function portal_mail_resolved(array $c, string $lang, string $label, string $body = ''): bool {
    if (!filter_var((string)$c['email'], FILTER_VALIDATE_EMAIL)) return false;
    $L = [
        'tr' => ['Talebiniz tamamlandı', 'Aşağıdaki talebiniz çözüldü ve kapatıldı:', 'Başka bir sorunuz olursa panelden yazabilirsiniz.'],
        'en' => ['Your request is resolved', 'The following request has been resolved and closed:', 'If you have another question, write to us from your client area.'],
        'fr' => ['Votre demande est traitée', 'La demande suivante a été résolue et clôturée :', 'Pour toute autre question, écrivez-nous depuis votre espace client.'],
        'ar' => ['تمّت معالجة طلبك', 'تمّ حلّ الطلب التالي وإغلاقه:', 'لأي سؤال آخر راسلنا من منطقة العملاء.'],
    ][$lang] ?? null;
    if (!$L) $L = ['Talebiniz tamamlandı', 'Talebiniz çözüldü ve kapatıldı:', 'Panelden yazabilirsiniz.'];
    $html = '<h2 style="margin:0 0 12px;font-size:20px">' . h($L[0]) . '</h2>'
        . '<p style="margin:0 0 6px;color:#555">' . h($L[1]) . ' <b>' . h($label) . '</b></p>'
        . ($body !== '' ? '<p style="white-space:pre-wrap;background:#f4f6f2;padding:12px;border-radius:8px;margin:0 0 16px">' . h($body) . '</p>' : '')
        . '<p style="margin:0;color:#777;font-size:13px">' . h($L[2]) . '</p>';
    return send_html_mail((string)$c['email'], $L[0], mail_layout($html, $lang === 'ar'));
}

/* ---------- E-mail du code de connexion ---------- */
// Adresse sans espace client ouvert : on prévient quand même la personne (même réponse côté écran,
// donc on ne révèle pas quelles adresses sont clientes)
function portal_send_noaccess(string $email, string $lang): bool {
    $L = [
        'tr' => ['Webina müşteri paneli', 'Bu e-posta adresi için açılmış bir müşteri paneli bulunmuyor.', 'Webina müşterisiyseniz, kayıtlı e-posta adresinizle deneyin veya bize yazın, erişiminizi hemen açalım.'],
        'en' => ['Webina client area', 'There is no client area open for this email address.', 'If you are a Webina client, try the email address we have on file or contact us and we will open your access.'],
        'fr' => ['Espace client Webina', 'Aucun espace client n’est ouvert pour cette adresse e-mail.', 'Si vous êtes client Webina, essayez l’adresse que nous avons enregistrée ou écrivez-nous : nous ouvrirons votre accès.'],
        'ar' => ['منطقة عملاء Webina', 'لا توجد منطقة عملاء مفتوحة لهذا البريد الإلكتروني.', 'إذا كنت من عملاء Webina فجرّب البريد المسجّل لدينا أو راسلنا لنفتح لك الوصول.'],
    ][$lang] ?? null;
    if (!$L) return false;
    $html = '<p style="margin:0 0 12px;font-weight:600">' . h($L[1]) . '</p>'
        . '<p style="margin:0 0 16px;color:#555">' . h($L[2]) . '</p>'
        . '<p style="margin:0"><a href="https://wa.me/905399662557" style="color:#1f6f2a">WhatsApp</a> · <a href="https://webina.com.tr/iletisim" style="color:#1f6f2a">webina.com.tr/iletisim</a></p>';
    return send_html_mail($email, $L[0], mail_layout($html, $lang === 'ar'));
}

function portal_send_code(array $c, string $code, string $lang): bool {
    $L = [
        'tr' => ['Webina müşteri paneli giriş kodunuz', 'Giriş kodunuz', PORTAL_CODE_TTL . ' dakika geçerlidir. Bu isteği siz yapmadıysanız bu e-postayı yok sayın.'],
        'en' => ['Your Webina client area login code', 'Your login code', 'Valid for ' . PORTAL_CODE_TTL . ' minutes. If you did not ask for it, ignore this email.'],
        'fr' => ['Votre code de connexion à l’espace client Webina', 'Votre code de connexion', 'Valable ' . PORTAL_CODE_TTL . ' minutes. Si vous n’êtes pas à l’origine de la demande, ignorez cet e-mail.'],
        'ar' => ['رمز الدخول إلى منطقة عملاء Webina', 'رمز الدخول', 'صالح لمدة ' . PORTAL_CODE_TTL . ' دقيقة. إذا لم تطلبه فتجاهل هذه الرسالة.'],
    ][$lang] ?? null;
    if (!$L) $L = ['Webina müşteri paneli giriş kodunuz', 'Giriş kodunuz', PORTAL_CODE_TTL . ' dakika geçerlidir.'];
    $html = '<p style="margin:0 0 8px;color:#555">' . h($L[1]) . '</p>'
        . '<p style="margin:0 0 16px;font-size:34px;font-weight:800;letter-spacing:8px;font-family:monospace">' . h($code) . '</p>'
        . '<p style="margin:0;color:#777;font-size:13px">' . h($L[2]) . '</p>';
    return send_html_mail((string)$c['email'], $L[0], mail_layout($html, $lang === 'ar'));
}

/* ---------- Mail de bienvenue : « Merci d'avoir choisi Webina » + lien vers l'espace client ---------- */
// Texte proposé dans la langue du client ; Webina peut le modifier avant l'envoi
function portal_welcome_draft(array $c, string $lang): array {
    $first = trim(explode(' ', trim((string)$c['name']))[0] ?? '');
    $email = (string)$c['email'];
    $T = [
        'tr' => ['Webina\'ya hoş geldiniz — özel müşteri alanınız',
            "Merhaba $first,\n\nWebina'yı seçtiğiniz için teşekkür ederiz. Sizin için özel bir müşteri alanı hazırladık.\n\nBu alanda şunları bulabilirsiniz:\n• Sözleşmeniz ve belgeleriniz\n• Sitenizin, alan adınızın ve hostinginizin durumu\n• Faturalarınız ve ödeme bilgileri\n• Destek talebi gönderme ve yanıtlarımızı takip etme\n\nAşağıdaki butona tıklayarak hemen giriş yapabilirsiniz. Daha sonra my.webina.com.tr adresinden e-posta adresinizle ($email) giriş yapabilirsiniz: size tek kullanımlık bir kod göndeririz, şifre gerekmez.\n\nHerhangi bir sorunuz olursa bize yazmaktan çekinmeyin.\n\nSevgiler,\nWebina Ekibi",
            'Özel alanıma gir', 'Bu bağlantı ' . PORTAL_LINK_TTL . ' gün geçerlidir ve tek kullanımlıktır.'],
        'en' => ['Welcome to Webina — your private client area',
            "Hello $first,\n\nThank you for choosing Webina. We have set up a private client area for you.\n\nIn it you will find:\n• Your contract and documents\n• The status of your website, domain and hosting\n• Your invoices and payment details\n• A way to send us support requests and follow our replies\n\nClick the button below to sign in right away. Later, you can sign in at my.webina.com.tr with your email address ($email): we send you a one-time code, no password needed.\n\nIf you have any question, just write to us.\n\nBest regards,\nThe Webina team",
            'Open my client area', 'This link is valid for ' . PORTAL_LINK_TTL . ' days and can be used once.'],
        'fr' => ['Bienvenue chez Webina — votre espace client privé',
            "Bonjour $first,\n\nMerci d'avoir choisi Webina. Nous avons préparé pour vous un espace client privé.\n\nVous y trouverez :\n• Votre contrat et vos documents\n• L'état de votre site, de votre nom de domaine et de votre hébergement\n• Vos factures et les informations de paiement\n• L'envoi de demandes d'assistance et le suivi de nos réponses\n\nCliquez sur le bouton ci-dessous pour vous connecter tout de suite. Ensuite, connectez-vous sur my.webina.com.tr avec votre adresse e-mail ($email) : nous vous envoyons un code à usage unique, sans mot de passe.\n\nPour toute question, n'hésitez pas à nous écrire.\n\nBien cordialement,\nL'équipe Webina",
            'Accéder à mon espace', 'Ce lien est valable ' . PORTAL_LINK_TTL . ' jours et ne sert qu’une fois.'],
        'ar' => ['مرحباً بك في Webina — منطقة العملاء الخاصة بك',
            "مرحباً $first،\n\nشكراً لاختيارك Webina. لقد جهّزنا لك منطقة عملاء خاصة.\n\nستجد فيها:\n• عقدك ومستنداتك\n• حالة موقعك ونطاقك والاستضافة\n• فواتيرك ومعلومات الدفع\n• إرسال طلبات الدعم ومتابعة ردودنا\n\nاضغط على الزر أدناه للدخول فوراً. لاحقاً يمكنك الدخول عبر my.webina.com.tr ببريدك الإلكتروني ($email): نرسل لك رمزاً لمرة واحدة دون كلمة مرور.\n\nلأي سؤال لا تتردد في مراسلتنا.\n\nمع أطيب التحيات،\nفريق Webina",
            'الدخول إلى منطقتي', 'هذا الرابط صالح لمدة ' . PORTAL_LINK_TTL . ' أيام ولمرة واحدة.'],
    ];
    $L = $T[$lang] ?? $T['tr'];
    return ['lang' => isset($T[$lang]) ? $lang : 'tr', 'to' => $email, 'subject' => $L[0], 'body' => $L[1], 'button' => $L[2], 'note' => $L[3]];
}

function portal_send_welcome(PDO $pdo, array $c, string $lang, string $subject, string $body): bool {
    $d = portal_welcome_draft($c, $lang);
    $url = portal_url() . '/?t=' . portal_token($pdo, $c['id'], 'link');
    $html = '<div style="font-size:15px;line-height:1.6;color:#2b302c;white-space:pre-wrap">' . h($body) . '</div>'
        . '<p style="margin:24px 0 8px"><a href="' . h($url) . '" style="display:inline-block;background:#23772a;color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:99px">' . h($d['button']) . '</a></p>'
        . '<p style="margin:0;color:#888;font-size:12px">' . h($d['note']) . '<br><a href="' . h(portal_url()) . '" style="color:#1f6f2a">' . h(preg_replace('#^https?://#', '', portal_url())) . '</a></p>';
    return send_html_mail((string)$c['email'], $subject, mail_layout($html, $d['lang'] === 'ar'), email_list($GLOBALS['CONFIG']['notify_email'] ?? '')[0] ?? null);
}
