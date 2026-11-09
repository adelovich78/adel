<?php
// POST /api/contact.php — message de contact (kind=contact) ou demande de devis (kind=quote)
// Les e-mails partent même si la base de données est indisponible.
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_promo.php';
require __DIR__ . '/_crm.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Méthode non autorisée.', 405);

$d = body();
if (!empty($d['website'])) json_out(['ok' => true]); // champ piège anti-robot

// Limitation de débit par IP (fichier temporaire, indépendant de la base)
$ip = client_ip();
rate_limit('contact', (int)$CONFIG['contact_rate_limit']);

$kind    = ($d['kind'] ?? '') === 'quote' ? 'quote' : 'contact';
$name    = str_field($d, 'name', 120, true);
$email   = str_field($d, 'email', 255, true);
$phone   = str_field($d, 'phone', 40);
$company = str_field($d, 'company', 120);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Email invalide.', 422);

$details = null; $quote = null; $lang = 'tr'; $clean = []; $promo = ''; $promoRejected = false;
if ($kind === 'quote') {
    $message = str_field($d, 'message', 4000) ?? '';
    $raw = is_array($d['details'] ?? null) ? $d['details'] : [];
    // On ne garde que des valeurs simples (chaînes ou listes de chaînes)
    foreach ($raw as $k => $v) {
        if (!is_string($k) || strlen($k) > 30) continue;
        if (is_string($v)) $clean[$k] = mb_substr(trim($v), 0, 200);
        elseif (is_array($v)) $clean[$k] = array_slice(array_values(array_filter($v, 'is_string')), 0, 20);
    }
    $lang = in_array($clean['lang'] ?? '', ['tr', 'en', 'fr', 'ar'], true) ? $clean['lang'] : 'tr';
    // Code de réduction : consommé ici, une seule fois, avant le calcul du prix
    $asked = promo_normalise((string)($clean['promo'] ?? ''));
    if ($asked !== '') {
        $pdo = db_or_null();
        if ($pdo && promo_consume($pdo, $asked, $name, $email)) $promo = $asked;
        else $promoRejected = true;
    }
    $clean['promo'] = $promo;
    // Prix recalculé côté serveur : le navigateur ne peut pas le falsifier
    $quote = compute_quote(load_pricing(), $lang, $clean, $promo);
    $clean['price'] = (string)$quote['total'];
    $clean['currency'] = $quote['currency'];
    $details = json_encode($clean, JSON_UNESCAPED_UNICODE);
} else {
    $message = str_field($d, 'message', 4000, true);
}

// 1) Enregistrement en base (si disponible)
$saved = false;
$sid = uuid4();
if ($pdo = db_or_null()) {
    try {
        $pdo->prepare('INSERT INTO contact_submissions (id, kind, name, email, phone, company, sector, service, budget, message, details, status, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'new\', ?)')
            ->execute([$sid, $kind, $name, $email, $phone, $company,
                str_field($d, 'sector', 60), str_field($d, 'service', 60), str_field($d, 'budget', 60), $message, $details, $ip]);
        $saved = true;
    } catch (Throwable $e) {
        error_log('[webina] INSERT: ' . $e->getMessage());
    }
    // Automatisation CRM : fiche client + vente + tâche « Rappeler »
    crm_ingest_submission($pdo, [
        'kind' => $kind, 'name' => $name, 'email' => $email, 'phone' => $phone, 'company' => $company,
        'sector' => str_field($d, 'sector', 60), 'service' => str_field($d, 'service', 60), 'budget' => str_field($d, 'budget', 60),
        'city' => $clean['city'] ?? null, 'message' => $message, 'submission_id' => $saved ? $sid : null,
        'amount' => $quote['total'] ?? 0, 'currency' => $quote['currency'] ?? 'TRY', 'promo' => $promo,
    ]);
}

// 2) E-mail interne détaillé (FR) vers info@
$host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$notified = false;
$to = $CONFIG['notify_email'] ?? '';
if ($to !== '') {
    $info = [['Nom', $name], ['E-mail', $email], ['Téléphone', $phone ?? '—'], ['Société', $company ?? '—']];
    if ($kind === 'quote') {
        $yn = fn($v) => $v === 'yes' ? 'Oui' : ($v === 'no' ? 'Non' : '—');
        // Libellés FR des codes envoyés par le formulaire
        $FR = ['home' => 'Accueil', 'about' => 'À propos', 'services' => 'Services', 'blog' => 'Blog', 'gallery' => 'Galerie', 'contact' => 'Contact', 'shop' => 'Boutique',
            'tr' => 'Turc', 'en' => 'Anglais', 'ar' => 'Arabe', 'fr' => 'Français', 'ru' => 'Russe', 'de' => 'Allemand', 'pl' => 'Polonais', 'it' => 'Italien', 'es' => 'Espagnol',
            'form' => 'Formulaire de contact', 'whatsapp' => 'WhatsApp / téléphone', 'calendar' => 'Calendrier de RDV + gestion admin',
            'ready' => 'Prêts', 'partial' => 'En partie (à compléter)', 'need' => 'Rédaction complète',
            'own' => 'Photos fournies par le client', 'stock' => 'Photos prêtes (banque d\'images)', 'pro' => 'Photographe pro (1 journée, Istanbul)'];
        $fr = fn($v) => $FR[$v] ?? ($v ?: '—');
        $list = fn($v) => is_array($v) && $v ? implode(', ', array_map($fr, $v)) : '—';
        $info = array_merge($info, [
            ['Langue du formulaire', strtoupper($lang)],
            ['Logo', $yn($clean['logo'] ?? '')],
            ['Nom de domaine', $yn($clean['domain'] ?? '') . (!empty($clean['domainName']) ? ' · ' . $clean['domainName'] : '')],
            ['Activité', str_field($d, 'sector', 60) ?? '—'],
            ['Ville', ($clean['city'] ?? '') ?: '—'],
            ['Pages', 'Pack (Accueil, À propos, Services, Contact)' . (!empty($clean['pages']) ? ' + ' . $list($clean['pages']) : '')
                . ((int)($clean['customPages'] ?? 0) ? ' + ' . (int)$clean['customPages'] . ' page(s) sur mesure' . (!empty($clean['customDesc']) ? ' : ' . $clean['customDesc'] : '') : '')
                . " — total {$quote['pageCount']} pages"],
            ['Langues du site', $list($clean['langs'] ?? [])],
            ['Contact clients', $list($clean['booking'] ?? [])],
            ['Textes', $fr($clean['content'] ?? '')],
            ['Photos', $fr($clean['photos'] ?? '')],
            ['Site exemple', ($clean['example'] ?? '') ?: '—'],
            ['Code de réduction', $promo !== '' ? $promo : ($promoRejected ? 'refusé (déjà utilisé ou inconnu)' : '—')],
        ]);
    }
    $rows = '';
    foreach ($info as [$k, $v]) $rows .= '<tr><td style="padding:6px 12px 6px 0;color:#666;white-space:nowrap;vertical-align:top">' . h($k) . '</td><td style="padding:6px 0">' . h((string)$v) . '</td></tr>';
    $inner = '<h2 style="margin:0 0 6px;font-size:20px">' . ($kind === 'quote' ? 'Nouvelle demande de devis' : 'Nouveau message') . '</h2>'
        . '<p style="margin:0 0 18px;color:#666;font-size:13px">' . date('d/m/Y H:i') . ($saved ? '' : ' · <b style="color:#c00">non enregistrée en base (vérifier config.php)</b>') . '</p>'
        . '<table cellpadding="0" cellspacing="0" style="font-size:14px;margin-bottom:18px">' . $rows . '</table>'
        . ($quote ? quote_table_html($quote, mail_texts('fr')) : '')
        . ($message !== '' ? '<p style="margin-top:18px;padding:14px;background:#f4f6f2;border-radius:10px;white-space:pre-wrap;font-size:14px">' . h($message) . '</p>' : '')
        . '<p style="margin-top:22px"><a href="https://' . h($host) . '/admin" style="background:#2E8B2E;color:#fff;padding:12px 20px;border-radius:99px;text-decoration:none;font-weight:700">Ouvrir l\'admin</a></p>';
    $subject = ($kind === 'quote' ? 'Devis ' . money($quote['total'], $quote['currency']) : 'Message') . ' — ' . $name;
    foreach (email_list($to) as $rcpt) $notified = send_html_mail($rcpt, $subject, mail_layout($inner, false, $kind === 'quote'), $email) || $notified;
}

// 3) E-mail récapitulatif au client (dans sa langue)
if ($kind === 'quote') {
    $L = mail_texts($lang);
    $wa = 'https://wa.me/' . preg_replace('/\D/', '', $CONFIG['whatsapp'] ?? '');
    $inner = '<p style="font-size:16px;margin:0 0 8px"><b>' . h(str_replace('{n}', $name, $L['hello'])) . '</b></p>'
        . '<p style="font-size:14px;color:#444;margin:0 0 22px;line-height:1.6">' . h($L['intro']) . '</p>'
        . '<h3 style="margin:0 0 6px;font-size:16px">' . h($L['title']) . '</h3>'
        . quote_table_html($quote, $L)
        . '<p style="font-size:11px;color:#888;margin:14px 0 0">' . h($L['note']) . '</p>'
        . '<p style="margin-top:24px"><a href="' . h($wa) . '" style="background:#2E8B2E;color:#fff;padding:12px 22px;border-radius:99px;text-decoration:none;font-weight:700">' . h($L['cta']) . '</a></p>';
    send_html_mail($email, $L['subject'], mail_layout($inner, $lang === 'ar', true), email_list($CONFIG['notify_email'] ?? '')[0] ?? null);
}

// Échec seulement si rien n'a pu être enregistré ni transmis
if (!$saved && !$notified) fail('Envoi impossible pour le moment.', 503);

json_out(['ok' => true, 'total' => $quote['total'] ?? null, 'currency' => $quote['currency'] ?? null, 'promoRejected' => $promoRejected], 201);
