<?php
// Calcul serveur du devis (mêmes règles que src/lib/pricing.ts) + libellés des e-mails

function load_pricing(): array {
    $file = dirname(__DIR__) . '/pricing.json';
    $p = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    if (!is_array($p)) fail('Tarifs indisponibles.', 500);
    return $p;
}

// $promo : code déjà validé et consommé (vide = pas de réduction)
// $rule  : sa réduction, ['kind' => 'percent'|'amount', 'value' => int|null] (voir promo_rule)
function compute_quote(array $p, string $lang, array $a, string $promo = '', ?array $rule = null): array {
    $currency = $p['currencyByLang'][$lang] ?? 'USD';
    $per  = (float)($p['tlPer'][$currency] ?? 1);
    $step = (float)($p['rounding'][$currency] ?? 1);
    $t = [];
    foreach ($p['prices'] as $k => $v) $t[$k] = (int)(round(((float)$v / $per) / $step) * $step);

    $pages = is_array($a['pages'] ?? null) ? $a['pages'] : [];
    $langs = is_array($a['langs'] ?? null) ? $a['langs'] : [];
    $inclLangs = (int)($p['includedLanguages'] ?? 2);
    $lines = [['key' => 'base', 'amount' => $t['base'], 'n' => (int)$p['includedPages'], 'l' => $inclLangs]];

    // Options de pages + pages sur mesure (le pack inclut déjà les 4 pages de base)
    if (in_array('gallery', $pages, true)) $lines[] = ['key' => 'gallery', 'amount' => $t['gallery'] ?? 0];
    if (in_array('blog', $pages, true)) $lines[] = ['key' => 'blog', 'amount' => $t['blog'] ?? 0];
    if (in_array('shop', $pages, true)) $lines[] = ['key' => 'shop', 'amount' => $t['shop']];
    $extraPages = max(0, min(20, (int)($a['customPages'] ?? 0)));
    if ($extraPages) $lines[] = ['key' => 'extraPages', 'n' => $extraPages, 'amount' => $extraPages * $t['extraPage']];
    $pageCount = (int)$p['includedPages'] + count(array_intersect($pages, ['gallery', 'blog'])) + $extraPages;
    $extraLangs = max(0, count($langs) - $inclLangs);
    if ($extraLangs) $lines[] = ['key' => 'extraLangs', 'n' => $extraLangs, 'amount' => $extraLangs * $t['extraLanguage']];
    $booking = is_array($a['booking'] ?? null) ? $a['booking'] : [(string)($a['booking'] ?? '')];
    if (in_array('calendar', $booking, true)) $lines[] = ['key' => 'booking', 'amount' => $t['bookingCalendar']];
    if (($a['content'] ?? '') === 'partial') $lines[] = ['key' => 'content', 'amount' => $t['contentPartial']];
    if (($a['content'] ?? '') === 'need') $lines[] = ['key' => 'content', 'amount' => $t['contentFull']];
    if (($a['photos'] ?? '') === 'pro') $lines[] = ['key' => 'photos', 'amount' => $t['photographer'] ?? 0];
    if (($a['logo'] ?? '') !== 'yes') $lines[] = ['key' => 'logo', 'amount' => 0, 'free' => true, 'was' => $t['logoValue']];
    $lines[] = ['key' => 'domain', 'amount' => 0, 'free' => true, 'was' => $t['renewalYearly']];

    // Code de réduction (usage unique, vérifié et consommé par contact.php)
    if ($promo !== '') {
        $subtotal = array_sum(array_column($lines, 'amount'));
        $kind = $rule['kind'] ?? 'amount';
        $value = $rule['value'] ?? null;
        if ($kind === 'percent' && $value) {
            $off = (int)(round($subtotal * min(100, $value) / 100 / $step) * $step);
            $label = $promo . ' · %' . $value;
        } else {
            // Montant saisi en TL, converti comme les autres prix ; à défaut, la réduction par défaut des tarifs
            $off = $value ? (int)(round(($value / $per) / $step) * $step) : (int)($t['promoDiscount'] ?? 0);
            $label = $promo;
        }
        $off = min($off, $subtotal);
        if ($off > 0) $lines[] = ['key' => 'promo', 'amount' => -$off, 'c' => $label];
    }

    $total = array_sum(array_column($lines, 'amount'));
    $pct = (int)($p['depositPercent'] ?? 20);
    $deposit = (int)round($total * $pct / 100);
    return compact('currency', 'lines', 'total', 'deposit', 'pct', 'pageCount', 'promo') + ['balance' => $total - $deposit, 'renewal' => $t['renewalYearly']];
}

function money(int $n, string $currency): string {
    $sign = $n < 0 ? '−' : '';
    $n = abs($n);
    $s = number_format($n, 0, ',', '.');
    return $sign . (['TRY' => "$s TL", 'USD' => '$' . number_format($n, 0, '.', ','), 'EUR' => number_format($n, 0, ',', ' ') . ' €'][$currency] ?? "$s $currency");
}

// Libellés de l'e-mail client (et FR pour l'e-mail interne)
function mail_texts(string $lang): array {
    $T = [
        'tr' => ['noDeposit' => 'Avans yok — %100 ödeme proje tesliminde ve onayınızla', 'subject' => 'Web sitesi teklif özetiniz — Webina', 'hello' => 'Merhaba {n},', 'intro' => 'Talebiniz için teşekkür ederiz. Seçimlerinize göre hesaplanan teklif özetiniz aşağıdadır. Ekibimiz en kısa sürede sizinle iletişime geçecektir.', 'title' => 'Teklif özeti', 'total' => 'Toplam (tek seferlik)', 'free' => 'Ücretsiz', 'deposit' => 'Sipariş onayında %{p} avans', 'balance' => 'Kalan %{p} — teslim ve onayınızla', 'renewal' => '2. yıldan itibaren alan adı + hosting yenilemesi: {x} / yıl (sizin tarafınızdan ödenir).', 'note' => 'Bu fiyat seçimlerinize göre otomatik hesaplanmıştır; kesin teklif yazılı olarak onaylanır.', 'cta' => 'WhatsApp ile yazın',
            'lines' => ['base' => 'Standart paket ({n} sayfa, {l} dil, iletişim, tasarım + SSL + SEO)', 'gallery' => 'Galeri sayfası', 'blog' => 'Blog sayfası', 'extraPages' => 'Ek özel sayfa × {n}', 'extraLangs' => 'Ek dil × {n}', 'booking' => 'Online randevu takvimi + yönetim', 'content' => 'Metin hazırlama', 'photos' => 'Profesyonel fotoğrafçı (1 gün, İstanbul)', 'shop' => 'Online mağaza', 'logo' => 'Logo tasarımı', 'domain' => 'Alan adı + hosting (1. yıl)', 'promo' => 'İndirim kodu ({c})']],
        'en' => ['noDeposit' => 'No deposit — 100% paid on delivery and your approval', 'subject' => 'Your website quote summary — Webina', 'hello' => 'Hello {n},', 'intro' => 'Thank you for your request. Below is the quote summary calculated from your choices. Our team will contact you shortly.', 'title' => 'Quote summary', 'total' => 'Total (one-time)', 'free' => 'Free', 'deposit' => '{p}% deposit on order', 'balance' => 'Remaining {p}% — on delivery and your approval', 'renewal' => 'From year 2, domain + hosting renewal: {x} / year (paid by you).', 'note' => 'This price is calculated automatically from your choices; the final quote is confirmed in writing.', 'cta' => 'Message us on WhatsApp',
            'lines' => ['base' => 'Standard pack ({n} pages, {l} languages, contact, design + SSL + SEO)', 'gallery' => 'Gallery page', 'blog' => 'Blog page', 'extraPages' => 'Extra custom page × {n}', 'extraLangs' => 'Extra language × {n}', 'booking' => 'Booking calendar + management', 'content' => 'Copywriting', 'photos' => 'Professional photographer (1 day, Istanbul)', 'shop' => 'Online shop', 'logo' => 'Logo design', 'domain' => 'Domain + hosting (1st year)', 'promo' => 'Discount code ({c})']],
        'fr' => ['noDeposit' => 'Aucun acompte — 100 % payé à la livraison, après votre validation', 'subject' => 'Récapitulatif de votre devis site web — Webina', 'hello' => 'Bonjour {n},', 'intro' => 'Merci pour votre demande. Voici le récapitulatif du devis calculé selon vos choix. Notre équipe revient vers vous très rapidement.', 'title' => 'Récapitulatif du devis', 'total' => 'Total (paiement unique)', 'free' => 'Offert', 'deposit' => 'Acompte de {p} % à la commande', 'balance' => 'Solde de {p} % — à la livraison et après votre validation', 'renewal' => 'À partir de la 2e année, renouvellement domaine + hébergement : {x} / an (à votre charge).', 'note' => 'Ce prix est calculé automatiquement selon vos choix ; le devis définitif est confirmé par écrit.', 'cta' => 'Écrire sur WhatsApp',
            'lines' => ['base' => 'Pack standard ({n} pages, {l} langues, contact, design + SSL + SEO)', 'gallery' => 'Page galerie', 'blog' => 'Page blog', 'extraPages' => 'Page sur mesure × {n}', 'extraLangs' => 'Langue supplémentaire × {n}', 'booking' => 'Calendrier de RDV + gestion', 'content' => 'Rédaction des textes', 'photos' => 'Photographe professionnel (1 journée, Istanbul)', 'shop' => 'Boutique en ligne', 'logo' => 'Création du logo', 'domain' => 'Domaine + hébergement (1re année)', 'promo' => 'Code de réduction ({c})']],
        'ar' => ['noDeposit' => 'بدون دفعة مقدّمة — 100% عند التسليم وموافقتكم', 'subject' => 'ملخّص عرض سعر موقعكم — Webina', 'hello' => 'مرحباً {n}،', 'intro' => 'شكراً لطلبكم. فيما يلي ملخّص العرض المحسوب حسب اختياراتكم. سيتواصل معكم فريقنا قريباً.', 'title' => 'ملخّص العرض', 'total' => 'الإجمالي (دفعة واحدة)', 'free' => 'مجاناً', 'deposit' => 'دفعة مقدّمة {p}% عند الطلب', 'balance' => 'المتبقّي {p}% — عند التسليم وموافقتكم', 'renewal' => 'اعتباراً من السنة الثانية، تجديد النطاق + الاستضافة: {x} / سنة (على نفقتكم).', 'note' => 'تم حساب هذا السعر تلقائياً حسب اختياراتكم، ويُؤكَّد العرض النهائي كتابياً.', 'cta' => 'راسلونا عبر واتساب',
            'lines' => ['base' => 'الباقة الأساسية ({n} صفحات، {l} لغات، تواصل، تصميم + SSL + SEO)', 'gallery' => 'صفحة المعرض', 'blog' => 'صفحة المدونة', 'extraPages' => 'صفحة مخصّصة إضافية × {n}', 'extraLangs' => 'لغة إضافية × {n}', 'booking' => 'تقويم مواعيد + إدارة', 'content' => 'كتابة النصوص', 'photos' => 'مصوّر محترف (يوم واحد، إسطنبول)', 'shop' => 'متجر إلكتروني', 'logo' => 'تصميم الشعار', 'domain' => 'النطاق + الاستضافة (السنة الأولى)', 'promo' => 'رمز الخصم ({c})']],
    ];
    return $T[$lang] ?? $T['tr'];
}

// Liste d'adresses (séparées par des virgules) -> tableau d'e-mails valides
function email_list(string $s): array {
    return array_values(array_filter(array_map('trim', explode(',', $s)), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Tableau HTML du devis (commun aux deux e-mails)
function quote_table_html(array $q, array $L): string {
    $rows = '';
    foreach ($q['lines'] as $l) {
        $label = str_replace(['{n}', '{l}', '{c}'], [(string)($l['n'] ?? ''), (string)($l['l'] ?? ''), (string)($l['c'] ?? '')], $L['lines'][$l['key']] ?? $l['key']);
        $val = !empty($l['free'])
            ? '<span style="color:#999;text-decoration:line-through;font-size:12px">' . h(money((int)$l['was'], $q['currency'])) . '</span>&nbsp; <b style="color:#2E8B2E">' . h($L['free']) . '</b>'
            : '<b>' . h(money((int)$l['amount'], $q['currency'])) . '</b>';
        $rows .= '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#444">' . h($label) . '</td><td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;white-space:nowrap">' . $val . '</td></tr>';
    }
    $rest = 100 - $q['pct'];
    $head = '<table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">' . $rows . '</table>'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px"><tr><td style="font-size:14px;color:#666">' . h($L['total']) . '</td><td style="text-align:right;font-size:26px;font-weight:800;color:#121812">' . h(money($q['total'], $q['currency'])) . '</td></tr></table>';
    $renew = '<p style="font-size:12px;color:#666;margin:14px 0 0">' . h(str_replace('{x}', money($q['renewal'], $q['currency']), $L['renewal'])) . '</p>';
    // Sans acompte : une seule ligne « 100 % à la livraison »
    if ($q['pct'] <= 0) {
        return $head . '<p style="margin:12px 0 0;background:#eaf8e1;padding:12px 14px;border-radius:8px;font-size:13px;font-weight:700">✓ ' . h($L['noDeposit']) . '</p>' . $renew;
    }
    return $head
        . '<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;font-size:13px">'
        . '<tr><td style="background:#eaf8e1;padding:10px 12px;border-radius:8px 0 0 8px">' . h(str_replace('{p}', (string)$q['pct'], $L['deposit'])) . '</td><td style="background:#eaf8e1;padding:10px 12px;text-align:right;font-weight:700;border-radius:0 8px 8px 0;white-space:nowrap">' . h(money($q['deposit'], $q['currency'])) . '</td></tr>'
        . '<tr><td colspan="2" style="height:6px"></td></tr>'
        . '<tr><td style="background:#f2f4f1;padding:10px 12px;border-radius:8px 0 0 8px">' . h(str_replace('{p}', (string)$rest, $L['balance'])) . '</td><td style="background:#f2f4f1;padding:10px 12px;text-align:right;font-weight:700;border-radius:0 8px 8px 0;white-space:nowrap">' . h(money($q['balance'], $q['currency'])) . '</td></tr></table>'
        . '<p style="font-size:12px;color:#666;margin:14px 0 0">' . h(str_replace('{x}', money($q['renewal'], $q['currency']), $L['renewal'])) . '</p>';
}

// Gabarit e-mail Webina
function mail_layout(string $inner, bool $rtl = false, bool $quote = false): string {
    global $CONFIG;
    $dir = $rtl ? 'rtl' : 'ltr';
    return '<!doctype html><html><body style="margin:0;background:#f4f6f2;font-family:Arial,Helvetica,sans-serif;color:#121812" dir="' . $dir . '">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f2;padding:24px 12px"><tr><td align="center">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden">'
        . '<tr><td style="background:#121812;padding:22px 28px;color:#fff;font-size:22px;font-weight:800;letter-spacing:1px">WEB<span style="color:#7ED957">INA</span><div style="font-size:10px;letter-spacing:3px;color:#a9b4a6;font-weight:400;margin-top:2px">DİJİTAL KREASYON AJANSI</div></td></tr>'
        . '<tr><td style="padding:28px">' . $inner . '</td></tr>'
        . '<tr><td style="background:#121812;padding:18px 28px;color:#c9d3c6;font-size:12px">'
        . '<a href="https://wa.me/' . h(preg_replace('/\D/', '', $quote ? ($CONFIG['whatsapp'] ?? '') : ($CONFIG['whatsapp_site'] ?? $CONFIG['whatsapp'] ?? ''))) . '" style="color:#7ED957;text-decoration:none">WhatsApp</a> &nbsp;·&nbsp; ' . h(email_list($CONFIG['notify_email'] ?? '')[0] ?? '') . ' &nbsp;·&nbsp; ' . h($CONFIG['website'] ?? '') . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

// Envoi d'un e-mail HTML : SMTP authentifié si configuré, sinon mail() de PHP
// $bcc : destinataires en copie cachée (aucun ne voit les autres)
function send_html_mail(string $to, string $subject, string $html, ?string $replyTo = null, ?array &$log = null, array $extra = [], array $bcc = []): bool {
    global $CONFIG;
    $from = $CONFIG['mail_from'] ?? 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $name = '=?UTF-8?B?' . base64_encode($CONFIG['mail_from_name'] ?? 'Webina') . '?=';
    $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    if (!empty($CONFIG['smtp_host'])) {
        $ok = smtp_send($to, $subj, $html, "$name <$from>", $from, $replyTo, $log, $extra, $bcc);
        if (!$ok && (int)($CONFIG['smtp_port'] ?? 465) !== 587) {
            // Secours : TLS sur 587 si SSL 465 est bloqué
            $log[] = '— nouvel essai en TLS 587 —';
            $saved = [$CONFIG['smtp_port'], $CONFIG['smtp_secure']];
            [$CONFIG['smtp_port'], $CONFIG['smtp_secure']] = [587, 'tls'];
            $ok = smtp_send($to, $subj, $html, "$name <$from>", $from, $replyTo, $log, $extra, $bcc);
            [$CONFIG['smtp_port'], $CONFIG['smtp_secure']] = $saved;
        }
        if (!$ok) error_log('[webina] SMTP: ' . implode(' | ', array_slice($log ?? [], -3)));
        return $ok;
    }
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: $name <$from>";
    if ($replyTo) $headers .= "\r\nReply-To: $replyTo";
    foreach ($extra as $hd) $headers .= "\r\n" . $hd;
    if ($bcc) $headers .= "\r\nBcc: " . implode(', ', $bcc);
    // -f : expéditeur d'enveloppe (meilleure délivrabilité sur cPanel)
    $ok = @mail($to, $subj, $html, $headers, '-f' . $from);
    $log[] = 'mail() => ' . ($ok ? 'accepté' : 'refusé');
    if (!$ok) error_log('[webina] mail() a refusé l\'envoi vers ' . $to);
    return $ok;
}

// Client SMTP minimal (AUTH LOGIN, SSL 465 ou STARTTLS 587)
function smtp_send(string $to, string $subj, string $html, string $fromHeader, string $fromAddr, ?string $replyTo, ?array &$log, array $extra = [], array $bcc = []): bool {
    global $CONFIG;
    $log = $log ?? [];
    $host = $CONFIG['smtp_host']; $port = (int)($CONFIG['smtp_port'] ?? 465);
    $secure = strtolower($CONFIG['smtp_secure'] ?? 'ssl');
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $log[] = "Connexion impossible à $host:$port — $errstr"; return false; }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp, &$log) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) { $data .= $line; if (isset($line[3]) && $line[3] === ' ') break; }
        $log[] = '< ' . trim($data);
        return (int)substr($data, 0, 3);
    };
    $cmd = function (string $c, array $okCodes, bool $hide = false) use ($fp, $read, &$log) {
        fwrite($fp, $c . "\r\n");
        $log[] = '> ' . ($hide ? '••••' : (strlen($c) > 200 ? '[contenu du message]' : $c));
        return in_array($read(), $okCodes, true);
    };

    $ehlo = 'EHLO ' . (preg_replace('/[^a-z0-9.\-]/i', '', explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]) ?: 'localhost');
    if ($read() !== 220 || !$cmd($ehlo, [250])) { fclose($fp); return false; }
    if ($secure === 'tls') {
        if (!$cmd('STARTTLS', [220]) || !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) || !$cmd($ehlo, [250])) { fclose($fp); return false; }
    }
    $ok = $cmd('AUTH LOGIN', [334])
        && $cmd(base64_encode($CONFIG['smtp_user'] ?? ''), [334], true)
        && $cmd(base64_encode($CONFIG['smtp_pass'] ?? ''), [235], true)
        && $cmd("MAIL FROM:<$fromAddr>", [250])
        && $cmd("RCPT TO:<$to>", [250, 251]);
    // Copie cachée : une enveloppe RCPT par destinataire, aucun en-tête « Bcc » dans le message
    if ($ok) foreach ($bcc as $b) { if (!$cmd("RCPT TO:<$b>", [250, 251])) $log[] = "Destinataire refusé : $b"; }
    $ok = $ok && $cmd('DATA', [354]);
    if (!$ok) { fclose($fp); return false; }

    $headers = [
        'Date: ' . date('r'),
        "From: $fromHeader",
        "To: <$to>",
        "Subject: $subj",
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . explode('@', $fromAddr)[1] . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    if ($replyTo) $headers[] = "Reply-To: $replyTo";
    foreach ($extra as $hd) $headers[] = $hd;
    $msg = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n.";
    $sent = $cmd($msg, [250]);
    $cmd('QUIT', [221]);
    fclose($fp);
    return $sent;
}
