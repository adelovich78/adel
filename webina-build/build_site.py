"""Générateur du site Webina (turc en statique + bascule 4 langues côté client).

Usage : python3 build_site.py [dossier_sortie]   (défaut : ../webina)
Écrit les pages HTML et js/i18n.js. Le CSS, le JS, les images, les polices,
l'API PHP et pricing.json vivent directement dans le dossier du site.
"""
import json, re, html, os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from details import build as build_details
import content as C

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

V2 = {
    'tr': {
        'stage': {'aria': 'Öne çıkan projeler', 'prev': 'Önceki proje', 'next': 'Sonraki proje',
                  'pause': 'Otomatik geçişi durdur', 'play': 'Otomatik geçişi başlat', 'drag': 'Sürükle', 'open': 'Aç'},
        'speed': 'Bu sayfa {t} saniyede açıldı',
        'clients': {'label': 'Bize güvenenler'},
        'manifesto': {'eyebrow': 'Yaklaşımımız',
                      'text': 'Web sitesi bir kartvizit değil, sizin için çalışan bir ekip arkadaşıdır: randevu alır, teklif hazırlar, dört dilde konuşur ve siz uyurken müşteri karşılar.'},
        'facts': ['Google PageSpeed puanı (100 üzerinden)', 'canlı sektör demosu', 'dilde yayın: TR, EN, FR, AR', 'uzmanlık, tek ekipte'],
    },
    'en': {
        'stage': {'aria': 'Featured projects', 'prev': 'Previous project', 'next': 'Next project',
                  'pause': 'Pause autoplay', 'play': 'Resume autoplay', 'drag': 'Drag', 'open': 'Open'},
        'speed': 'This page loaded in {t} s',
        'clients': {'label': 'Trusted by'},
        'manifesto': {'eyebrow': 'Our approach',
                      'text': 'A website is not a business card. It is a teammate that works for you: it books appointments, prepares quotes, speaks four languages and welcomes customers while you sleep.'},
        'facts': ['Google PageSpeed score (out of 100)', 'live industry demos', 'languages: TR, EN, FR, AR', 'disciplines in one team'],
    },
    'fr': {
        'stage': {'aria': 'Projets à la une', 'prev': 'Projet précédent', 'next': 'Projet suivant',
                  'pause': 'Mettre le défilement en pause', 'play': 'Reprendre le défilement', 'drag': 'Glisser', 'open': 'Ouvrir'},
        'speed': "Cette page s'est affichée en {t} s",
        'clients': {'label': 'Ils nous font confiance'},
        'manifesto': {'eyebrow': 'Notre approche',
                      'text': "Un site web n'est pas une carte de visite. C'est un collègue qui travaille pour vous : il prend les rendez-vous, prépare les devis, parle quatre langues et accueille vos clients pendant que vous dormez."},
        'facts': ['score Google PageSpeed (sur 100)', 'démos métier en ligne', 'langues : TR, EN, FR, AR', 'expertises dans une seule équipe'],
    },
    'ar': {
        'stage': {'aria': 'مشاريع مختارة', 'prev': 'المشروع السابق', 'next': 'المشروع التالي',
                  'pause': 'إيقاف العرض التلقائي', 'play': 'متابعة العرض التلقائي', 'drag': 'اسحب', 'open': 'افتح'},
        'speed': 'فُتحت هذه الصفحة خلال {t} ثانية',
        'clients': {'label': 'يثقون بنا'},
        'manifesto': {'eyebrow': 'نهجنا',
                      'text': 'الموقع الإلكتروني ليس بطاقة عمل، بل زميل يعمل لأجلك: يحجز المواعيد، يجهّز عروض الأسعار، يتحدث أربع لغات ويستقبل عملاءك وأنت نائم.'},
        'facts': ['نتيجة Google PageSpeed (من 100)', 'عروض تجريبية حية', 'لغات: التركية والإنجليزية والفرنسية والعربية', 'تخصصاً في فريق واحد'],
    },
}

# ==========================================================================
# Dictionnaire des 4 langues : textes conservés + nouvelle structure (content.py)
# ==========================================================================
CLIENT_CATS = ['design', 'press', 'lawacc', 'media', 'trade', 'consult', 'accounting']
DICT = {}
for l in LANGS:
    b = base[l]
    c = C.T[l]
    team = dict(b['team']); team['t2'] = strip_end(team['t2'])
    foot = dict(b['footer']); foot['title2'] = strip_end(foot['title2'])
    ct = dict(b['contact']); ct['t2'] = strip_end(ct['t2']); ct['sentT'] = strip_end(ct['sentT'])
    ct['sending'] = ct['sending'].rstrip('.…') + '…'
    # Un seul référentiel de secteurs pour les formulaires (contact, devis, CRM)
    ct['sectorOpts'] = C.FORM_SECTORS[l] + [C.OTHER[l]]
    ct['serviceOpts'] = C.SERVICE_OPTS[l]
    q = json.loads(json.dumps(quote[l])); q['t2'] = strip_end(q['t2']); q['sentT'] = strip_end(q['sentT'])
    q['sum']['sending'] = q['sum']['sending'].rstrip('.…') + '…'
    q['promo'] = PROMO[l]; q['lines']['promo'] = PROMO[l]['line']
    home = dict(c['home']); home['h1b'] = home['rotate'][0]
    det = build_details(l)
    DICT[l] = {
        'nav': c['nav'], 'cta': c['cta'], 'footer': foot, 'home': home, 'team': team,
        'services': b['services'], 'cats': {k: b['cats'][k] for k in CLIENT_CATS},
        'demo': {'items': demo[l]['items'], 'badge': demo[l]['badge']},
        'ui': ui[l], 'contact': ct, 'quote': q, 'cookie': COOKIE[l],
        'details': {'ui': det['ui'], 'roles': det['roles']},
        'groups': c['groups'], 'sec': c['sec'], 'sp': c['sp'], 'sol': c['sol'], 'price': c['price'],
        'about': c['about'], 'demos': c['demos'], 'meta': c['meta'], 'sectorsPage': c['sectorsPage'],
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

# ==========================================================================
# Données
# ==========================================================================
IMG = os.path.join(OUT, 'images')
def img_h(name, h):
    if h: return h
    from PIL import Image
    return Image.open(os.path.join(IMG, name)).height

# Secteurs (un par démo en ligne)
SECTORS = []
for key, slug, ico, group, sub, long_img, h, card in C.SECTORS:
    host = f'{sub}.webina.com.tr'
    SECTORS.append({'key': key, 'slug': slug, 'icon': ico, 'group': group, 'host': host, 'url': f'https://{host}/',
                    'long': long_img, 'h': img_h(long_img, h), 'img': card, 'page': f'/sektorler/{slug}'})
BY_KEY = {s['key']: s for s in SECTORS}

# Sites clients réels (hors démos)
CLIENTS = [
    {'title': 'Adel Benelli', 'cat': 'design', 'img': 'w-adelbenelli.webp', 'url': 'https://adelbenelli.webina.com.tr/'},
    {'title': 'NEW TR News Agency', 'cat': 'press', 'img': 'w-newtrtv-FiRvvTCk.webp', 'url': 'https://newtr.tv/'},
    {'title': 'RightExpert', 'cat': 'lawacc', 'img': 'w-right-C977AepQ.webp', 'url': 'https://www.right-expert.com/'},
    {'title': 'YDM TV', 'cat': 'media', 'img': 'w-ydm-BkQBTV4W.webp', 'url': 'https://ydm-tv.com/'},
    {'title': 'J AND J Global', 'cat': 'trade', 'img': 'w-jandj-D28PlbRX.webp', 'url': 'https://jandjglobal.net/'},
    {'title': 'Ola Andalusia', 'cat': 'consult', 'img': 'w-ola-DcZDCr04.webp', 'url': 'https://olaandalusia.com/'},
    {'title': 'Audexa', 'cat': 'accounting', 'img': 'w-audexa-yDxpiSMP.webp', 'url': 'https://audexa.com/'},
    {'title': 'New TR LIVE', 'cat': 'press', 'img': 'w-newtrlive-C2LJbWvW.webp', 'url': 'https://www.newtr.live/'},
]
for p in CLIENTS:
    p['host'] = re.sub(r'^https?://(www\.)?|/$', '', p['url'])

# Scène du héro : les vrais sites clients (même ordre que home.rotate) ; les démos ont leur propre page
HERO = [CLIENTS[i] for i in [0, 2, 1, 5, 4, 6, 3]]
HIRES = {p['img'] for p in HERO if os.path.exists(os.path.join(IMG, p['img'][:-5] + '-1024.webp'))}   # captures aussi en 1024 px (écrans haute densité)
def hero_srcset(img):
    stem = img[:-5]
    big = f', /images/{stem}-1024.webp 1024w' if img in HIRES else ''
    return f'/images/{stem}-600.webp 600w, /images/{img} 840w{big}'
BLANK = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='
HERO_SIZES = '(max-width: 767px) 82vw, 48vw'
DEPTS = ['strategy', 'design', 'dev', 'marketing', 'support']

# Tarifs : lus dans pricing.json au moment de la génération (le script de la page les relit ensuite en direct)
PRICING = json.load(open(os.path.join(OUT, 'pricing.json'), encoding='utf-8'))
def tl(*keys):
    n = sum(PRICING['prices'][k] for k in keys)
    return f"{n:,}".replace(',', '.') + ' TL'
def tpl(key, *keys):
    return html.escape(get(TR, key).replace('{x}', tl(*keys)))

# ==========================================================================
# Navigation : 7 entrées, menus déroulants courts (4 éléments + lien « tout »)
# ==========================================================================
NAV = [('home', '/'), ('solutions', '/cozumler'), ('sectors', '/sektorler'),
       ('pricing', '/teklif'), ('about', '/hakkimizda'), ('contact', '/iletisim')]

def dropdown(k):
    if k == 'solutions':
        items = [(f'/cozumler#{s}', f'sol.items.{s}.t', C.SOL_ICONS[s], False) for s in C.SOLUTIONS]
        return items, None
    if k == 'sectors':
        items = [(BY_KEY[s]['page'], f'sec.{s}.name', BY_KEY[s]['icon'], False) for s in C.FEATURED]
        return items, ('/sektorler', 'nav.allSectors')
    if k == 'demos':
        items = [(BY_KEY[s]['url'], f'sec.{s}.name', BY_KEY[s]['icon'], True) for s in C.FEATURED]
        return items, ('/demolar', 'nav.allDemos')
    return None, None

def nav_desktop(current):
    out = []
    for k, h in NAV:
        cur = ' aria-current="page"' if k == current else ''
        items, more = dropdown(k)
        link = f'<a href="{h}" class="nav_link"{cur} {ti("nav."+k)}>{t("nav."+k)}</a>'
        if not items:
            out.append(link); continue
        rows = ''.join(
            f'<a href="{u}" class="nav_drop-link"{" target=\"_blank\" rel=\"noopener\"" if ext else ""}>{icon(ic)}<span {ti(lab)}>{t(lab)}</span>{icon("i-arrow", "icon is-ext") if ext else ""}</a>'
            for u, lab, ic, ext in items)
        if more:
            rows += f'<a href="{more[0]}" class="nav_drop-more"><span {ti(more[1])}>{t(more[1])}</span>{icon("i-arrow")}</a>'
        out.append(f'<div class="nav_item">{link[:-4]}{icon("i-chevron-down", "icon nav_caret")}</a>'
                   f'<div class="nav_drop"><div class="nav_drop-inner">{rows}</div></div></div>')
    return '\n              '.join(out)

def nav_items(cls, current):
    return '\n'.join(
        f'<a href="{h}" class="{cls}"{" aria-current=\"page\"" if k == current else ""} {ti("nav."+k)}>{t("nav."+k)}</a>'
        for k, h in NAV)

# ==========================================================================
# Fragments partagés
# ==========================================================================
def clients_items(clone):
    return ''.join(f'''
              <li class="clients_item"><a href="{p['url']}" target="_blank" rel="noopener" class="clients_link"{' tabindex="-1"' if clone else ''}>{html.escape(p['title'])}</a><span class="brand-square" aria-hidden="true"></span></li>''' for p in CLIENTS)

def plan_cards():
    out = []
    for i, (k, keys) in enumerate(C.PLANS):
        items = ''.join(f'<li>{icon("i-check")}{tx(f"price.plans.{k}.items.{j}")}</li>' for j in range(len(TR['price']['plans'][k]['items'])))
        pop = f'<span class="plan_badge text-style-mono">{tx("price.popular")}</span>' if k == 'randevu' else ''
        out.append(f'''
                <article class="plan_card{' is-popular' if k == 'randevu' else ''}" data-reveal style="--d:{i}">
                  {pop}
                  <h3 class="heading-style-h4">{tx(f"price.plans.{k}.t")}</h3>
                  {tx(f"price.plans.{k}.p", 'p', 'text-color-secondary')}
                  <p class="plan_price"><span class="plan_amount" dir="ltr" data-amount="{'+'.join(keys)}">{tl(*keys)}</span>{tx("price.once", 'span', 'plan_once text-color-muted')}</p>
                  <ul class="plan_items" role="list">{items}</ul>
                  <a href="/teklif?paket={k}" class="button is-block{'' if k == 'randevu' else ' is-secondary'}">{tx("price.order")}{icon("i-arrow")}</a>
                </article>''')
    return ''.join(out)

def sector_link(s, cls='pick_link'):
    return (f'<a href="{s["page"]}" class="{cls}">{icon(s["icon"])}<span {ti("sec."+s["key"]+".name")}>{t("sec."+s["key"]+".name")}</span></a>')

def facts():
    numbers = [99, len(SECTORS), len(LANGS), len(TR['team']['roles'])]
    return ''.join(f'''
                <li class="facts_item" data-reveal style="--d:{i}"><span class="facts_number" data-count="{n}">{n}</span>{tx(f"facts.{i}", 'span', 'facts_label text-color-secondary')}</li>''' for i, n in enumerate(numbers))

def team():
    out = []
    for n, dk in enumerate(DEPTS):
        roles = [i for i, r in enumerate(TR['team']['roles']) if r['dept'] == dk]
        items = ''.join(f'''
                <li class="team_role has-detail">{cover('roles', i, f'rol-t-{i}')}<h3 class="team_role-title" id="rol-t-{i}" {ti(f"team.roles.{i}.title")}>{t(f"team.roles.{i}.title")}</h3>{tx(f"team.roles.{i}.text", 'p', 'text-size-small text-color-secondary')}{detail_panel('roles', i)}</li>''' for i in roles)
        out.append(f'''
            <div class="team_dept" data-reveal style="--d:{n}">
              <p class="team_dept-name text-style-mono">{tx(f"team.depts.{dk}")}<span class="text-color-muted">{len(roles):02d}</span></p>
              <ul class="team_roles" role="list">{items}
              </ul>
            </div>''')
    return ''.join(out)

def cover(kind, i, title_id):
    return (f'<button type="button" class="detail_plus" data-detail-toggle aria-expanded="false" aria-controls="{kind}-panel-{i}" '
            f'aria-labelledby="{title_id}">{icon("i-plus")}</button>')

def detail_panel(kind, i):
    points = ''.join(f'<li>{icon("i-check")}{tx(f"details.{kind}.{i}.points.{j}")}</li>'
                     for j in range(len(TR['details'][kind][i]['points'])))
    return f'''<div class="detail_panel" id="{kind}-panel-{i}" role="region" aria-labelledby="{kind[:3]}-t-{i}">
                <div class="detail_panel-inner"><div class="detail_panel-body">
                  {tx(f"details.{kind}.{i}.intro", 'p', 'detail_intro')}
                  {tx('details.ui.role', 'p', 'detail_label text-style-mono')}
                  <ul class="detail_points" role="list">{points}</ul>
                </div></div>
              </div>'''

# ==========================================================================
# Pages
# ==========================================================================
def home_fragments():
    cards, segs, labels = [], [], []
    for i, s in enumerate(HERO):
        near = i in (0, 1, len(HERO) - 1)
        name = s['title']
        prio = 'fetchpriority="high"' if i == 0 else 'fetchpriority="low"'
        source = (f'src="/images/{s["img"]}" srcset="{hero_srcset(s["img"])}"' if near
                  else f'src="{BLANK}" data-src="/images/{s["img"]}" data-srcset="{hero_srcset(s["img"])}"')
        cards.append(f'''
              <a href="{s['url']}" target="_blank" rel="noopener" class="stage_card" data-stage-card{'' if i == 0 else ' tabindex="-1"'}>
                <span class="stage_bar" aria-hidden="true"><span class="browser_dots"><i></i><i></i><i></i></span><span class="stage_url" dir="ltr">{s['host']}</span></span>
                <img {source} sizes="{HERO_SIZES}" alt="{html.escape(name)}" width="840" height="472" {prio} decoding="async" draggable="false">
                <span class="stage_shade" aria-hidden="true"></span>
              </a>''')
        segs.append(f'''
                  <button type="button" class="stage_seg{' is-active' if i == 0 else ''}" data-stage-go="{i}" aria-label="{html.escape(name)}"><span class="stage_seg-line"><span class="stage_seg-fill"></span></span></button>''')
        labels.append(f'''
                  <span class="stage_label-item{' is-active' if i == 0 else ''}"><span class="brand-square" aria-hidden="true"></span>{tx(f"cats.{s['cat']}", 'span', 'stage_label-cat')}<span class="stage_label-title">{html.escape(name)}</span></span>''')
    # Choix du métier : par univers
    pick = ''.join(f'''
                <div class="pick_group">
                  <p class="pick_title text-style-mono text-color-muted">{tx(f"groups.{g}")}</p>
                  <div class="pick_list">{''.join(sector_link(s) for s in SECTORS if s['group'] == g)}</div>
                </div>''' for g in C.GROUPS)
    # Étapes : un « + » ouvre le détail dans une bulle sous la carte (survol, clic ou toucher)
    def step_pop(i):
        pts = ''.join(f'<li>{icon("i-check")}{tx(f"home.stepsD.{i}.points.{j}")}</li>' for j in range(len(TR['home']['stepsD'][i]['points'])))
        return f'''<div class="detail_panel" id="steps-panel-{i}" role="region" aria-labelledby="ste-t-{i}">
                    <div class="detail_panel-inner"><div class="detail_panel-body">
                      {tx(f"home.stepsD.{i}.intro", 'p', 'detail_intro')}
                      <ul class="detail_points" role="list">{pts}</ul>
                    </div></div>
                  </div>'''
    steps = ''.join(f'''
                <li class="steps_item has-detail" data-reveal style="--d:{i}">
                  <button type="button" class="detail_plus" data-detail-toggle aria-expanded="false" aria-controls="steps-panel-{i}" aria-labelledby="ste-t-{i}">{icon("i-plus")}</button>
                  <span class="steps_number text-style-mono">{i+1:02d}</span>
                  <h3 class="heading-style-h5" id="ste-t-{i}" {ti(f"home.steps.{i}.t")}>{t(f"home.steps.{i}.t")}</h3>
                  {tx(f"home.steps.{i}.p", 'p', 'text-size-small text-color-secondary')}
                  {step_pop(i)}
                </li>''' for i in range(len(TR['home']['steps'])))
    sol = ''.join(f'''
                <li data-reveal style="--d:{i}"><a href="/cozumler#{s}" class="solution_card">{icon(C.SOL_ICONS[s], 'icon solution_icon')}{tx(f"sol.items.{s}.t", 'h3', 'heading-style-h5')}{tx(f"sol.items.{s}.p", 'p', 'text-size-small text-color-secondary')}</a></li>''' for i, s in enumerate(C.SOLUTIONS))
    return {
        'STAGE_CARDS': ''.join(cards), 'STAGE_TOTAL': f'{len(HERO):02d}', 'STAGE_SEGS': ''.join(segs), 'STAGE_LABELS': ''.join(labels),
        'PICK': pick, 'STEPS': steps, 'SOLUTION_CARDS': sol,
        'CLIENTS': clients_items(False), 'CLIENTS_CLONE': clients_items(True), 'PRICE_HOME': tpl('home.price', 'base'),
    }

def solutions_fragments():
    blocks = ''.join(f'''
                <article class="solution_block" id="{s}" data-reveal>
                  <div class="solution_head">{icon(C.SOL_ICONS[s], 'icon solution_icon')}<span class="text-style-mono text-color-muted">{i+1:02d}</span></div>
                  <div class="solution_body">
                    {tx(f"sol.items.{s}.t", 'h2', 'heading-style-h3')}
                    {tx(f"sol.items.{s}.p", 'p', 'text-size-medium text-color-secondary')}
                    <ul class="check_list is-2" role="list">{''.join(f'<li>{icon("i-check")}{tx(f"sol.items.{s}.points.{j}")}</li>' for j in range(4))}</ul>
                  </div>
                </article>''' for i, s in enumerate(C.SOLUTIONS))
    extras = ''.join(f'''
                <li class="extras_item" data-reveal style="--d:{n % 3}">{tx(f"services.{i}.title", 'h3', 'heading-style-h6')}{tx(f"services.{i}.text", 'p', 'text-size-small text-color-secondary')}</li>''' for n, i in enumerate(C.EXTRAS))
    return {'SOLUTION_BLOCKS': blocks, 'EXTRAS': extras}

def sectors_fragments():
    out = []
    for g in C.GROUPS:
        cards = ''.join(f'''
                    <div class="sector-tile" data-reveal style="--d:{n % 3}">
                      <a href="{s['page']}" class="sector-tile_link">
                        <span class="sector-tile_media"><img src="/images/{s['img']}" alt="" width="840" height="472" loading="lazy" decoding="async"></span>
                        <span class="sector-tile_body">{icon(s['icon'], 'icon sector-tile_icon')}<span class="sector-tile_text"><span class="sector-tile_name" {ti("sec."+s['key']+".title")}>{t("sec."+s['key']+".title")}</span><span class="sector-tile_demo-name text-color-muted" {ti("demo.items."+s['key']+".name")}>{t("demo.items."+s['key']+".name")}</span></span>{icon("i-arrow", "icon sector-tile_arrow")}</span>
                      </a>
                      <a href="{s['url']}" target="_blank" rel="noopener" class="sector-tile_demo"><span class="live-dot" aria-hidden="true"></span>{tx("demo.badge")}{icon("i-arrow")}</a>
                    </div>''' for n, s in enumerate(x for x in SECTORS if x['group'] == g))
        out.append(f'''
                <div class="sector-index_group">
                  <h2 class="sector-index_title text-style-mono">{tx(f"groups.{g}")}</h2>
                  <div class="sector-index_grid">{cards}
                  </div>
                </div>''')
    return {'SECTOR_INDEX': ''.join(out)}

def demos_fragments():
    filters = ''.join(
        f'\n                    <button type="button" class="works_filter" data-filter="{g}" aria-pressed="false">{tx(f"groups.{g}")}<span class="works_filter-count">{sum(1 for s in SECTORS if s["group"] == g)}</span></button>'
        for g in C.GROUPS)
    tiles = []
    for i, s in enumerate(SECTORS):
        k = s['key']
        tiles.append(f'''
                  <a href="{s['url']}" target="_blank" rel="noopener" class="works_tile" data-cat="{s['group']}" data-reveal style="--d:{i%3}">
                    <img src="/images/{s['img']}" alt="" loading="{'eager' if i < 3 else 'lazy'}" decoding="async" class="works_tile-image" width="840" height="472">
                    <span class="works_tile-scrim" aria-hidden="true"></span>
                    <span class="works_badge"><span class="live-dot" aria-hidden="true"></span>{tx("demo.badge")}</span>
                    <span class="works_tile-label">
                      {tx(f"sec.{k}.name", 'span', 'works_tile-cat')}
                      <span class="works_tile-title" {ti(f"demo.items.{k}.name")}>{t(f"demo.items.{k}.name")}</span>
                    </span>
                    <span class="works_panel">
                      {tx(f"sec.{k}.name", 'span', 'works_panel-cat')}
                      <span class="works_panel-title" {ti(f"demo.items.{k}.name")}>{t(f"demo.items.{k}.name")}</span>
                      {tx(f"demo.items.{k}.desc", 'span', 'works_panel-work')}
                      <span class="works_panel-foot"><span class="works_panel-host" dir="ltr">{s['host']}</span><span class="works_panel-arrow">{icon("i-arrow")}</span></span>
                    </span>
                  </a>''')
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
    images = ''.join(f'<img data-src="/images/{p["img"]}" alt="" width="840" height="472" decoding="async">' for p in CLIENTS)
    return {'FILTERS': filters, 'DEMO_TILES': ''.join(tiles), 'COUNT_ALL': str(len(SECTORS)), 'WORKLIST': worklist, 'WORKLIST_IMAGES': images}

def pricing_fragments():
    opts = ''.join(f'''
                    <li class="options_item">{tx(f"price.opts.{k}")}<span class="options_price" dir="ltr" data-amount="{k}">{tl(k)}</span></li>''' for k in TR['price']['opts'])
    faq = ''.join(f'''
                  <details class="faq_item"{' open' if i == 0 else ''}>
                    <summary class="faq_q">{tx(f"price.faq.{i}.q")}<span class="faq_icon" aria-hidden="true">{icon("i-plus")}</span></summary>
                    {tx(f"price.faq.{i}.a", 'p', 'faq_a text-color-secondary')}
                  </details>''' for i in range(len(TR['price']['faq'])))
    return {'PLANS': plan_cards(), 'OPTIONS': opts, 'FAQ': faq, 'PRICE_RENEWAL': tpl('price.renewal', 'renewalYearly')}

def about_fragments():
    d = demos_fragments()
    return {'FACTS': facts(), 'TEAM': team(), 'WORKLIST': d['WORKLIST'], 'WORKLIST_IMAGES': d['WORKLIST_IMAGES']}

def sector_body(s):
    k = s['key']
    body = open(os.path.join(HERE, 'pages', 'sector.html'), encoding='utf-8').read()
    body = (body.replace('sec.KEY.', f'sec.{k}.').replace('demo.items.KEY.', f'demo.items.{k}.').replace('groups.GROUP', f'groups.{s["group"]}')
                .replace('#ICON', '#' + s['icon']).replace('DEMO_URL', s['url']).replace('HOST', s['host'])
                .replace('LONG_IMG', s['long']).replace('LONG_H', str(s['h'])))
    frag = {
        'PROVIDE': ''.join(f'<li>{icon("i-check")}{tx(f"sp.provide.{j}")}</li>' for j in range(len(TR['sp']['provide']))),
        'FEATS': ''.join(f'<li>{icon("i-check")}{tx(f"demo.items.{k}.feats.{j}")}</li>' for j in range(len(TR['demo']['items'][k]['feats']))),
        'BENEFITS': ''.join(f'<li class="benefits_item" data-reveal style="--d:{j}">{tx(f"sp.benefits.{j}.t", "h3", "heading-style-h5")}{tx(f"sp.benefits.{j}.p", "p", "text-size-small text-color-secondary")}</li>' for j in range(3)),
        'OTHERS': ''.join(sector_link(o, 'chip_link') for o in SECTORS if o['key'] != k),
        'PRICE_FROM': tpl('sp.from', 'base'),
    }
    for a, v in frag.items():
        body = body.replace('{{' + a + '}}', v)
    return body

def options(key, empty_key=None):
    first = f'<option value="" {ti(empty_key)}>{t(empty_key)}</option>' if empty_key else f'<option value="">—</option>'
    return first + ''.join(f'<option value="{html.escape(o)}" data-i18n="{key}.{i}">{html.escape(o)}</option>' for i, o in enumerate(get(TR, key)))

def contact_fragments():
    budget = ''.join(f'''
                          <label class="choice_chip"><input type="radio" name="budgetChoice" value="{i}"><span {ti(f"contact.budgetOpts.{i}")}>{t(f"contact.budgetOpts.{i}")}</span></label>'''
                     for i in range(len(TR['contact']['budgetOpts'])))
    return {'SECTOR_OPTS': options('contact.sectorOpts', 'contact.sector'), 'SERVICE_OPTS': options('contact.serviceOpts', 'contact.service'), 'BUDGET_OPTS': budget}

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
    return body, {'INCLUDED_PAGES': included, 'LANG_OPTS': langs, 'SECTOR_OPTS_Q': options('contact.sectorOpts', 'quote.q.sectorPh')}

# ==========================================================================
# Assemblage
# ==========================================================================
layout = open(os.path.join(HERE, 'layout.tpl.html'), encoding='utf-8').read()
ldjson = open(os.path.join(HERE, 'ldjson.html'), encoding='utf-8').read().strip()
header_part = open(os.path.join(HERE, 'pages', '_header.part'), encoding='utf-8').read()

# (gabarit, fichier produit, chemin, entrée de menu active, clé du titre, description)
PAGES = [
    ('home', 'index.html', '/', 'home', 'meta.home', TR['home']['lead']),
    ('solutions', 'cozumler/index.html', '/cozumler', 'solutions', 'meta.solutions', TR['sol']['text']),
    ('sectors', 'sektorler/index.html', '/sektorler', 'sectors', 'meta.sectors', TR['sectorsPage']['text']),
    ('about', 'hakkimizda/index.html', '/hakkimizda', 'about', 'meta.about', TR['about']['text']),
    ('contact', 'iletisim/index.html', '/iletisim', 'contact', 'contact.eyebrow', TR['contact']['text']),
    ('teklif', 'teklif/index.html', '/teklif', 'pricing', 'quote.eyebrow', TR['quote']['lead']),
] + [(f'sector:{s["key"]}', f'sektorler/{s["slug"]}/index.html', s['page'], 'sectors', f'sec.{s["key"]}.title', TR['sec'][s['key']]['why']) for s in SECTORS]

def render(name, out, path, navkey, titlekey, desc):
    if name.startswith('sector:'):
        body = sector_body(BY_KEY[name.split(':')[1]]); frag = {}
    else:
        body = open(os.path.join(HERE, 'pages', name + '.html'), encoding='utf-8').read()
        body = re.sub(r'\{\{HEADER:(\w+)\}\}', lambda m: header_part.replace('NS.', m.group(1) + '.'), body)
        frag = {'home': home_fragments, 'solutions': solutions_fragments, 'sectors': sectors_fragments, 'demos': demos_fragments,
                'pricing': pricing_fragments, 'about': about_fragments, 'contact': contact_fragments}.get(name, lambda: {})()
        if name == 'teklif': body, frag = teklif_fragments(body)
    for k, v in frag.items():
        body = body.replace('{{' + k + '}}', v)
    title = get(TR, titlekey) if name == 'home' else f'{get(TR, titlekey)} — Webina'
    page = layout.replace('{{BODY}}', body)
    page = page.replace('{{TITLE}}', html.escape(title)).replace('{{DESC}}', html.escape(desc))
    page = page.replace('{{PATH}}', path).replace('{{PAGE}}', '' if name == 'home' else titlekey)
    page = page.replace('{{LDJSON}}', ldjson if name == 'home' else '')
    first = HERO[0]['img']
    preload = (f'<link rel="preload" as="image" href="/images/{first}" imagesrcset="{hero_srcset(first)}" '
               f'imagesizes="{HERO_SIZES}" fetchpriority="high">') if name == 'home' else ''
    page = page.replace('{{PRELOAD}}', preload)
    page = page.replace('{{NAV_DESKTOP}}', nav_desktop(navkey))
    page = page.replace('{{NAV_MOBILE}}', nav_items('nav_mobile-link', navkey))
    page = page.replace('{{NAV_FOOTER}}', nav_items('footer_nav-link', None))
    page = page.replace('{{SCRIPTS}}', '<script src="/js/tr-cities.js" defer></script><script src="/js/quote.js" defer></script>' if name == 'teklif' else '')
    page = re.sub(r'\{\{t:([\w.]+)\}\}', lambda m: f'<span {ti(m.group(1))}>{t(m.group(1))}</span>', page)
    page = re.sub(r'\{\{i18n:([\w.]+)\}\}', lambda m: ti(m.group(1)), page)
    page = re.sub(r'\{\{raw:([\w.]+)\}\}', lambda m: t(m.group(1)), page)
    left = re.findall(r'\{\{[^}]*\}\}', page)
    assert not left, (name, left[:5])
    dest = os.path.join(OUT, out)
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    open(dest, 'w', encoding='utf-8').write(page)

for p in PAGES:
    render(*p)

# Anciennes pages : remplacées par des redirections 301 (.htaccess), leurs dossiers ne sont plus livrés
import shutil
for old in ['services', 'secteurs', 'portfolio', 'contact', 'fiyatlar', 'demolar']:
    shutil.rmtree(os.path.join(OUT, old), ignore_errors=True)

open(os.path.join(OUT, 'js', 'i18n.js'), 'w', encoding='utf-8').write(
    '// Textes du site en 4 langues\nwindow.WEBINA_I18N = ' + json.dumps(DICT, ensure_ascii=False, indent=1) + ';\n')

# Plan du site
import datetime
today = datetime.date.today().isoformat()
urls = [p[2] for p in PAGES]
open(os.path.join(OUT, 'sitemap.xml'), 'w', encoding='utf-8').write(
    '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
    ''.join(f'  <url><loc>https://webina.com.tr{u if u == "/" else u + "/"}</loc><lastmod>{today}</lastmod><priority>{"1.0" if u == "/" else "0.8"}</priority></url>\n' for u in urls) +
    '</urlset>\n')

missing = sorted({s['img'] for s in SECTORS} | {s['long'] for s in SECTORS} | {p['img'] for p in CLIENTS} |
                 {s['img'][:-5] + '-600.webp' for s in HERO}) 
missing = [f for f in missing if not os.path.exists(os.path.join(IMG, f))]
assert not missing, ('images manquantes', missing)

# Numéro de version sur le CSS et le JS (cache navigateur d'une semaine)
import hashlib
def stamp_assets(path):
    page = open(path, encoding='utf-8').read()
    def ver(m):
        f = os.path.join(OUT, m.group(1).lstrip('/'))
        h = hashlib.md5(open(f, 'rb').read()).hexdigest()[:10] if os.path.exists(f) else '0'
        return f'{m.group(1)}?v={h}"'
    new = re.sub(r'(/(?:css|js)/[\w.-]+\.(?:css|js))(?:\?v=\w+)?"', ver, page)
    if new != page:
        open(path, 'w', encoding='utf-8').write(new)
for p in PAGES:
    stamp_assets(os.path.join(OUT, p[1]))
stamp_assets(os.path.join(OUT, 'admin', 'index.html'))
print('ok', len(PAGES), 'pages')
