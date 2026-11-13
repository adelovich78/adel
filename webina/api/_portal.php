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
    $requests = portal_all($pdo, 'SELECT id, project_id, kind, status, created_at FROM portal_requests WHERE client_id = ? ORDER BY created_at DESC LIMIT 10', [$c['id']]);
    $pricing = load_pricing();
    return [
        'client' => ['name' => $c['name'], 'company' => $c['company'], 'email' => $c['email'], 'phone' => $c['phone'], 'lang' => $c['lang']],
        'services' => $services, 'actions' => $actions, 'invoices' => $invoices, 'notices' => $notices, 'requests' => $requests,
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
