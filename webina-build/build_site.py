"""Générateur du site Webina (6 pages, turc en statique + bascule 4 langues côté client).

Usage : python3 build_site.py [dossier_sortie]   (défaut : ../webina)
Écrit les pages HTML et js/i18n.js. Le CSS, le JS, les images, les polices,
l'API PHP et pricing.json vivent directement dans le dossier du site.
"""
import json, re, html, os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from details import build as build_details

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.abspath(sys.argv[1] if len(sys.argv) > 1 else os.path.join(HERE, '..', 'webina'))
LANGS = ['tr', 'en', 'fr', 'ar']

base = json.load(open(os.path.join(HERE, 'i18n.json'), encoding='utf-8'))
demo = json.load(open(os.path.join(HERE, 'demo_i18n.json'), encoding='utf-8'))
quote = json.load(open(os.path.join(HERE, 'quote_i18n.json'), encoding='utf-8'))

def strip_end(s):
    # Titres et liens courts : ni point final, ni flèche, ni point d'exclamation
    return re.sub(r'\s*[.→←!]\s*$', '', s)

ui = {
    'tr': {'menu': 'Menü', 'theme': 'Temayı değiştir', 'lang': 'Dil', 'skip': 'İçeriğe geç', 'demoHint': 'Sayfayı görmek için üzerine gelin',
           'more': 'Tüm hizmetler', 'seeAll': 'Tüm projeler', 'whatsapp': 'WhatsApp', 'demoTab': 'Demo seç', 'office': 'Ofis',
           'visit': 'Siteyi aç', 'filter': 'Kategoriye göre filtrele'},
    'en': {'menu': 'Menu', 'theme': 'Switch theme', 'lang': 'Language', 'skip': 'Skip to content', 'demoHint': 'Hover to scroll the page',
           'more': 'All services', 'seeAll': 'All projects', 'whatsapp': 'WhatsApp', 'demoTab': 'Choose a demo', 'office': 'Office',
           'visit': 'Open site', 'filter': 'Filter by category'},
    'fr': {'menu': 'Menu', 'theme': 'Changer de thème', 'lang': 'Langue', 'skip': 'Aller au contenu', 'demoHint': 'Survolez pour faire défiler la page',
           'more': 'Tous les services', 'seeAll': 'Tous les projets', 'whatsapp': 'WhatsApp', 'demoTab': 'Choisir une démo', 'office': 'Bureau',
           'visit': 'Ouvrir le site', 'filter': 'Filtrer par catégorie'},
    'ar': {'menu': 'القائمة', 'theme': 'تغيير المظهر', 'lang': 'اللغة', 'skip': 'انتقل إلى المحتوى', 'demoHint': 'مرّر المؤشر لتصفح الصفحة',
           'more': 'كل الخدمات', 'seeAll': 'كل المشاريع', 'whatsapp': 'واتساب', 'demoTab': 'اختر عرضاً', 'office': 'المكتب',
           'visit': 'افتح الموقع', 'filter': 'تصفية حسب الفئة'},
}


COOKIE = {
    'tr': {'title': 'Gizliliğinize saygı duyuyoruz',
           'text': 'Sitenin çalışması için gerekli çerezleri kullanıyoruz. İzin verirseniz, ziyaretleri ölçmek ve size uygun içerik göstermek için ek çerezler de kullanabiliriz.',
           'text2': 'Seçiminizi istediğiniz zaman sayfanın altındaki «Çerez ayarları» bağlantısından değiştirebilirsiniz.',
           'settings': 'Çerez ayarları', 'reject': 'Reddet ve kapat', 'accept': 'Kabul et ve kapat', 'save': 'Seçimimi kaydet', 'back': 'Geri',
           'always': 'Her zaman açık',
           'necessary': 'Gerekli', 'necessaryText': 'Dil ve tema tercihleriniz, güvenlik ve formların çalışması için gereklidir.',
           'analytics': 'Ölçüm', 'analyticsText': 'Hangi sayfaların ziyaret edildiğini anonim olarak ölçmemize yardımcı olur.',
           'marketing': 'Pazarlama', 'marketingText': 'İlgi alanlarınıza uygun içerik ve reklam gösterilmesine izin verir.'},
    'en': {'title': 'We respect your privacy',
           'text': 'We use the cookies the site needs to work. With your consent, we may also use additional cookies to measure visits and show you relevant content.',
           'text2': 'You can change your choice at any time from the “Cookie settings” link at the bottom of every page.',
           'settings': 'Cookie settings', 'reject': 'Reject and close', 'accept': 'Accept and close', 'save': 'Save my choice', 'back': 'Back',
           'always': 'Always on',
           'necessary': 'Necessary', 'necessaryText': 'Needed for your language and theme preferences, security and forms.',
           'analytics': 'Measurement', 'analyticsText': 'Helps us measure, anonymously, which pages are visited.',
           'marketing': 'Marketing', 'marketingText': 'Allows content and ads tailored to your interests.'},
    'fr': {'title': 'Nous respectons votre vie privée',
           'text': 'Nous utilisons les cookies nécessaires au fonctionnement du site. Avec votre accord, nous pouvons aussi utiliser des cookies supplémentaires pour mesurer les visites et vous proposer des contenus adaptés.',
           'text2': 'Vous pouvez modifier votre choix à tout moment via le lien « Paramètres des cookies » en bas de chaque page.',
           'settings': 'Paramètres des cookies', 'reject': 'Refuser et fermer', 'accept': 'Accepter et fermer', 'save': 'Enregistrer mon choix', 'back': 'Retour',
           'always': 'Toujours actifs',
           'necessary': 'Nécessaires', 'necessaryText': 'Indispensables pour vos préférences de langue et de thème, la sécurité et les formulaires.',
           'analytics': 'Mesure d’audience', 'analyticsText': 'Nous aide à mesurer, de façon anonyme, les pages visitées.',
           'marketing': 'Marketing', 'marketingText': 'Permet de vous proposer des contenus et publicités adaptés à vos centres d’intérêt.'},
    'ar': {'title': 'نحترم خصوصيتكم',
           'text': 'نستخدم ملفات تعريف الارتباط الضرورية لعمل الموقع. وبموافقتكم، قد نستخدم ملفات إضافية لقياس الزيارات وعرض محتوى يناسبكم.',
           'text2': 'يمكنكم تغيير اختياركم في أي وقت عبر رابط «إعدادات ملفات تعريف الارتباط» أسفل كل صفحة.',
           'settings': 'إعدادات ملفات تعريف الارتباط', 'reject': 'رفض وإغلاق', 'accept': 'قبول وإغلاق', 'save': 'حفظ اختياري', 'back': 'رجوع',
           'always': 'مفعّلة دائماً',
           'necessary': 'ضرورية', 'necessaryText': 'لازمة لتفضيلات اللغة والمظهر والأمان وعمل النماذج.',
           'analytics': 'القياس', 'analyticsText': 'تساعدنا على قياس الصفحات التي تتم زيارتها بشكل مجهول.',
           'marketing': 'التسويق', 'marketingText': 'تسمح بعرض محتوى وإعلانات تناسب اهتماماتكم.'},
}

PROMO = {
    'tr': {'label': 'İndirim kodu', 'ph': 'Kodunuz', 'apply': 'Uygula', 'applied': 'Kod uygulandı', 'invalid': 'Bu kod geçerli değil', 'remove': 'Kaldır', 'used': 'Bu kod daha önce kullanılmış', 'line': 'İndirim kodu ({c})'},
    'en': {'label': 'Discount code', 'ph': 'Your code', 'apply': 'Apply', 'applied': 'Code applied', 'invalid': 'This code is not valid', 'remove': 'Remove', 'used': 'This code has already been used', 'line': 'Discount code ({c})'},
    'fr': {'label': 'Code de réduction', 'ph': 'Votre code', 'apply': 'Appliquer', 'applied': 'Code appliqué', 'invalid': "Ce code n'est pas valide", 'remove': 'Retirer', 'used': 'Ce code a déjà été utilisé', 'line': 'Code de réduction ({c})'},
    'ar': {'label': 'رمز الخصم', 'ph': 'رمزكم', 'apply': 'تطبيق', 'applied': 'تم تطبيق الرمز', 'invalid': 'هذا الرمز غير صالح', 'remove': 'إزالة', 'used': 'تم استخدام هذا الرمز من قبل', 'line': 'رمز الخصم ({c})'},
}

# Un mot par réalisation de la scène du héro (même ordre que HERO, plus bas)
ROTATE = {
    'tr': ['mağazayı', 'portfolyoyu', 'büro sitesini', 'vitrini', 'haber sitesini', 'kurumsal siteyi', 'markayı'],
    'en': ['online store', 'portfolio', 'firm website', 'showcase', 'news site', 'corporate site', 'brand'],
    'fr': ['la boutique', 'le portfolio', 'le site du cabinet', 'la vitrine', "le site d'actualités", "le site d'entreprise", 'la marque'],
    'ar': ['متجراً إلكترونياً', 'معرضاً رقمياً', 'موقعاً لمكتبك', 'حضوراً رقمياً', 'موقعاً إخبارياً', 'موقعاً لشركتك', 'موقعاً احترافياً'],
}
# Textes de la version 2 de l'accueil : scène, bandeau clients, manifeste, chiffres
V2 = {
    'tr': {
        'stage': {'aria': 'Öne çıkan projeler', 'prev': 'Önceki proje', 'next': 'Sonraki proje',
                  'pause': 'Otomatik geçişi durdur', 'play': 'Otomatik geçişi başlat', 'drag': 'Sürükle', 'open': 'Aç'},
        'speed': 'Bu sayfa {t} saniyede açıldı',
        'clients': {'label': 'Bize güvenenler'},
        'manifesto': {'eyebrow': 'Yaklaşımımız',
                      'text': 'Web sitesi bir kartvizit değil, sizin için çalışan bir ekip arkadaşıdır: randevu alır, teklif hazırlar, dört dilde konuşur ve siz uyurken müşteri karşılar.'},
        'facts': ['yayında müşteri sitesi', 'canlı sektör demosu', 'dilde yayın: TR, EN, FR, AR', 'uzmanlık, tek ekipte'],
    },
    'en': {
        'stage': {'aria': 'Featured projects', 'prev': 'Previous project', 'next': 'Next project',
                  'pause': 'Pause autoplay', 'play': 'Resume autoplay', 'drag': 'Drag', 'open': 'Open'},
        'speed': 'This page loaded in {t} s',
        'clients': {'label': 'Trusted by'},
        'manifesto': {'eyebrow': 'Our approach',
                      'text': 'A website is not a business card. It is a teammate that works for you: it books appointments, prepares quotes, speaks four languages and welcomes customers while you sleep.'},
        'facts': ['client sites live', 'live industry demos', 'languages: TR, EN, FR, AR', 'disciplines in one team'],
    },
    'fr': {
        'stage': {'aria': 'Projets à la une', 'prev': 'Projet précédent', 'next': 'Projet suivant',
                  'pause': 'Mettre le défilement en pause', 'play': 'Reprendre le défilement', 'drag': 'Glisser', 'open': 'Ouvrir'},
        'speed': "Cette page s'est affichée en {t} s",
        'clients': {'label': 'Ils nous font confiance'},
        'manifesto': {'eyebrow': 'Notre approche',
                      'text': "Un site web n'est pas une carte de visite. C'est un collègue qui travaille pour vous : il prend les rendez-vous, prépare les devis, parle quatre langues et accueille vos clients pendant que vous dormez."},
        'facts': ['sites clients en ligne', 'démos métier en ligne', 'langues : TR, EN, FR, AR', 'expertises dans une seule équipe'],
    },
    'ar': {
        'stage': {'aria': 'مشاريع مختارة', 'prev': 'المشروع السابق', 'next': 'المشروع التالي',
                  'pause': 'إيقاف العرض التلقائي', 'play': 'متابعة العرض التلقائي', 'drag': 'اسحب', 'open': 'افتح'},
        'speed': 'فُتحت هذه الصفحة خلال {t} ثانية',
        'clients': {'label': 'يثقون بنا'},
        'manifesto': {'eyebrow': 'نهجنا',
                      'text': 'الموقع الإلكتروني ليس بطاقة عمل، بل زميل يعمل لأجلك: يحجز المواعيد، يجهّز عروض الأسعار، يتحدث أربع لغات ويستقبل عملاءك وأنت نائم.'},
        'facts': ['مواقع عملاء على الإنترنت', 'عروض تجريبية حية', 'لغات: التركية والإنجليزية والفرنسية والعربية', 'تخصصاً في فريق واحد'],
    },
}

DICT = {}
for l in LANGS:
    b = base[l]
    h = dict(b['home'])
    for k in ['h1d', 'whatB', 'sectorsB']:
        h[k] = strip_end(h[k])
    # Français : l'article fait partie du mot qui change (« le digital », « la boutique »…)
    if l == 'fr':
        h['h1a'], h['h1b'] = 'On crée', 'le digital'
    # Mot vert du titre : un par réalisation de la scène, dans le même ordre que HERO.
    # Le mot d'origine (h1b) reste affiché à l'arrivée, avant le premier changement.
    h['rotate'] = ROTATE[l]
    team = dict(b['team']); team['t2'] = strip_end(team['t2'])
    foot = dict(b['footer']); foot['title2'] = strip_end(foot['title2'])
    dm = dict(demo[l]); dm['t2'] = strip_end(dm['t2'])
    sp = dict(b['servicesPage']); sp['t2'] = strip_end(sp['t2'])
    sc = dict(b['sectorsPage']); sc['t2'] = strip_end(sc['t2'])
    pp = dict(b['portfolioPage']); pp['t1'] = strip_end(pp['t1'])
    ct = dict(b['contact']); ct['t2'] = strip_end(ct['t2']); ct['sentT'] = strip_end(ct['sentT'])
    ct['sending'] = ct['sending'].rstrip('.…') + '…'
    q = json.loads(json.dumps(quote[l])); q['t2'] = strip_end(q['t2']); q['sentT'] = strip_end(q['sentT'])
    q['sum']['sending'] = q['sum']['sending'].rstrip('.…') + '…'
    q['promo'] = PROMO[l]; q['lines']['promo'] = PROMO[l]['line']
    DICT[l] = {
        'nav': b['nav'], 'cta': b['cta'], 'footer': foot, 'home': h, 'team': team,
        'services': b['services'], 'sectors': b['sectors'], 'cats': b['cats'], 'tags': b['tags'],
        'demo': dm, 'ui': ui[l], 'servicesPage': sp, 'sectorsPage': sc, 'portfolioPage': pp,
        'contact': ct, 'quote': q, 'cookie': COOKIE[l], 'details': build_details(l),
        **V2[l],
    }
TR = DICT['tr']

def get(d, key):
    for part in key.split('.'):
        d = d[int(part)] if isinstance(d, list) else d[part]
    return d
def t(key): return html.escape(get(TR, key))
def ti(key): return f'data-i18n="{key}"'
def tx(key, tag='span', cls=''):
    c = f' class="{cls}"' if cls else ''
    return f'<{tag}{c} {ti(key)}>{t(key)}</{tag}>'
def icon(name, cls='icon'):
    return f'<svg class="{cls}" aria-hidden="true"><use href="#{name}"/></svg>'

# ---------- Données ----------
PROJECTS = [
    {'title': 'Adel Benelli', 'cat': 'design', 'tags': ['vitrine', 'multi', 'uiux'], 'img': 'w-adelbenelli.webp', 'url': 'https://adelbenelli.webina.com.tr/'},
    {'title': 'NEW TR News Agency', 'cat': 'press', 'tags': ['media', 'news', 'multi'], 'img': 'w-newtrtv-FiRvvTCk.webp', 'url': 'https://newtr.tv/'},
    {'title': 'RightExpert', 'cat': 'lawacc', 'tags': ['vitrine', 'seo', 'booking'], 'img': 'w-right-C977AepQ.webp', 'url': 'https://www.right-expert.com/'},
    {'title': 'YDM TV', 'cat': 'media', 'tags': ['corporate', 'reserve'], 'img': 'w-ydm-BkQBTV4W.webp', 'url': 'https://ydm-tv.com/'},
    {'title': 'J AND J Global', 'cat': 'trade', 'tags': ['corporate', 'multi'], 'img': 'w-jandj-D28PlbRX.webp', 'url': 'https://jandjglobal.net/'},
    {'title': 'Ola Andalusia', 'cat': 'consult', 'tags': ['vitrine', 'leads'], 'img': 'w-ola-DcZDCr04.webp', 'url': 'https://olaandalusia.com/'},
    {'title': 'Audexa', 'cat': 'accounting', 'tags': ['vitrine', 'uiux'], 'img': 'w-audexa-yDxpiSMP.webp', 'url': 'https://audexa.com/'},
    {'title': 'New TR LIVE', 'cat': 'press', 'tags': ['live', 'media'], 'img': 'w-newtrlive-C2LJbWvW.webp', 'url': 'https://www.newtr.live/'},
    {'title': 'Yıldız Hukuk', 'cat': 'lawacc', 'tags': ['booking', 'multi', 'vitrine'], 'img': 'w-avukat-w3pfLOAM.webp', 'url': 'https://test2.webina.com.tr/', 'demo': True},
    {'title': 'Yıldız Oto Elektrik', 'cat': 'auto', 'tags': ['finder', 'vitrine', 'leads'], 'img': 'w-yildiz-g8dHpiBm.webp', 'url': 'https://demo.webina.com.tr/', 'demo': True},
    {'title': 'Yıldız Berber', 'cat': 'beauty', 'tags': ['booking', 'multi', 'vitrine'], 'img': 'w-kuafor-BFD-VWN0.webp', 'url': 'https://my.webina.com.tr/', 'demo': True},
    {'title': 'Dr. Karim Haddad', 'cat': 'health', 'tags': ['booking', 'multi', 'vitrine'], 'img': 'w-doktor-Ckhg_LFM.webp', 'url': 'https://test.webina.com.tr/', 'demo': True},
    {'title': 'Bio&Baio', 'cat': 'ecommerce', 'tags': ['shop', 'cart', 'multi'], 'img': 'w-biobaio.webp', 'url': 'https://eticaret.webina.com.tr/', 'demo': True},
]
for p in PROJECTS:
    p['host'] = re.sub(r'^https?://(www\.)?|/$', '', p['url'])
# Captures aussi disponibles en 1024 px (écrans haute densité)
HIRES = {'w-biobaio.webp', 'w-right-C977AepQ.webp', 'w-ola-DcZDCr04.webp', 'w-newtrtv-FiRvvTCk.webp',
         'w-jandj-D28PlbRX.webp', 'w-audexa-yDxpiSMP.webp', 'w-ydm-BkQBTV4W.webp', 'w-newtrlive-C2LJbWvW.webp'}
def srcset(img):
    return f' srcset="/images/{img} 840w, /images/{img[:-5]}-1024.webp 1024w"' if img in HIRES else ''
CLIENTS = [p for p in PROJECTS if not p.get('demo')]   # sites clients (hors démos)
HERO = [PROJECTS[12], PROJECTS[0], PROJECTS[2], PROJECTS[5], PROJECTS[1], PROJECTS[4], PROJECTS[6]]  # ordre de passage dans le héro
DEMOS = [
    {'key': 'law', 'img': 'long-avukat-xcq9581a.webp', 'url': 'https://test2.webina.com.tr/', 'host': 'test2.webina.com.tr', 'icon': 'i-scale'},
    {'key': 'doctor', 'img': 'long-doktor-I3nRpI3G.webp', 'url': 'https://test.webina.com.tr/', 'host': 'test.webina.com.tr', 'icon': 'i-stethoscope'},
    {'key': 'shop', 'img': 'long-yildiz-Ce8JzrvT.webp', 'url': 'https://demo.webina.com.tr/', 'host': 'demo.webina.com.tr', 'icon': 'i-store'},
    {'key': 'barber', 'img': 'long-kuafor-Cd_bigEo.webp', 'url': 'https://my.webina.com.tr/', 'host': 'my.webina.com.tr', 'icon': 'i-scissors'},
    {'key': 'ecommerce', 'img': 'long-biobaio.webp', 'url': 'https://eticaret.webina.com.tr/', 'host': 'eticaret.webina.com.tr', 'icon': 'i-bag'},
]
DEPTS = ['strategy', 'design', 'dev', 'marketing', 'support']
CITIES = ["İstanbul", "Ankara", "İzmir", "Bursa", "Antalya", "Kocaeli", "Konya", "Adana", "Gaziantep", "Eskişehir", "Muğla", "Trabzon"]
NAV = [('home', '/'), ('services', '/services'), ('sectors', '/secteurs'), ('portfolio', '/portfolio'), ('contact', '/contact'), ('quote', '/teklif')]

def nav_items(cls, current):
    return '\n'.join(
        f'<a href="{h}" class="{cls}"{" aria-current=\"page\"" if k == current else ""} {ti("nav."+k)}>{t("nav."+k)}</a>'
        for k, h in NAV)

# ---------- Fragments accueil ----------
def home_fragments():
    # Scène du héro : une fenêtre de navigateur par réalisation
    cards, segs, labels = [], [], []
    for i, p in enumerate(HERO):
        prio = 'fetchpriority="high"' if i == 0 else 'fetchpriority="low"'
        cards.append(f'''
              <a href="{p['url']}" target="_blank" rel="noopener" class="stage_card" data-stage-card{'' if i == 0 else ' tabindex="-1"'}>
                <span class="stage_bar" aria-hidden="true"><span class="browser_dots"><i></i><i></i><i></i></span><span class="stage_url" dir="ltr">{p['host']}</span></span>
                <img src="/images/{p['img']}"{srcset(p['img'])} sizes="(max-width: 767px) 80vw, 46vw" alt="{html.escape(p['title'])}" width="840" height="472" {prio} decoding="async" draggable="false">
                <span class="stage_shade" aria-hidden="true"></span>
              </a>''')
        segs.append(f'''
                  <button type="button" class="stage_seg{' is-active' if i == 0 else ''}" data-stage-go="{i}" aria-label="{html.escape(p['title'])}"><span class="stage_seg-line"><span class="stage_seg-fill"></span></span></button>''')
        labels.append(f'''
                  <span class="stage_label-item{' is-active' if i == 0 else ''}"><span class="brand-square" aria-hidden="true"></span>{tx(f"cats.{p['cat']}", 'span', 'stage_label-cat')}<span class="stage_label-title">{html.escape(p['title'])}</span></span>''')
    services = ''.join(f'''
            <li class="services_item has-detail" data-reveal style="--d:{min(i,5)}">
              {cover('services', i, f'ser-t-{i}')}
              <span class="services_index text-style-mono">{i+1:02d}</span>
              <h3 class="services_title heading-style-h4" id="ser-t-{i}" {ti(f"services.{i}.title")}>{t(f"services.{i}.title")}</h3>
              {tx(f"services.{i}.text", 'p', 'services_text text-color-secondary')}
              {detail_panel('services', i)}
            </li>''' for i in range(6))
    tabs, panels = [], []
    for i, d in enumerate(DEMOS):
        k = d['key']
        tabs.append(f'<button type="button" role="tab" class="demos_tab" id="tab-{k}" aria-controls="panel-{k}" aria-selected="{"true" if i==1 else "false"}" tabindex="{0 if i==1 else -1}">{icon(d["icon"])}{tx(f"demo.items.{k}.tab")}</button>')
        feats = ''.join(f'<li class="demos_feat">{icon("i-check","icon is-accent")}{tx(f"demo.items.{k}.feats.{j}")}</li>' for j in range(len(TR['demo']['items'][k]['feats'])))
        panels.append(f'''
            <div class="demos_panel" role="tabpanel" id="panel-{k}" aria-labelledby="tab-{k}" {'hidden' if i!=1 else ''}>
              <div class="demos_frame">
                <span class="crop-mark is-tl" aria-hidden="true"></span><span class="crop-mark is-tr" aria-hidden="true"></span><span class="crop-mark is-bl" aria-hidden="true"></span><span class="crop-mark is-br" aria-hidden="true"></span>
                <div class="browser_bar"><span class="browser_dots" aria-hidden="true"><i></i><i></i><i></i></span><span class="browser_url">{d['host']}</span><span class="browser_live text-style-mono"><span class="live-dot" aria-hidden="true"></span>{tx("demo.badge")}</span></div>
                <div class="demos_screen" tabindex="0"><img src="/images/{d['img']}" alt="" width="800" loading="lazy" decoding="async"></div>
              </div>
              <div class="demos_info">
                {tx(f"demo.items.{k}.name", 'h3', 'heading-style-h3')}
                {tx(f"demo.items.{k}.desc", 'p', 'text-size-medium text-color-secondary')}
                <ul class="demos_feats" role="list">{feats}</ul>
                <div class="button-group">
                  <a href="/teklif" class="button">{tx("demo.want")}{icon("i-arrow")}</a>
                  <a href="{d['url']}" target="_blank" rel="noopener" class="button is-secondary">{tx("demo.live")}{icon("i-arrow")}</a>
                </div>
                {tx("ui.demoHint", 'p', 'demos_hint text-size-small text-color-muted')}
              </div>
            </div>''')
    team = []
    for n, dk in enumerate(DEPTS):
        roles = [i for i, r in enumerate(TR['team']['roles']) if r['dept'] == dk]
        items = ''.join(f'''
                <li class="team_role has-detail">{cover('roles', i, f'rol-t-{i}')}<h3 class="team_role-title" id="rol-t-{i}" {ti(f"team.roles.{i}.title")}>{t(f"team.roles.{i}.title")}</h3>{tx(f"team.roles.{i}.text", 'p', 'text-size-small text-color-secondary')}{detail_panel('roles', i)}</li>''' for i in roles)
        team.append(f'''
            <div class="team_dept" data-reveal style="--d:{n}">
              <p class="team_dept-name text-style-mono">{tx(f"team.depts.{dk}")}<span class="text-color-muted">{len(roles):02d}</span></p>
              <ul class="team_roles" role="list">{items}
              </ul>
            </div>''')
    # Bandeau clients (la copie sert à boucler le défilement sans raccord)
    def client_items(clone):
        return ''.join(f'''
              <li class="clients_item"><a href="{p['url']}" target="_blank" rel="noopener" class="clients_link"{' tabindex="-1"' if clone else ''}>{html.escape(p['title'])}</a><span class="brand-square" aria-hidden="true"></span></li>''' for p in CLIENTS)
    # Chiffres : calculés à partir du contenu du site, donc toujours exacts
    numbers = [len(CLIENTS), len(DEMOS), len(LANGS), len(TR['team']['roles'])]
    facts = ''.join(f'''
                <li class="facts_item" data-reveal style="--d:{i}"><span class="facts_number" data-count="{n}">{n}</span>{tx(f"facts.{i}", 'span', 'facts_label text-color-secondary')}</li>''' for i, n in enumerate(numbers))
    # Projets clients : index typographique ; l'aperçu suit le curseur (vignette en ligne sur mobile)
    worklist = ''.join(f'''
                <li class="worklist_item" data-reveal>
                  <a href="{p['url']}" target="_blank" rel="noopener" class="worklist_link" data-preview="{i}">
                    <span class="worklist_index text-style-mono">{i+1:02d}</span>
                    <img class="worklist_thumb" src="/images/{p['img']}" alt="" width="840" height="472" loading="lazy" decoding="async">
                    <span class="worklist_title">{html.escape(p['title'])}</span>
                    {tx(f"cats.{p['cat']}", 'span', 'worklist_cat text-color-secondary')}
                    <span class="worklist_host text-style-mono text-color-muted" dir="ltr">{p['host']}</span>
                    <span class="worklist_arrow">{icon("i-arrow")}</span>
                  </a>
                </li>''' for i, p in enumerate(CLIENTS))
    worklist_images = ''.join(f'<img data-src="/images/{p["img"]}" alt="" width="840" height="472" decoding="async">' for p in CLIENTS)
    sectors = ''.join(sector_item(i) for i in range(6))
    return {
        'STAGE_CARDS': ''.join(cards), 'STAGE_TOTAL': f'{len(HERO):02d}', 'STAGE_SEGS': ''.join(segs), 'STAGE_LABELS': ''.join(labels),
        'CLIENTS': client_items(False), 'CLIENTS_CLONE': client_items(True), 'FACTS': facts,
        'WORKLIST': worklist, 'WORKLIST_IMAGES': worklist_images,
        'SERVICES': services, 'DEMO_TABS': '\n'.join(tabs), 'DEMO_PANELS': ''.join(panels),
        'TEAM': ''.join(team), 'SECTORS': sectors,
    }

def cover(kind, i, title_id):
    # Bouton « + » : ouvre / ferme l'explication (clavier et écrans tactiles)
    return (f'<button type="button" class="detail_plus" data-detail-toggle aria-expanded="false" aria-controls="{kind}-panel-{i}" '
            f'aria-labelledby="{title_id}">{icon("i-plus")}</button>')

def detail_panel(kind, i):
    # Explication qui se déplie sous l'élément au survol du titre ou du texte
    label = {'services': 'details.ui.included', 'roles': 'details.ui.role', 'sectors': 'details.ui.forYou'}[kind]
    points = ''.join(f'<li>{icon("i-check")}{tx(f"details.{kind}.{i}.points.{j}")}</li>'
                     for j in range(len(TR['details'][kind][i]['points'])))
    return f'''<div class="detail_panel" id="{kind}-panel-{i}" role="region" aria-labelledby="{kind[:3]}-t-{i}">
                <div class="detail_panel-inner"><div class="detail_panel-body">
                  {tx(f"details.{kind}.{i}.intro", 'p', 'detail_intro')}
                  {tx(label, 'p', 'detail_label text-style-mono')}
                  <ul class="detail_points" role="list">{points}</ul>
                  <div class="detail_links">
                    <a href="/teklif" class="text-link">{tx("details.ui.quote")}{icon("i-arrow")}</a>
                    <a href="https://wa.me/905399662557" target="_blank" rel="noopener" class="text-link" data-detail-wa>{icon("i-whatsapp")}{tx("details.ui.whatsapp")}</a>
                  </div>
                </div></div>
              </div>'''

def sector_item(i):
    return f'''
            <li class="sectors_item has-detail" data-reveal style="--d:{i%3}">
              {cover('sectors', i, f'sec-t-{i}')}
              <span class="text-style-mono text-color-muted">{i+1:02d}</span>
              <h3 class="heading-style-h5" id="sec-t-{i}" {ti(f"sectors.{i}.title")}>{t(f"sectors.{i}.title")}</h3>
              {tx(f"sectors.{i}.text", 'p', 'text-size-small text-color-secondary')}
              {detail_panel('sectors', i)}
            </li>'''

def services_fragments():
    rows = ''.join(f'''
                <li class="service-index_item has-detail" data-reveal>
                  {cover('services', i, f'ser-t-{i}')}
                  <span class="service-index_number text-style-mono">{i+1:02d}</span>
                  <h2 class="service-index_title heading-style-h3" id="ser-t-{i}" {ti(f"services.{i}.title")}>{t(f"services.{i}.title")}</h2>
                  {tx(f"services.{i}.text", 'p', 'service-index_text text-color-secondary')}
                  {detail_panel('services', i)}
                </li>''' for i in range(len(TR['services'])))
    method = ''.join(f'''
                <li class="method_step" data-reveal style="--d:{i}">
                  <p class="text-style-mono text-color-muted">{tx("servicesPage.step")} {i+1:02d}</p>
                  {tx(f"servicesPage.steps.{i}", 'h3', 'heading-style-h4')}
                </li>''' for i in range(4))
    return {'SERVICES_ALL': rows, 'METHOD': method}

def portfolio_fragments():
    cats = []
    for p in PROJECTS:
        if p['cat'] not in cats:
            cats.append(p['cat'])
    filters = ''.join(
        f'\n                    <button type="button" class="works_filter" data-filter="{c}" aria-pressed="false">{tx(f"cats.{c}")}<span class="works_filter-count">{sum(1 for p in PROJECTS if p["cat"]==c)}</span></button>'
        for c in cats)
    tiles = []
    for i, p in enumerate(PROJECTS):
        # Travail réalisé : les étiquettes du projet, traduites
        work = '<span class="works_sep" aria-hidden="true"> · </span>'.join(tx(f"tags.{tg}") for tg in p['tags'])
        badge = f'<span class="works_badge"><span class="live-dot" aria-hidden="true"></span>{tx("portfolioPage.demoBadge")}</span>' if p.get('demo') else ''
        tiles.append(f'''
                  <a href="{p['url']}" target="_blank" rel="noopener" class="works_tile" data-cat="{p['cat']}" data-reveal style="--d:{i%3}">
                    <img src="/images/{p['img']}" alt="" loading="{'eager' if i < 3 else 'lazy'}" decoding="async" class="works_tile-image">
                    <span class="works_tile-scrim" aria-hidden="true"></span>
                    {badge}
                    <span class="works_tile-label">
                      {tx(f"cats.{p['cat']}", 'span', 'works_tile-cat')}
                      <span class="works_tile-title">{html.escape(p['title'])}</span>
                    </span>
                    <span class="works_panel">
                      {tx(f"cats.{p['cat']}", 'span', 'works_panel-cat')}
                      <span class="works_panel-title">{html.escape(p['title'])}</span>
                      <span class="works_panel-work">{work}</span>
                      <span class="works_panel-foot"><span class="works_panel-host" dir="ltr">{p['host']}</span><span class="works_panel-arrow">{icon("i-arrow")}</span></span>
                    </span>
                  </a>''')
    return {'FILTERS': filters, 'PORTFOLIO': ''.join(tiles), 'COUNT_ALL': str(len(PROJECTS))}

def options(key, empty_key=None):
    first = f'<option value="" {ti(empty_key)}>{t(empty_key)}</option>' if empty_key else f'<option value="">—</option>'
    return first + ''.join(f'<option value="{html.escape(o)}" data-i18n="{key}.{i}">{html.escape(o)}</option>' for i, o in enumerate(get(TR, key)))

def contact_fragments():
    budget = ''.join(f'''
                          <label class="choice_chip"><input type="radio" name="budgetChoice" value="{i}"><span {ti(f"contact.budgetOpts.{i}")}>{t(f"contact.budgetOpts.{i}")}</span></label>'''
                     for i in range(len(TR['contact']['budgetOpts'])))
    return {
        'SECTOR_OPTS': options('contact.sectorOpts', 'contact.sector'),
        'SERVICE_OPTS': options('contact.serviceOpts', 'contact.service'),
        'BUDGET_OPTS': budget,
    }

def opt(kind, name, value, label, price=None):
    pr = f'<span class="option_price" data-price="{price}" dir="ltr"></span>' if price else ''
    return (f'<label class="option_button"><input type="{kind}" name="{name}" value="{value}">'
            f'<span class="option_mark" aria-hidden="true"><svg class="icon"><use href="#i-check"/></svg></span>'
            f'{tx(label, "span", "option_label")}{pr}</label>')

def teklif_fragments(body):
    body = re.sub(r'\{\{OPT:(\w+):(\w+):([\w.]+)(?::(\w+))?\}\}', lambda m: opt('radio', m[1], m[2], m[3], m[4]), body)
    body = re.sub(r'\{\{CHK:(\w+):(\w+):([\w.]+)(?::(\w+))?\}\}', lambda m: opt('checkbox', m[1], m[2], m[3], m[4] or m[2]), body)
    included = ''.join(f'''
                    <div class="option_button is-locked"><span class="option_mark" aria-hidden="true"><svg class="icon"><use href="#i-check"/></svg></span>{tx(f"quote.opt.pages.{k}", "span", "option_label")}<span class="option_price">{icon("i-lock")}{tx("quote.included")}</span></div>'''
                       for k in ['home', 'about', 'services', 'contact'])
    langs = ''.join(f'\n                    {opt("checkbox", "langs", k, f"quote.opt.langs.{k}")}' for k in TR['quote']['opt']['langs'])
    return body, {
        'INCLUDED_PAGES': included, 'LANG_OPTS': langs,
        'SECTOR_OPTS_Q': options('contact.sectorOpts', 'quote.q.sectorPh'),
        'CITIES': ''.join(f'<option value="{c}"></option>' for c in CITIES),
    }

# ---------- Assemblage ----------
layout = open(os.path.join(HERE, 'layout.tpl.html')).read()
ldjson = open(os.path.join(HERE, 'ldjson.html'), encoding='utf-8').read().strip()

PAGES = [
    # (fichier, page, chemin, clé nav active, clé titre, description TR)
    ('home', 'index.html', '/', 'home', None),
    ('services', 'services/index.html', '/services', 'services', 'servicesPage.eyebrow'),
    ('sectors', 'secteurs/index.html', '/secteurs', 'sectors', 'sectorsPage.eyebrow'),
    ('portfolio', 'portfolio/index.html', '/portfolio', 'portfolio', 'portfolioPage.eyebrow'),
    ('contact', 'contact/index.html', '/contact', 'contact', 'contact.eyebrow'),
    ('teklif', 'teklif/index.html', '/teklif', 'quote', 'quote.eyebrow'),
]
DESC = {
    'home': 'Webina, dijital kreasyon ajansı: web siteleri, özel yazılımlar, CRM ve SEO.',
    'services': TR['servicesPage']['text'], 'sectors': TR['sectorsPage']['text'],
    'portfolio': TR['portfolioPage']['text'], 'contact': TR['contact']['text'], 'teklif': TR['quote']['lead'],
}

for name, out, path, navkey, titlekey in PAGES:
    body = open(os.path.join(HERE, 'pages', name + '.html')).read()
    frag = {}
    if name == 'home': frag = home_fragments()
    elif name == 'services': frag = services_fragments()
    elif name == 'sectors': frag = {'SECTORS_ALL': ''.join(sector_item(i) for i in range(len(TR['sectors'])))}
    elif name == 'portfolio': frag = portfolio_fragments()
    elif name == 'contact': frag = contact_fragments()
    elif name == 'teklif': body, frag = teklif_fragments(body)
    for k, v in frag.items():
        body = body.replace('{{' + k + '}}', v)
    title = 'Webina — Dijital Kreasyon Ajansı' if not titlekey else f'{get(TR, titlekey)} — Webina'
    page = layout.replace('{{BODY}}', body)
    page = page.replace('{{TITLE}}', html.escape(title)).replace('{{DESC}}', html.escape(DESC[name]))
    page = page.replace('{{PATH}}', path).replace('{{PAGE}}', titlekey or '')
    page = page.replace('{{LDJSON}}', ldjson if name == 'home' else '')
    first = HERO[0]['img']
    preload = (f'<link rel="preload" as="image" href="/images/{first}" imagesrcset="/images/{first} 840w, /images/{first[:-5]}-1024.webp 1024w" '
               f'imagesizes="(max-width: 767px) 80vw, 46vw" fetchpriority="high">') if name == 'home' else ''
    page = page.replace('{{PRELOAD}}', preload)
    page = page.replace('{{NAV_DESKTOP}}', nav_items('nav_link', navkey))
    page = page.replace('{{NAV_MOBILE}}', nav_items('nav_mobile-link', navkey))
    page = page.replace('{{NAV_FOOTER}}', nav_items('footer_nav-link', None))
    page = page.replace('{{SCRIPTS}}', '<script src="/js/quote.js" defer></script>' if name == 'teklif' else '')
    page = re.sub(r'\{\{t:([\w.]+)\}\}', lambda m: f'<span {ti(m.group(1))}>{t(m.group(1))}</span>', page)
    page = re.sub(r'\{\{i18n:([\w.]+)\}\}', lambda m: ti(m.group(1)), page)
    page = re.sub(r'\{\{raw:([\w.]+)\}\}', lambda m: t(m.group(1)), page)
    left = re.findall(r'\{\{[^}]*\}\}', page)
    assert not left, (name, left[:5])
    dest = os.path.join(OUT, out)
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    open(dest, 'w').write(page)

os.makedirs(os.path.join(OUT, 'js'), exist_ok=True)
os.makedirs(os.path.join(OUT, 'images'), exist_ok=True)
open(os.path.join(OUT, 'js', 'i18n.js'), 'w').write(
    '// Textes du site en 4 langues (repris du site actuel)\nwindow.WEBINA_I18N = ' + json.dumps(DICT, ensure_ascii=False, indent=1) + ';\n')
# Les images vivent dans le dossier du site : on vérifie seulement qu'il n'en manque aucune
missing = [f for f in sorted({p['img'] for p in PROJECTS} | {d['img'] for d in DEMOS})
           if not os.path.exists(os.path.join(OUT, 'images', f))]
assert not missing, ('images manquantes', missing)
print('ok')
