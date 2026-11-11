<?php
// Prospection : modèles d'e-mails par métier, file d'envoi lente, désinscription, recherche OpenStreetMap.
// Nécessite _bootstrap.php, _quote.php (envoi d'e-mails) et _crm.php.

// Métiers : libellé, mots-clés reconnus dans le secteur d'une fiche, démo à montrer, tags OpenStreetMap
const PROSPECT_TRADES = [
    'dentist'    => ['label' => 'Diş hekimi',        'kw' => ['diş', 'dis hekim', 'dentist', 'ortodont'],                     'osm' => ['amenity=dentist', 'healthcare=dentist']],
    'doctor'     => ['label' => 'Doktor / klinik',   'kw' => ['doktor', 'dr.', 'hekim', 'klinik', 'muayene', 'doctor'],         'osm' => ['amenity=doctors', 'amenity=clinic', 'healthcare=doctor']],
    'lawyer'     => ['label' => 'Avukat',            'kw' => ['avukat', 'hukuk', 'av.', 'lawyer', 'law'],                      'osm' => ['office=lawyer']],
    'accountant' => ['label' => 'Mali müşavir',      'kw' => ['muhasebe', 'müşavir', 'musavir', 'smmm', 'accountant'],         'osm' => ['office=accountant', 'office=tax_advisor']],
    'beauty'     => ['label' => 'Kuaför / güzellik', 'kw' => ['kuaför', 'kuafor', 'güzellik', 'berber', 'beauty', 'hair', 'nail'], 'osm' => ['shop=hairdresser', 'shop=beauty']],
    'auto'       => ['label' => 'Otomotiv',          'kw' => ['oto', 'otomotiv', 'detail', 'servis', 'lastik', 'car'],         'osm' => ['shop=car_repair', 'shop=car', 'shop=tyres', 'amenity=car_wash']],
    'realestate' => ['label' => 'Emlak',             'kw' => ['emlak', 'gayrimenkul', 'real estate', 'estate'],                'osm' => ['office=estate_agent']],
    'vet'        => ['label' => 'Veteriner',         'kw' => ['veteriner', 'vet', 'pet'],                                      'osm' => ['amenity=veterinary']],
    'photo'      => ['label' => 'Fotoğrafçı',        'kw' => ['fotoğraf', 'fotograf', 'foto ', 'photo'],                       'osm' => ['shop=photo', 'craft=photographer']],
    'events'     => ['label' => 'Düğün / etkinlik',  'kw' => ['düğün', 'dugun', 'etkinlik', 'organizasyon', 'catering', 'event'], 'osm' => ['amenity=events_venue', 'shop=wedding']],
    'cleaning'   => ['label' => 'Temizlik',          'kw' => ['temizlik', 'cleaning'],                                         'osm' => ['shop=laundry', 'shop=dry_cleaning']],
    'shop'       => ['label' => 'Mağaza / butik',    'kw' => ['mağaza', 'magaza', 'butik', 'shop', 'store'],                   'osm' => ['shop=clothes', 'shop=gift', 'shop=deli']],
    'general'    => ['label' => 'Diğer meslekler',   'kw' => [],                                                               'osm' => []],
];

function prospect_trade(?string $sector): string {
    $s = ' ' . mb_strtolower((string)$sector) . ' ';
    foreach (PROSPECT_TRADES as $k => $t) {
        foreach ($t['kw'] as $w) if ($w !== '' && mb_strpos($s, $w) !== false) return $k;
    }
    return 'general';
}

function prospect_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    crm_schema($pdo);
    $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    // Prospects = fiches clients au statut « lead » ; quelques colonnes en plus pour l'envoi
    $cols = array_column($pdo->query('SHOW COLUMNS FROM crm_clients')->fetchAll(), 'Field');
    if (!in_array('district', $cols, true)) $pdo->exec('ALTER TABLE crm_clients ADD COLUMN district VARCHAR(80) NULL AFTER city');
    if (!in_array('email_optout', $cols, true)) $pdo->exec('ALTER TABLE crm_clients ADD COLUMN email_optout TINYINT(1) NOT NULL DEFAULT 0');
    if (!in_array('last_mailed_at', $cols, true)) $pdo->exec('ALTER TABLE crm_clients ADD COLUMN last_mailed_at DATETIME NULL');
    if (!in_array('unsub_token', $cols, true)) $pdo->exec('ALTER TABLE crm_clients ADD COLUMN unsub_token CHAR(32) NULL, ADD UNIQUE KEY uq_unsub (unsub_token)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_templates (
        id CHAR(36) NOT NULL PRIMARY KEY,
        trade VARCHAR(20) NOT NULL,
        name VARCHAR(120) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_trade (trade)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_emails (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        template_id CHAR(36) NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'queued',
        error VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME NULL,
        INDEX idx_status (status, created_at), INDEX idx_client (client_id), INDEX idx_sent (sent_at)
    ) $o");
    if (!(int)$pdo->query('SELECT COUNT(*) FROM crm_templates')->fetchColumn()) prospect_seed_templates($pdo);
}

/* ---------- Modèles de départ (modifiables dans l'admin) ----------
   Variables : {isletme} nom de l'établissement · {ilce} quartier · {demo} lien vers l'exemple du métier */
function prospect_seed_templates(PDO $pdo): void {
    $common = "Ben Webina'dan yazıyorum. Webina, İstanbul merkezli bir dijital kreasyon ajansı: mesleğe özel web siteleri, online randevu, Google görünürlüğü (SEO) ve yönetim panelleri hazırlıyoruz.\n\n"
        . "Sizin için neler yapabiliriz:\n"
        . "– Telefonda da hızlı açılan, size ait modern bir web sitesi (4 sayfa, 2 dil, SSL)\n"
        . "– WhatsApp ve tek tıkla arama butonları, harita ve çalışma saatleri\n"
        . "– Google'da bulunmanız için temel SEO\n"
        . "– Talepleri ve randevuları takip edeceğiniz basit bir yönetim paneli\n\n";
    $close = "Mesleğinize yakın hazırladığımız örneği buradan inceleyebilirsiniz: {demo}\n\n"
        . "Size uygun bir zamanda 10 dakikalık kısa bir görüşme yapabilir miyiz? Bu e-postayı yanıtlamanız veya WhatsApp'tan yazmanız yeterli.\n\n"
        . "Saygılarımla,";
    $t = [
        ['dentist', 'Diş hekimleri', '{isletme} için hasta getiren bir web sitesi',
            "Merhaba,\n\nHastaların çoğu yeni bir diş hekimi seçmeden önce Google'da arama yapıyor, yorumlara ve kliniğin sitesine bakıyor. {ilce} bölgesinde sitesi olan bir muayenehane, bu hastaların ilk aradığı yer oluyor.\n\n"
            . $common . "Diş hekimleri için ayrıca: online randevu talebi, tedaviler sayfası, önce/sonra fotoğraf galerisi.\n\n" . $close],
        ['doctor', 'Doktorlar ve klinikler', '{isletme} için online randevu alan bir web sitesi',
            "Merhaba,\n\nHastalar artık bir doktoru seçmeden önce internette araştırıyor: uzmanlık alanı, tedaviler, muayenehanenin yeri ve randevu imkânı. Bunları gösteren bir site güven veriyor ve telefonla gelen randevuları azaltıyor.\n\n"
            . $common . "Doktorlar için ayrıca: online randevu talebi, tedaviler ve ameliyatlar sayfaları, blog.\n\n" . $close],
        ['lawyer', 'Avukatlar', '{isletme} için bilgilendirici bir web sitesi',
            "Merhaba,\n\nMüvekkiller bir avukata ulaşmadan önce genellikle internette çalışma alanlarına ve iletişim bilgilerine bakıyor. Avukatlık mesleğinin reklam yasağına uygun, tamamen bilgilendirici bir site bu güveni sağlıyor.\n\n"
            . $common . "Avukatlar için ayrıca: çalışma alanları, makaleler, görüşme talep formu; reklam yasağına uygun dil.\n\n" . $close],
        ['accountant', 'Mali müşavirler', '{isletme} için müşteri kazandıran bir web sitesi',
            "Merhaba,\n\nŞirket kuran ya da muhasebecisini değiştirmek isteyen işletmeler, mali müşavirlerini çoğunlukla internette arıyor. Hizmetlerinizi ve iletişim bilgilerinizi gösteren bir site, bu talepleri doğrudan size getiriyor.\n\n"
            . $common . "Mali müşavirler için ayrıca: hizmetler, şirket kuruluşu sayfası, müşteri belge yükleme alanı.\n\n" . $close],
        ['beauty', 'Kuaför ve güzellik salonları', '{isletme} için online randevulu bir web sitesi',
            "Merhaba,\n\nSalonunuzu arayan yeni müşteriler önce internette fiyatlara, fotoğraflara ve randevu imkânına bakıyor. Kendi siteniz olduğunda Instagram'a bağlı kalmadan randevu alabilirsiniz.\n\n"
            . $common . "Salonlar için ayrıca: online randevu, hizmet ve fiyat listesi, çalışmalarınızdan galeri.\n\n" . $close],
        ['auto', 'Otomotiv', '{isletme} için müşteri getiren bir web sitesi',
            "Merhaba,\n\nAraç sahipleri servis, bakım veya detailing için önce Google'da arama yapıyor. {ilce} bölgesinde sitesi olan işletmeler bu müşterilerin ilk aradığı yer oluyor.\n\n"
            . $common . "Otomotiv için ayrıca: hizmetler ve fiyatlar, önce/sonra galerisi, tek tıkla WhatsApp ile fiyat sorma.\n\n" . $close],
        ['realestate', 'Emlak', '{isletme} için ilanlarınızı gösteren bir web sitesi',
            "Merhaba,\n\nAlıcılar ve kiracılar ilan sitelerinin yanında, güvendikleri ofisin kendi sitesine de bakıyor. Kendi siteniz, portföyünüzü kendi markanızla göstermenizi sağlar.\n\n"
            . $common . "Emlak ofisleri için ayrıca: ilan sayfaları, çok dilli içerik (yabancı alıcılar için), WhatsApp ile hızlı iletişim.\n\n"
            . str_replace('{demo}', 'https://webina.com.tr/portfolio', $close)],
        ['vet', 'Veterinerler', '{isletme} için randevu alan bir web sitesi',
            "Merhaba,\n\nEvcil hayvan sahipleri yeni bir veteriner ararken önce internette kliniğin hizmetlerine, saatlerine ve yerine bakıyor. Acil durumlarda da telefon ve konum bilgisine hızla ulaşmak istiyorlar.\n\n"
            . $common . "Veteriner klinikleri için ayrıca: online randevu talebi, hizmetler, acil iletişim butonu.\n\n" . $close],
        ['photo', 'Fotoğrafçılar', '{isletme} için çalışmalarınızı gösteren bir portfolyo sitesi',
            "Merhaba,\n\nBir fotoğrafçıyı seçen müşteri önce çalışmalarına bakıyor. Kendi portfolyo siteniz, en iyi işlerinizi Instagram'dan daha düzenli ve profesyonel gösterir.\n\n"
            . $common . "Fotoğrafçılar için ayrıca: büyük fotoğraflı portfolyo, paketler ve fiyatlar, rezervasyon formu.\n\n" . $close],
        ['events', 'Düğün ve etkinlik', '{isletme} için rezervasyon alan bir web sitesi',
            "Merhaba,\n\nÇiftler ve şirketler etkinlik hazırlığına internette başlıyor: fotoğraflar, paketler ve müsait tarihler. Bunları gösteren bir site, sizi tekliflerin başına taşır.\n\n"
            . $common . "Etkinlik işletmeleri için ayrıca: galeri, menü ve paketler, rezervasyon talep formu.\n\n"
            . str_replace('{demo}', 'https://webina.com.tr/portfolio', $close)],
        ['cleaning', 'Temizlik', '{isletme} için teklif toplayan bir web sitesi',
            "Merhaba,\n\nEv ve ofis temizliği arayan müşteriler, hizmetleri ve fiyatları internette karşılaştırıyor. Kendi siteniz, teklif taleplerini doğrudan size getirir.\n\n"
            . $common . "Temizlik firmaları için ayrıca: hizmetler, bölgeler, online teklif formu.\n\n" . $close],
        ['shop', 'Mağazalar', '{isletme} için online satış yapan bir site',
            "Merhaba,\n\nMüşterileriniz mağazanıza gelmeden önce internette ürünlerinize bakmak, hatta online sipariş vermek istiyor. Kendi online mağazanızla 7/24 satış yapabilirsiniz.\n\n"
            . $common . "Mağazalar için ayrıca: ürün kataloğu, sepet ve sipariş, çok dilli satış.\n\n" . $close],
        ['general', 'Genel tanıtım (tüm meslekler)', '{isletme} için profesyonel bir web sitesi',
            "Merhaba,\n\nMüşterilerin çoğu bir işletmeyi seçmeden önce Google'da arama yapıyor. İnternette bir siteniz olmadığında, bu müşteriler sitesi olan rakiplerinize gidiyor.\n\n"
            . $common . $close],
    ];
    $st = $pdo->prepare('INSERT INTO crm_templates (id, trade, name, subject, body) VALUES (?, ?, ?, ?, ?)');
    foreach ($t as [$trade, $name, $subject, $body]) $st->execute([uuid4(), $trade, $name, $subject, $body]);
}

// Lien vers l'exemple le plus proche du métier
function prospect_demo(string $trade): string {
    return [
        'dentist' => 'https://test.webina.com.tr/', 'doctor' => 'https://test.webina.com.tr/', 'vet' => 'https://test.webina.com.tr/',
        'lawyer' => 'https://test2.webina.com.tr/', 'accountant' => 'https://test2.webina.com.tr/',
        'beauty' => 'https://my.webina.com.tr/', 'auto' => 'https://demo.webina.com.tr/', 'cleaning' => 'https://demo.webina.com.tr/',
        'photo' => 'https://adelbenelli.webina.com.tr/', 'shop' => 'https://eticaret.webina.com.tr/',
    ][$trade] ?? 'https://webina.com.tr/portfolio';
}

function prospect_fill(string $text, array $client): string {
    $trade = prospect_trade($client['sector'] ?? '');
    $name = trim((string)($client['company'] ?: $client['name']));
    $district = trim((string)($client['district'] ?? '')) ?: trim((string)($client['city'] ?? '')) ?: 'İstanbul';
    return strtr($text, ['{isletme}' => $name, '{ilce}' => $district, '{demo}' => prospect_demo($trade)]);
}

function prospect_token(PDO $pdo, array $client): string {
    if (!empty($client['unsub_token'])) return $client['unsub_token'];
    $tok = bin2hex(random_bytes(16));
    $pdo->prepare('UPDATE crm_clients SET unsub_token = ? WHERE id = ?')->execute([$tok, $client['id']]);
    return $tok;
}

// Visuel de l'e-mail selon le métier : capture de la démo la plus proche (JPG, lu par toutes les messageries)
function prospect_visual(string $trade): string {
    return ['dentist' => 'health', 'doctor' => 'health', 'vet' => 'health', 'lawyer' => 'law', 'accountant' => 'law',
        'beauty' => 'beauty', 'auto' => 'auto', 'cleaning' => 'auto', 'photo' => 'photo', 'shop' => 'shop'][$trade] ?? 'general';
}

// Liens de Webina, affichés en première ligne et en pied d'e-mail
function prospect_links(): array {
    global $CONFIG;
    $wa = preg_replace('/\D/', '', $CONFIG['whatsapp_site'] ?? $CONFIG['whatsapp'] ?? '');
    $links = [['Web', 'https://webina.com.tr'], ['Instagram', $CONFIG['instagram'] ?? 'https://www.instagram.com/webinacreativedesign/']];
    $yt = $CONFIG['youtube'] ?? 'https://www.youtube.com/channel/UCteSvU-tpoDeVEBHnRubuCQ';
    if ($yt) $links[] = ['YouTube', $yt];
    if ($wa) $links[] = ['WhatsApp', 'https://wa.me/' . $wa];
    return $links;
}

/* E-mail de prospection mis en page (tableaux et styles en ligne : rendu identique dans Gmail, Outlook, Apple Mail…)
   Le texte du modèle reste la source : paragraphes séparés par une ligne vide, lignes commençant par « – » = liste. */
function prospect_html(string $body, string $unsubUrl, string $trade = 'general', string $preheader = ''): string {
    global $CONFIG;
    $base = prospect_base_url();
    $green = '#23772a'; $ink = '#121812'; $muted = '#5b645e';
    $wa = preg_replace('/\D/', '', $CONFIG['whatsapp_site'] ?? $CONFIG['whatsapp'] ?? '');
    $demo = prospect_demo($trade);
    $link = fn($u, $t, $st = '') => '<a href="' . h($u) . '" style="color:' . $green . ';text-decoration:none;font-weight:600;' . $st . '">' . $t . '</a>';
    $inline = function (string $t) use ($green) {
        $t = h($t);
        return preg_replace('~(https?://[^\s<]+)~', '<a href="$1" style="color:' . $green . ';font-weight:600">$1</a>', $t);
    };

    // Corps : paragraphes, listes à coches ; la phrase avec le lien de démo est remplacée par le visuel
    $blocks = [];
    foreach (preg_split("/\n\s*\n/", trim(str_replace("\r", '', $body))) as $para) {
        $html = '';
        $lines = array_values(array_filter(array_map('trim', explode("\n", $para)), 'strlen'));
        if (!$lines) continue;
        if (strpos($para, $demo) !== false && count($lines) === 1) continue;
        $items = array_filter($lines, fn($l) => preg_match('/^[–\-•]\s*/u', $l));
        if (count($items) >= 2) {
            $head = array_values(array_filter($lines, fn($l) => !preg_match('/^[–\-•]\s*/u', $l)));
            if ($head) $html .= '<p style="margin:0 0 10px;font-weight:700;color:' . $ink . '">' . $inline(implode(' ', $head)) . '</p>';
            $html .= '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px">';
            foreach ($items as $it) {
                $html .= '<tr><td width="28" valign="top" style="padding:3px 0"><span style="display:inline-block;width:18px;height:18px;border-radius:5px;background:#e3f1e2;color:' . $green . ';font-size:12px;line-height:18px;text-align:center;font-weight:700">&#10003;</span></td>'
                    . '<td style="padding:3px 0;font-size:15px;line-height:1.5;color:' . $ink . '">' . $inline(preg_replace('/^[–\-•]\s*/u', '', $it)) . '</td></tr>';
            }
            $blocks[] = $html . '</table>';
            continue;
        }
        $blocks[] = '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:' . $ink . '">' . implode('<br>', array_map($inline, $lines)) . '</p>';
    }
    // L'encadré prix vient avant la demande de rendez-vous et la formule de politesse (les deux derniers paragraphes)
    $tail = count($blocks) > 3 ? array_splice($blocks, -2) : [];
    $html = implode('', $blocks);
    $after = implode('', $tail);

    $top = implode(' &nbsp;·&nbsp; ', array_map(fn($l) => $link($l[1], $l[0], 'color:#d7e3d4;font-weight:500'), prospect_links()));
    $foot = implode(' &nbsp;·&nbsp; ', array_map(fn($l) => $link($l[1], $l[0], 'color:' . $muted . ';font-weight:500'), prospect_links()));
    $img = $base . '/images/mail/' . prospect_visual($trade) . '.jpg';
    $demoHost = preg_replace('~^https?://|/$~', '', $demo);
    $btn = fn($u, $t, $bg, $fg, $extra = '') => '<a href="' . h($u) . '" style="display:inline-block;padding:13px 22px;margin:4px 0;border-radius:10px;background:' . $bg . ';color:' . $fg . ';font-size:15px;font-weight:700;text-decoration:none;' . $extra . '">' . $t . '</a>';
    $waText = rawurlencode('Merhaba, Webina e-postanız hakkında bilgi almak istiyorum.');

    return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>Webina</title></head>'
        . '<body style="margin:0;padding:0;background:#eef1ed;font-family:Arial,Helvetica,sans-serif">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . h($preheader) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1ed"><tr><td align="center" style="padding:24px 12px">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden">'
        // 1re ligne : logo + liens (site, Instagram, YouTube, WhatsApp)
        . '<tr><td style="background:' . $ink . ';padding:18px 28px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="font-size:22px;font-weight:800;letter-spacing:1px;color:#ffffff"><a href="https://webina.com.tr" style="color:#ffffff;text-decoration:none">WEB<span style="color:#7ED957">INA</span></a>'
        . '<div style="font-size:9px;letter-spacing:3px;color:#a9b4a6;font-weight:400;margin-top:2px">DİJİTAL KREASYON AJANSI</div></td>'
        . '<td align="right" style="font-size:13px;line-height:1.6">' . $top . '</td></tr></table></td></tr>'
        // Visuel : la démo du métier, cliquable
        . '<tr><td style="padding:28px 28px 0"><a href="' . h($demo) . '" style="text-decoration:none">'
        . '<img src="' . h($img) . '" width="544" alt="Örnek site: ' . h($demoHost) . '" style="display:block;width:100%;max-width:544px;height:auto;border:0;border-radius:12px;box-shadow:0 0 0 1px #e1e6e0"></a>'
        . '<p style="margin:10px 0 0;font-size:13px;color:' . $muted . '">Mesleğinize özel örnek: ' . $link($demo, h($demoHost) . ' &rarr;') . '</p></td></tr>'
        . '<tr><td style="padding:24px 28px 4px">' . $html . '</td></tr>'
        // Encadré prix + boutons
        . '<tr><td style="padding:0 28px 8px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f3;border-radius:14px"><tr><td style="padding:20px 22px">'
        . '<p style="margin:0;font-size:13px;color:' . $muted . '">Standart paket · 4 sayfa · 2 dil · SSL · SEO</p>'
        . '<p style="margin:4px 0 14px;font-size:26px;font-weight:800;color:' . $ink . '">17.000 TL<span style="font-size:15px;font-weight:600;color:' . $muted . '">\'den başlayan</span></p>'
        . '<p style="margin:0 0 14px;font-size:14px;color:' . $green . ';font-weight:700">+ Alan adı, 1 yıl hosting ve logo tasarımı ücretsiz</p>'
        . $btn('https://webina.com.tr/teklif', 'Fiyatınızı hesaplayın', $green, '#ffffff')
        . ($wa ? ' &nbsp; ' . $btn('https://wa.me/' . $wa . '?text=' . $waText, 'WhatsApp\'tan yazın', '#ffffff', $green, 'border:1.5px solid ' . $green . ';padding:11.5px 20.5px;') : '')
        . '</td></tr></table></td></tr>'
        . ($after ? '<tr><td style="padding:20px 28px 0">' . $after . '</td></tr>' : '')
        // Signature
        . '<tr><td style="padding:4px 28px 26px;font-size:14px;line-height:1.6;color:' . $ink . '"><strong>' . h($CONFIG['mail_from_name'] ?? 'Webina') . '</strong><br>'
        . '<span style="color:' . $muted . '">' . h($CONFIG['company_address'] ?? 'Maslak, İstanbul') . ($wa ? ' · +' . h($wa) : '') . '</span></td></tr>'
        . '</table>'
        // Pied : liens + désinscription
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px"><tr><td style="padding:16px 28px;font-size:12px;line-height:1.6;color:' . $muted . ';text-align:center">'
        . $foot . '<br>Bu e-posta, işletmenizin herkese açık iletişim adresine gönderilmiştir. Bir daha almak istemiyorsanız '
        . '<a href="' . h($unsubUrl) . '" style="color:' . $muted . '">buradan çıkabilirsiniz</a>.</td></tr></table>'
        . '</td></tr></table></body></html>';
}

function prospect_base_url(): string {
    global $CONFIG;
    $site = trim((string)($CONFIG['site_url'] ?? ''));
    if ($site !== '') return rtrim($site, '/');
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host ? 'https://' . $host : 'https://webina.com.tr';
}

/* ---------- File d'envoi ----------
   Les e-mails partent un par un, espacés et plafonnés par jour : un envoi massif depuis un
   hébergement mutualisé ferait classer webina.com.tr comme spam (et bloquer aussi vos devis). */
function prospect_limits(): array {
    global $CONFIG;
    return [
        'daily' => max(1, min(300, (int)($CONFIG['prospect_daily_limit'] ?? 40))),
        'gap' => max(5, (int)($CONFIG['prospect_gap_seconds'] ?? 25)),
    ];
}

function prospect_queue_state(PDO $pdo): array {
    $lim = prospect_limits();
    // Envoi interrompu (page fermée, délai dépassé) : marqué en échec plutôt que renvoyé deux fois
    $pdo->exec("UPDATE crm_emails SET status = 'failed', error = 'envoi interrompu' WHERE status = 'sending' AND sent_at < (NOW() - INTERVAL 10 MINUTE)");
    $today = (int)$pdo->query("SELECT COUNT(*) FROM crm_emails WHERE status = 'sent' AND sent_at >= CURDATE()")->fetchColumn();
    $queued = (int)$pdo->query("SELECT COUNT(*) FROM crm_emails WHERE status = 'queued'")->fetchColumn();
    $last = $pdo->query("SELECT MAX(sent_at) FROM crm_emails WHERE status IN ('sent', 'failed')")->fetchColumn();
    $wait = $last ? max(0, $lim['gap'] - (time() - strtotime($last))) : 0;
    return ['queued' => $queued, 'sentToday' => $today, 'daily' => $lim['daily'], 'gap' => $lim['gap'], 'wait' => $wait];
}

// Envoie au plus un e-mail si l'espacement et le plafond du jour le permettent
function prospect_send_next(PDO $pdo): array {
    $q = prospect_queue_state($pdo);
    if (!$q['queued']) return $q + ['sent' => null, 'reason' => 'empty'];
    if ($q['sentToday'] >= $q['daily']) return $q + ['sent' => null, 'reason' => 'daily'];
    if ($q['wait'] > 0) return $q + ['sent' => null, 'reason' => 'wait'];
    // Réservation de la ligne : deux onglets ouverts n'envoient pas deux fois le même e-mail
    $row = $pdo->query("SELECT * FROM crm_emails WHERE status = 'queued' ORDER BY created_at, id LIMIT 1")->fetch();
    if (!$row) return $q + ['sent' => null, 'reason' => 'empty'];
    $lock = $pdo->prepare("UPDATE crm_emails SET status = 'sending', sent_at = NOW() WHERE id = ? AND status = 'queued'");
    $lock->execute([$row['id']]);
    if ($lock->rowCount() !== 1) return $q + ['sent' => null, 'reason' => 'wait'];

    $c = $pdo->prepare('SELECT * FROM crm_clients WHERE id = ?');
    $c->execute([$row['client_id']]);
    $client = $c->fetch();
    if (!$client || (int)$client['email_optout'] === 1) {
        $pdo->prepare("UPDATE crm_emails SET status = 'skipped', error = 'désinscrit' WHERE id = ?")->execute([$row['id']]);
        return prospect_queue_state($pdo) + ['sent' => null, 'reason' => 'skipped'];
    }
    $unsub = prospect_base_url() . '/api/unsubscribe.php?t=' . prospect_token($pdo, $client);
    global $CONFIG;
    $replyTo = email_list($CONFIG['notify_email'] ?? '')[0] ?? null;
    $log = [];
    $html = prospect_html($row['body'], $unsub, prospect_trade($client['sector'] ?? ''), $row['subject']);
    $ok = send_html_mail($row['to_email'], $row['subject'], $html, $replyTo, $log,
        ['List-Unsubscribe: <' . $unsub . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click']);
    if ($ok) {
        $pdo->prepare("UPDATE crm_emails SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$row['id']]);
        $pdo->prepare('UPDATE crm_clients SET last_mailed_at = NOW() WHERE id = ?')->execute([$client['id']]);
        crm_log($pdo, $client['id'], null, 'email', 'E-mail de prospection envoyé : « ' . $row['subject'] . ' »');
    } else {
        $err = mb_substr(implode(' | ', array_slice($log, -2)), 0, 250) ?: 'échec de l\'envoi';
        $pdo->prepare("UPDATE crm_emails SET status = 'failed', sent_at = NOW(), error = ? WHERE id = ?")->execute([$err, $row['id']]);
    }
    return prospect_queue_state($pdo) + ['sent' => ['to' => $row['to_email'], 'ok' => $ok, 'name' => $client['company'] ?: $client['name']]];
}

/* ---------- Recherche automatique : OpenStreetMap (gratuit, données libres ODbL) ----------
   Renvoie nom, téléphone, e-mail et site quand ils sont renseignés sur la carte. */
function prospect_osm_search(string $trade, string $district): array {
    global $CONFIG;
    $tags = PROSPECT_TRADES[$trade]['osm'] ?? [];
    if (!$tags) fail('Choisissez un métier.', 422);
    $district = trim($district);
    $area = $district !== ''
        ? 'area["name"="' . addcslashes($district, '"\\') . '"]["boundary"="administrative"]->.a;'
        : 'area["name"="İstanbul"]["admin_level"="4"]->.a;';
    $parts = '';
    foreach ($tags as $t) { [$k, $v] = explode('=', $t); $parts .= "nwr[\"$k\"=\"$v\"](area.a);"; }
    $query = "[out:json][timeout:25];{$area}({$parts});out tags center 300;";
    $url = $CONFIG['overpass_url'] ?? 'https://overpass-api.de/api/interpreter';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['data' => $query]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_USERAGENT => 'Webina-CRM/1.0']);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code !== 200) fail('La recherche OpenStreetMap ne répond pas (code ' . $code . '). Réessayez dans une minute.', 502);
    $data = json_decode($raw, true);
    $out = [];
    foreach (($data['elements'] ?? []) as $e) {
        $tg = $e['tags'] ?? [];
        $name = trim((string)($tg['name'] ?? $tg['name:tr'] ?? ''));
        if ($name === '') continue;
        $email = trim((string)($tg['email'] ?? $tg['contact:email'] ?? ''));
        $phone = trim((string)($tg['phone'] ?? $tg['contact:phone'] ?? $tg['contact:mobile'] ?? ''));
        $web = trim((string)($tg['website'] ?? $tg['contact:website'] ?? $tg['url'] ?? ''));
        $social = trim((string)($tg['contact:instagram'] ?? $tg['contact:facebook'] ?? ''));
        $dist = trim((string)($tg['addr:district'] ?? $tg['addr:suburb'] ?? $district));
        $street = trim(($tg['addr:street'] ?? '') . ' ' . ($tg['addr:housenumber'] ?? ''));
        $out[] = ['osm' => ($e['type'] ?? 'n') . '/' . ($e['id'] ?? 0), 'name' => mb_substr($name, 0, 160), 'email' => mb_substr($email, 0, 255),
            'phone' => mb_substr($phone, 0, 60), 'website' => mb_substr($web, 0, 255), 'social' => mb_substr($social, 0, 255),
            'district' => mb_substr($dist, 0, 80), 'address' => mb_substr($street, 0, 160)];
    }
    // Les fiches sans site en premier, puis celles qui ont un e-mail
    usort($out, fn($a, $b) => [($a['website'] !== ''), ($a['email'] === ''), $a['name']] <=> [($b['website'] !== ''), ($b['email'] === ''), $b['name']]);
    return $out;
}
