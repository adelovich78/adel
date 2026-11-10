# -*- coding: utf-8 -*-
"""Générateur statique de la boutique Bio&Baio (eticaret.webina.com.tr).

Usage : python3 build.py [dossier_sortie]   (défaut : ../eticaret)
Les pages sont rendues en français ; js/store.js bascule la langue côté client
à partir de js/i18n.js (attributs data-i18n).
"""
import html, json, os, sys

from i18n import T, LANGS, COUNTRIES, ORIGINS

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.abspath(sys.argv[1] if len(sys.argv) > 1 else os.path.join(HERE, '..', 'eticaret'))
SITE = 'https://eticaret.webina.com.tr'
FREE_SHIP = 5000          # livraison offerte dès 50 € (centimes)
DATA = json.load(open(os.path.join(HERE, 'data.json'), encoding='utf-8'))
CATS = DATA['categories']
PRODUCTS = DATA['products']
CAT_BY_KEY = {c['key']: c for c in CATS}
FR = T['fr']
esc = html.escape


def money(cents):
    """Prix au format français (8,90 €) ; le JS reformate selon la langue."""
    return f"{cents / 100:.2f}".replace('.', ',') + ' €'


def t(key, **kw):
    s = FR[key]
    if 'amount' not in kw and '{amount}' in s:
        kw['amount'] = money(FREE_SHIP).replace(',00', '')
    for k, v in kw.items():
        s = s.replace('{' + k + '}', str(v))
    return esc(s)


def tx(key, tag='span', cls='', attrs=''):
    """Élément traduit."""
    c = f' class="{cls}"' if cls else ''
    return f'<{tag}{c} data-i18n="{key}"{attrs}>{t(key)}</{tag}>'


def img(cat, size, alt_key=None, alt='', cls='', eager=False, sizes='(max-width: 767px) 50vw, 25vw'):
    a = f' data-i18n-alt="{alt_key}"' if alt_key else ''
    load = 'fetchpriority="high"' if eager else 'loading="lazy"'
    return (f'<img src="/images/cat-{cat}-480.webp" srcset="/images/cat-{cat}-480.webp 480w, /images/cat-{cat}-1024.webp 1024w" '
            f'sizes="{sizes}" width="{size}" height="{size}" alt="{esc(alt)}"{a} class="{cls}" {load} decoding="async">')


# ---------------------------------------------------------------- icônes
ICONS = '''<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-bag" viewBox="0 0 24 24"><path d="M6 8h12l-1 12H7L6 8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
  <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 8h16M4 16h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></symbol>
  <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></symbol>
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-5-5 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-star" viewBox="0 0 24 24"><path d="m12 3.5 2.6 5.3 5.8.8-4.2 4.1 1 5.8L12 16.8l-5.2 2.7 1-5.8-4.2-4.1 5.8-.8L12 3.5Z" fill="currentColor"/></symbol>
  <symbol id="i-leaf" viewBox="0 0 24 24"><path d="M5 19c0-8 5-13 14-14 0 9-5 14-14 14Zm0 0 7-7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-box" viewBox="0 0 24 24"><path d="m4 7.5 8-4 8 4v9l-8 4-8-4v-9Zm0 0 8 4 8-4M12 11.5v9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></symbol>
  <symbol id="i-truck" viewBox="0 0 24 24"><path d="M3 6.5h11v9H3zM14 9.5h4l3 3v3h-7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7" cy="17.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="17.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
  <symbol id="i-lock" viewBox="0 0 24 24"><rect x="5" y="10.5" width="14" height="9.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
  <symbol id="i-return" viewBox="0 0 24 24"><path d="M9 7 5 11l4 4M5 11h9a5 5 0 0 1 0 10h-2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
</svg>'''


# ---------------------------------------------------------------- composants
def stars(rating):
    return (f'<span class="rating"><svg class="icon is-star" aria-hidden="true"><use href="#i-star"/></svg>'
            f'<span>{str(rating).replace(".", ",")}</span></span>')


def price_html(p, cls=''):
    old = f'<s class="price_old" data-price="{p["compareAt"]}">{money(p["compareAt"])}</s>' if p.get('compareAt') else ''
    return f'<span class="price {cls}"><span class="price_now" data-price="{p["price"]}">{money(p["price"])}</span>{old}</span>'


def badge(p):
    if not p.get('badge'):
        return ''
    return f'<span class="badge is-{p["badge"]}" data-i18n="badge.{p["badge"]}">{t("badge." + p["badge"])}</span>'


def card(p, eager=False):
    """Carte produit : lien vers la fiche + bouton d'ajout rapide (hors du lien)."""
    name = p['name']['fr']
    origin = ORIGINS[p['origin']]['fr']
    return f'''<article class="product_card" data-product="{p['id']}" data-cat="{p['category']}" data-price-sort="{p['price']}" data-pop="{p['reviews']}" data-new="{1 if p.get('badge') == 'new' else 0}" data-sale="{1 if p.get('compareAt') else 0}" data-featured="{1 if p.get('featured') else 0}">
  <a href="/p/{p['slug']}/" class="product_link">
    <div class="product_media">{img(p['category'], 480, f"products.{p['id']}.name", name, 'product_img', eager)}{badge(p)}</div>
    <div class="product_body">
      <h3 class="product_name" data-i18n="products.{p['id']}.name">{esc(name)}</h3>
      <p class="product_meta"><span>{esc(p['weight'])}</span> · <span data-i18n="origins.{p['origin']}">{esc(origin)}</span></p>
      <div class="product_foot">{price_html(p)}{stars(p['rating'])}</div>
    </div>
  </a>
  <button type="button" class="product_add" data-add="{p['id']}" aria-label="{t('p.add')}" data-i18n-aria="p.add"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><svg class="icon is-done" aria-hidden="true"><use href="#i-check"/></svg></button>
</article>'''


def grid(items, cls='product_grid', eager_first=False):
    return f'<div class="{cls}">' + ''.join(card(p, eager_first and i < 2) for i, p in enumerate(items)) + '</div>'


# ---------------------------------------------------------------- gabarit
def layout(path, title_key, desc_key, body, title_override=None, desc_override=None, page='', noindex=False):
    title = esc(title_override) if title_override else t(title_key)
    desc = esc(desc_override) if desc_override else t(desc_key)
    nav_links = ''.join(
        f'<a href="/c/{CAT_BY_KEY[k]["slug"]}/" class="nav_link" data-i18n="cat.{k}">{t("cat." + k)}</a>'
        for k in ['jam', 'oil', 'coffee', 'gifts'])
    menu_links = ''.join(
        f'<a href="/c/{c["slug"]}/" class="menu_link" data-i18n="cat.{c["key"]}">{t("cat." + c["key"])}</a>' for c in CATS)
    langs = ''.join(f'<button type="button" class="lang_btn" data-lang="{l}" aria-pressed="{"true" if l == "fr" else "false"}" lang="{l}">{l.upper()}</button>' for l in LANGS)
    foot_cats = ''.join(f'<li><a href="/c/{c["slug"]}/" data-i18n="cat.{c["key"]}">{t("cat." + c["key"])}</a></li>' for c in CATS)
    robots = '<meta name="robots" content="noindex">' if noindex else ''
    return f'''<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title data-i18n-title="{'' if title_override else title_key}">{title}</title>
  <meta name="description" content="{desc}">
  {robots}
  <meta name="theme-color" content="#0d0b08">
  <link rel="canonical" href="{SITE}{path}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="{title}">
  <meta property="og:description" content="{desc}">
  <meta property="og:image" content="{SITE}/images/hero-1088.webp">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="/favicon.png" type="image/png" sizes="64x64">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <link rel="preload" href="/fonts/fraunces-latin-opsz-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/fonts/manrope-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/css/store.css">
  <script>document.documentElement.classList.add('js');try{{var l=localStorage.getItem('bb-lang');if(l&&l!=='fr'){{document.documentElement.lang=l;document.documentElement.dir=l==='ar'?'rtl':'ltr';document.documentElement.classList.add('is-translating')}}}}catch(e){{}}</script>
</head>
<body data-page="{page}">
{ICONS}
<a href="#main" class="skip_link" data-i18n="ui.skip">{t('ui.skip')}</a>
<div class="page-wrapper">
  <div class="announce" aria-live="off">
    <div class="announce_track" data-announce>
      {tx('announce.0', 'p', 'announce_item is-active')}{tx('announce.1', 'p', 'announce_item')}{tx('announce.2', 'p', 'announce_item')}
    </div>
  </div>
  <header class="nav_component" data-nav>
    <div class="padding-global"><div class="container-large"><div class="nav_inner">
      <button type="button" class="nav_icon-btn nav_menu-btn" data-menu-open aria-label="{t('ui.menu')}" data-i18n-aria="ui.menu" aria-expanded="false" aria-controls="menu"><svg class="icon" aria-hidden="true"><use href="#i-menu"/></svg></button>
      <a href="/" class="nav_brand" aria-label="Bio&amp;Baio"><img src="/images/bio-baio-logo.webp" alt="Bio&amp;Baio" width="600" height="153" class="nav_logo"></a>
      <nav class="nav_links" aria-label="Collections">
        <a href="/boutique/" class="nav_link" data-i18n="nav.shop">{t('nav.shop')}</a>{nav_links}
      </nav>
      <div class="nav_actions">
        <div class="lang_group" role="group" aria-label="{t('ui.lang')}" data-i18n-aria="ui.lang">{langs}</div>
        <button type="button" class="nav_icon-btn nav_cart" data-cart-open aria-label="{t('nav.cart')}" data-i18n-aria="nav.cart">
          <svg class="icon" aria-hidden="true"><use href="#i-bag"/></svg><span class="cart_count" data-cart-count hidden>0</span>
        </button>
      </div>
    </div></div></div>
  </header>

  <main id="main" class="main-wrapper">
{body}
  </main>

  <footer class="footer_component">
    <div class="padding-global"><div class="container-large">
      <div class="footer_grid">
        <div class="footer_brand">
          <img src="/images/bio-baio-logo.webp" alt="Bio&amp;Baio" width="600" height="153" class="footer_logo" loading="lazy">
          {tx('footer.tagline', 'p', 'text-color-muted')}
        </div>
        <div><p class="footer_title" data-i18n="footer.shop">{t('footer.shop')}</p><ul class="footer_list">{foot_cats}</ul></div>
        <div><p class="footer_title" data-i18n="footer.help">{t('footer.help')}</p><ul class="footer_list">
          <li><span data-i18n="footer.ship">{t('footer.ship')}</span></li>
          <li><a href="mailto:contact@bio-baio.com">contact@bio-baio.com</a></li>
        </ul></div>
      </div>
      <div class="footer_bottom"><span>© 2026 Bio&amp;Baio. <span data-i18n="footer.rights">{t('footer.rights')}</span></span>
        <span class="footer_pay"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg><span data-i18n="p.secure">{t('p.secure')}</span></span></div>
    </div></div>
  </footer>
</div>

<!-- Menu mobile -->
<div class="sheet is-left" id="menu" data-sheet="menu" hidden>
  <div class="sheet_backdrop" data-sheet-close></div>
  <div class="sheet_panel" role="dialog" aria-modal="true" aria-label="{t('ui.menu')}" data-i18n-aria="ui.menu">
    <div class="sheet_head"><img src="/images/bio-baio-logo.webp" alt="Bio&amp;Baio" width="600" height="153" class="sheet_logo"><button type="button" class="nav_icon-btn" data-sheet-close aria-label="{t('ui.close')}" data-i18n-aria="ui.close"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button></div>
    <nav class="menu_list"><a href="/" class="menu_link" data-i18n="nav.home">{t('nav.home')}</a><a href="/boutique/" class="menu_link" data-i18n="nav.shop">{t('nav.shop')}</a>{menu_links}</nav>
    <div class="lang_group is-menu" role="group" aria-label="{t('ui.lang')}" data-i18n-aria="ui.lang">{langs}</div>
  </div>
</div>

<!-- Panier -->
<div class="sheet is-right" data-sheet="cart" hidden>
  <div class="sheet_backdrop" data-sheet-close></div>
  <div class="sheet_panel cart_panel" role="dialog" aria-modal="true" aria-labelledby="cart-title">
    <div class="sheet_head"><h2 class="heading-style-h5" id="cart-title" data-i18n="cart.title">{t('cart.title')}</h2><button type="button" class="nav_icon-btn" data-sheet-close aria-label="{t('ui.close')}" data-i18n-aria="ui.close"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button></div>
    <div class="cart_free" data-cart-free><p class="cart_free-text" data-cart-free-text></p><div class="cart_free-bar"><span data-cart-free-bar></span></div></div>
    <ul class="cart_lines" data-cart-lines></ul>
    <div class="cart_empty" data-cart-empty>
      {tx('cart.empty', 'p', 'text-color-muted')}
      <a href="/boutique/" class="button is-secondary" data-i18n="cart.emptyCta">{t('cart.emptyCta')}</a>
    </div>
    <div class="cart_foot" data-cart-foot>
      <div class="cart_row"><span data-i18n="cart.subtotal">{t('cart.subtotal')}</span><strong data-cart-subtotal>0,00 €</strong></div>
      <a href="/commande/" class="button is-full" data-i18n="cart.checkout">{t('cart.checkout')}</a>
      <button type="button" class="button is-text" data-sheet-close data-i18n="cart.continue">{t('cart.continue')}</button>
    </div>
  </div>
</div>
<div class="toast" data-toast role="status" aria-live="polite"></div>

<script src="/js/i18n.js" defer></script>
<script src="/js/store.js" defer></script>
</body>
</html>
'''


def section_head(title_key, sub_key=None, link=None):
    sub = tx(sub_key, 'p', 'text-color-muted section_sub') if sub_key else ''
    a = f'<a href="{link}" class="link_arrow"><span data-i18n="home.seeAll">{t("home.seeAll")}</span><svg class="icon is-arrow" aria-hidden="true"><use href="#i-arrow"/></svg></a>' if link else ''
    return f'<div class="section_head" data-reveal><div>{tx(title_key, "h2", "heading-style-h2")}{sub}</div>{a}</div>'


def collection_tiles():
    out = []
    for i, c in enumerate(CATS):
        out.append(f'''<a href="/c/{c['slug']}/" class="collection_tile" data-reveal style="--d:{i}">
  {img(c['key'], 480, None, '', 'collection_img', sizes='(max-width: 767px) 50vw, 33vw')}
  <span class="collection_label"><span data-i18n="cat.{c['key']}">{t('cat.' + c['key'])}</span><svg class="icon is-arrow" aria-hidden="true"><use href="#i-arrow"/></svg></span>
</a>''')
    return '<div class="collection_grid">' + ''.join(out) + '</div>'


# ---------------------------------------------------------------- pages
def page_home():
    gift = next(p for p in PRODUCTS if p['id'] == 'p5')
    sel = [p for p in PRODUCTS if p.get('featured')] + [p for p in PRODUCTS if not p.get('featured')]
    promises = ''.join(f'''<li class="promise_item" data-reveal style="--d:{i}"><svg class="icon" aria-hidden="true"><use href="#i-{ic}"/></svg>
  {tx(f'promise.{i}.t', 'h3', 'heading-style-h6')}{tx(f'promise.{i}.d', 'p', 'text-size-small text-color-muted')}</li>'''
                       for i, ic in enumerate(['leaf', 'box', 'truck', 'lock']))
    trust = ''.join(f'<li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>{tx(f"trust.{i}")}</li>' for i in range(3))
    body = f'''
    <section class="section_hero">
      <div class="padding-global"><div class="container-large"><div class="hero_grid">
        <div class="hero_text">
          {tx('hero.eyebrow', 'p', 'eyebrow', ' data-enter style="--d:0"')}
          <h1 class="heading-style-h1 hero_title" data-enter style="--d:1"><span data-i18n="hero.title1">{t('hero.title1')}</span> <em data-i18n="hero.titleEm">{t('hero.titleEm')}</em>,<br><span data-i18n="hero.title2">{t('hero.title2')}</span></h1>
          {tx('hero.lead', 'p', 'text-size-large text-color-muted hero_lead')}
          <div class="button-group" data-enter style="--d:3">
            <a href="/boutique/" class="button" data-i18n="hero.cta">{t('hero.cta')}</a>
            <a href="/c/coffrets-cadeaux/" class="button is-secondary" data-i18n="hero.cta2">{t('hero.cta2')}</a>
          </div>
          <ul class="trust_list" data-enter style="--d:4">{trust}</ul>
        </div>
        <figure class="hero_visual">
          <img src="/images/hero-640.webp" srcset="/images/hero-640.webp 640w, /images/hero-820.webp 820w, /images/hero-1088.webp 1088w" sizes="(max-width: 991px) 92vw, 46vw" width="1088" height="1280" alt="Confiture de figues artisanale Bio&amp;Baio" class="hero_img" fetchpriority="high" decoding="async">
          <figcaption class="hero_caps">
            <span class="hero_cap"><span class="hero_cap-k" data-i18n="hero.capOrigin">{t('hero.capOrigin')}</span><span data-i18n="hero.capOriginV">{t('hero.capOriginV')}</span></span>
            <span class="hero_cap"><span class="hero_cap-k" data-i18n="hero.capNotes">{t('hero.capNotes')}</span><span data-i18n="hero.capNotesV">{t('hero.capNotesV')}</span></span>
          </figcaption>
        </figure>
      </div></div></div>
    </section>

    <section class="section_collections">
      <div class="padding-global"><div class="container-large">
        {section_head('home.collections', 'home.collectionsSub', '/boutique/')}
        {collection_tiles()}
      </div></div>
    </section>

    <section class="section_selection" data-selection>
      <div class="padding-global"><div class="container-large">
        <div class="section_head" data-reveal>
          {tx('home.selection', 'h2', 'heading-style-h2')}
          <div class="tabs" role="tablist" aria-label="{t('home.selection')}">
            <button type="button" class="tab" role="tab" aria-selected="true" data-filter="featured" data-i18n="home.tabAll">{t('home.tabAll')}</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-filter="new" data-i18n="home.tabNew">{t('home.tabNew')}</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-filter="sale" data-i18n="home.tabSale">{t('home.tabSale')}</button>
          </div>
        </div>
        {grid(sel, 'product_grid is-selection')}
      </div></div>
    </section>

    <section class="section_gift">
      <div class="padding-global"><div class="container-large"><div class="gift_component" data-reveal>
        <div class="gift_media">{img('gifts', 1024, 'products.p5.name', gift['name']['fr'], 'gift_img', sizes='(max-width: 767px) 92vw, 46vw')}</div>
        <div class="gift_text">
          {tx('gift.eyebrow', 'p', 'eyebrow')}
          {tx('gift.title', 'h2', 'heading-style-h2')}
          {tx('gift.text', 'p', 'text-color-muted')}
          <div class="gift_price">{price_html(gift, 'is-large')}</div>
          <div class="button-group">
            <button type="button" class="button" data-add="p5"><span data-i18n="p.add">{t('p.add')}</span></button>
            <a href="/p/{gift['slug']}/" class="button is-secondary" data-i18n="gift.cta">{t('gift.cta')}</a>
          </div>
        </div>
      </div></div></div>
    </section>

    <section class="section_promise">
      <div class="padding-global"><div class="container-large"><ul class="promise_grid">{promises}</ul></div></div>
    </section>

    <section class="section_news">
      <div class="padding-global"><div class="container-small"><div class="news_component" data-reveal>
        {tx('news.title', 'h2', 'heading-style-h3')}
        {tx('news.text', 'p', 'text-color-muted')}
        <form class="news_form" data-news novalidate>
          <input type="email" name="email" class="form_input" placeholder="{t('news.ph')}" data-i18n-ph="news.ph" aria-label="{t('news.ph')}" data-i18n-aria="news.ph" autocomplete="email" required>
          <button type="submit" class="button" data-i18n="news.btn">{t('news.btn')}</button>
        </form>
        <p class="news_msg" data-news-msg role="status"></p>
      </div></div></div>
    </section>
'''
    return layout('/', 'meta.title', 'meta.desc', body, page='home')


def chips(active=None):
    out = [f'<a href="/boutique/" class="chip" {"aria-current=\"page\"" if active is None else ""} data-i18n="shop.all">{t("shop.all")}</a>']
    for c in CATS:
        cur = 'aria-current="page"' if active == c['key'] else ''
        out.append(f'<a href="/c/{c["slug"]}/" class="chip" {cur} data-i18n="cat.{c["key"]}">{t("cat." + c["key"])}</a>')
    return '<nav class="chips" aria-label="Collections">' + ''.join(out) + '</nav>'


def toolbar(n):
    opts = ''.join(f'<option value="{v}" data-i18n="shop.{k}">{t("shop." + k)}</option>' for v, k in
                   [('pop', 'sortPop'), ('asc', 'sortAsc'), ('desc', 'sortDesc'), ('new', 'sortNew')])
    return f'''<div class="shop_toolbar">
  <p class="text-color-muted shop_count" data-count data-n="{n}">{t('shop.count', n=n)}</p>
  <label class="shop_sort"><span data-i18n="shop.sort">{t('shop.sort')}</span><select class="form_select" data-sort>{opts}</select></label>
</div>'''


def page_shop():
    items = sorted(PRODUCTS, key=lambda p: -p['reviews'])
    body = f'''
    <section class="section_page-head">
      <div class="padding-global"><div class="container-large">
        {tx('shop.title', 'h1', 'heading-style-h1', ' data-enter style="--d:0"')}
        {tx('shop.lead', 'p', 'text-size-large text-color-muted')}
        {chips()}
      </div></div>
    </section>
    <section class="section_shop" data-shop>
      <div class="padding-global"><div class="container-large">
        {toolbar(len(items))}
        {grid(items, eager_first=True)}
      </div></div>
    </section>'''
    return layout('/boutique/', 'shop.title', 'meta.desc', body, title_override=f"{FR['shop.title']} — Bio&Baio", page='shop')


def page_cat(c):
    items = sorted([p for p in PRODUCTS if p['category'] == c['key']], key=lambda p: -p['reviews'])
    name = FR['cat.' + c['key']]
    body = f'''
    <section class="section_cat-hero">
      <div class="padding-global"><div class="container-large"><div class="cat_hero">
        <div class="cat_hero-text">
          <a href="/boutique/" class="link_back"><svg class="icon is-arrow is-back" aria-hidden="true"><use href="#i-arrow"/></svg><span data-i18n="cat.back">{t('cat.back')}</span></a>
          {tx('cat.' + c['key'], 'h1', 'heading-style-h1', ' data-enter style="--d:0"')}
          {tx('catd.' + c['key'], 'p', 'text-size-large text-color-muted')}
        </div>
        <div class="cat_hero-media">{img(c['key'], 1024, None, '', 'cat_hero-img', eager=True, sizes='(max-width: 767px) 92vw, 40vw')}</div>
      </div>{chips(c['key'])}</div></div>
    </section>
    <section class="section_shop" data-shop>
      <div class="padding-global"><div class="container-large">
        {toolbar(len(items))}
        {grid(items)}
      </div></div>
    </section>'''
    return layout(f'/c/{c["slug"]}/', 'cat.' + c['key'], 'catd.' + c['key'], body,
                  title_override=f"{name} — Bio&Baio", desc_override=FR['catd.' + c['key']], page='cat')


def related(p, n=4):
    same = [x for x in PRODUCTS if x['category'] == p['category'] and x['id'] != p['id']]
    others = [x for x in PRODUCTS if x['category'] != p['category'] and x['id'] != p['id']]
    return (same + others)[:n]


def page_product(p):
    cat = CAT_BY_KEY[p['category']]
    name = p['name']['fr']
    perks = ''.join(f'<li><svg class="icon" aria-hidden="true"><use href="#i-{ic}"/></svg>{tx(k)}</li>'
                    for ic, k in [('truck', 'p.ship'), ('return', 'p.return'), ('lock', 'p.secure')])
    stock = (f'<p class="stock is-in"><span class="stock_dot"></span>{tx("p.inStock")}</p>' if p['inStock']
             else f'<p class="stock is-out">{tx("p.out")}</p>')
    ld = {'@context': 'https://schema.org', '@type': 'Product', 'name': name, 'description': p['description']['fr'],
          'image': f'{SITE}/images/cat-{p["category"]}-1024.webp', 'brand': {'@type': 'Brand', 'name': 'Bio&Baio'},
          'aggregateRating': {'@type': 'AggregateRating', 'ratingValue': p['rating'], 'reviewCount': p['reviews']},
          'offers': {'@type': 'Offer', 'priceCurrency': 'EUR', 'price': f'{p["price"] / 100:.2f}',
                     'availability': 'https://schema.org/' + ('InStock' if p['inStock'] else 'OutOfStock')}}
    body = f'''
    <script type="application/ld+json">{json.dumps(ld, ensure_ascii=False)}</script>
    <section class="section_product">
      <div class="padding-global"><div class="container-large">
        <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/boutique/" data-i18n="nav.shop">{t('nav.shop')}</a><span aria-hidden="true">/</span><a href="/c/{cat['slug']}/" data-i18n="cat.{cat['key']}">{t('cat.' + cat['key'])}</a></nav>
        <div class="product_layout" data-pdp="{p['id']}">
          <div class="pdp_media">{img(p['category'], 1024, f"products.{p['id']}.name", name, 'pdp_img', eager=True, sizes='(max-width: 991px) 92vw, 50vw')}{badge(p)}</div>
          <div class="pdp_info">
            <h1 class="heading-style-h2 pdp_title" data-i18n="products.{p['id']}.name">{esc(name)}</h1>
            <p class="pdp_rating">{stars(p['rating'])}<span class="text-color-muted">· {p['reviews']} <span data-i18n="p.reviews">{t('p.reviews')}</span></span></p>
            <div class="pdp_price">{price_html(p, 'is-large')}</div>
            <p class="text-size-medium text-color-muted pdp_desc" data-i18n="products.{p['id']}.desc">{esc(p['description']['fr'])}</p>
            <dl class="pdp_specs">
              <div><dt data-i18n="p.weight">{t('p.weight')}</dt><dd>{esc(p['weight'])}</dd></div>
              <div><dt data-i18n="p.origin">{t('p.origin')}</dt><dd data-i18n="origins.{p['origin']}">{esc(ORIGINS[p['origin']]['fr'])}</dd></div>
            </dl>
            {stock}
            <div class="pdp_buy">
              <div class="qty" data-qty>
                <button type="button" class="qty_btn" data-qty-step="-1" aria-label="{t('p.less')}" data-i18n-aria="p.less"><svg class="icon" aria-hidden="true"><use href="#i-minus"/></svg></button>
                <input type="number" class="qty_input" value="1" min="1" max="20" inputmode="numeric" aria-label="{t('p.qty')}" data-i18n-aria="p.qty">
                <button type="button" class="qty_btn" data-qty-step="1" aria-label="{t('p.more')}" data-i18n-aria="p.more"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></button>
              </div>
              <button type="button" class="button is-grow" data-add="{p['id']}" data-add-qty {'disabled' if not p['inStock'] else ''}><span data-i18n="{'p.add' if p['inStock'] else 'p.out'}">{t('p.add' if p['inStock'] else 'p.out')}</span></button>
            </div>
            <ul class="pdp_perks">{perks}</ul>
          </div>
        </div>
      </div></div>
    </section>
    <section class="section_related">
      <div class="padding-global"><div class="container-large">
        {section_head('p.related')}
        {grid(related(p))}
      </div></div>
    </section>'''
    return layout(f'/p/{p["slug"]}/', None, None, body, title_override=f"{name} — Bio&Baio",
                  desc_override=p['description']['fr'], page='product')


def field(name, key, typ='text', auto='', full=False, req=True):
    cls = 'form_field is-full' if full else 'form_field'
    return f'''<label class="{cls}"><span class="form_label" data-i18n="co.{key}">{t('co.' + key)}</span>
  <input class="form_input" name="{name}" type="{typ}" autocomplete="{auto}" {'required' if req else ''}><span class="form_error" data-i18n="co.required" hidden>{t('co.required')}</span></label>'''


def page_checkout():
    countries = ''.join(f'<option value="{i}" data-i18n="countries.{i}">{esc(n)}</option>' for i, n in enumerate(COUNTRIES['fr']))
    body = f'''
    <section class="section_checkout">
      <div class="padding-global"><div class="container-large">
        {tx('co.title', 'h1', 'heading-style-h2')}
        <p class="demo_note"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>{tx('co.demo')}</p>
        <div class="checkout_layout" data-checkout>
          <form class="checkout_form" novalidate data-checkout-form>
            <fieldset class="form_group"><legend class="heading-style-h6" data-i18n="co.contact">{t('co.contact')}</legend>
              <div class="form_grid">{field('first', 'first', auto='given-name')}{field('last', 'last', auto='family-name')}{field('email', 'email', 'email', 'email')}{field('phone', 'phone', 'tel', 'tel')}</div>
            </fieldset>
            <fieldset class="form_group"><legend class="heading-style-h6" data-i18n="co.address">{t('co.address')}</legend>
              <div class="form_grid">{field('street', 'street', auto='street-address', full=True)}{field('zip', 'zip', auto='postal-code')}{field('city', 'city', auto='address-level2')}
                <label class="form_field is-full"><span class="form_label" data-i18n="co.country">{t('co.country')}</span><select class="form_select" name="country" autocomplete="country-name">{countries}</select></label></div>
            </fieldset>
            <fieldset class="form_group"><legend class="heading-style-h6" data-i18n="co.shipping">{t('co.shipping')}</legend>
              <label class="option"><input type="radio" name="ship" value="std" checked><span class="option_text" data-i18n="co.shipStd">{t('co.shipStd')}</span><span class="option_price" data-ship-std></span></label>
              <label class="option"><input type="radio" name="ship" value="exp"><span class="option_text" data-i18n="co.shipExp">{t('co.shipExp')}</span><span class="option_price" data-price="990">{money(990)}</span></label>
            </fieldset>
            <fieldset class="form_group"><legend class="heading-style-h6" data-i18n="co.payment">{t('co.payment')}</legend>
              <label class="option"><input type="radio" name="pay" value="card" checked><span class="option_text" data-i18n="co.payCard">{t('co.payCard')}</span></label>
              <label class="option"><input type="radio" name="pay" value="cod"><span class="option_text" data-i18n="co.payCod">{t('co.payCod')}</span></label>
            </fieldset>
            <p class="form_alert" data-checkout-alert hidden></p>
            <button type="submit" class="button is-full is-large" data-i18n="co.place">{t('co.place')}</button>
          </form>
          <aside class="checkout_summary">
            {tx('co.summary', 'h2', 'heading-style-h6')}
            <ul class="summary_lines" data-summary-lines></ul>
            <div class="cart_row"><span data-i18n="cart.subtotal">{t('cart.subtotal')}</span><span data-sum-sub></span></div>
            <div class="cart_row"><span data-i18n="co.shipLine">{t('co.shipLine')}</span><span data-sum-ship></span></div>
            <div class="cart_row is-total"><span data-i18n="co.total">{t('co.total')}</span><strong data-sum-total></strong></div>
          </aside>
        </div>
        <div class="checkout_done" data-checkout-done hidden>
          <span class="done_icon"><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg></span>
          {tx('co.okTitle', 'h2', 'heading-style-h2')}
          <p class="text-color-muted" data-done-text></p>
          <a href="/boutique/" class="button" data-i18n="co.okCta">{t('co.okCta')}</a>
        </div>
      </div></div>
    </section>'''
    return layout('/commande/', 'co.title', 'meta.desc', body, title_override=f"{FR['co.title']} — Bio&Baio", page='checkout', noindex=True)


def page_404():
    body = f'''
    <section class="section_404"><div class="padding-global"><div class="container-small nf_component">
      <p class="nf_code">404</p>{tx('nf.title', 'h1', 'heading-style-h2')}{tx('nf.text', 'p', 'text-color-muted')}
      <a href="/" class="button" data-i18n="nf.cta">{t('nf.cta')}</a>
    </div></div></section>'''
    return layout('/404.html', 'nf.title', 'nf.text', body, title_override='404 — Bio&Baio', page='404', noindex=True)


# ---------------------------------------------------------------- données client
def client_i18n():
    out = {}
    for l in LANGS:
        d = dict(T[l])
        for p in PRODUCTS:
            d[f'products.{p["id"]}.name'] = p['name'][l] or p['name']['fr']
            d[f'products.{p["id"]}.desc'] = p['description'][l] or p['description']['fr']
        for k, v in ORIGINS.items():
            d[f'origins.{k}'] = v[l]
        for i, n in enumerate(COUNTRIES[l]):
            d[f'countries.{i}'] = n
        out[l] = d
    catalog = {p['id']: {'slug': p['slug'], 'cat': p['category'], 'price': p['price'], 'weight': p['weight']} for p in PRODUCTS}
    return ('/* Généré par eticaret-build/build.py — ne pas modifier à la main */\n'
            f'window.BB_I18N={json.dumps(out, ensure_ascii=False, separators=(",", ":"))};\n'
            f'window.BB_CATALOG={json.dumps(catalog, separators=(",", ":"))};\n'
            f'window.BB_FREE_SHIP={FREE_SHIP};\n')


def write(rel, content):
    path = os.path.join(OUT, rel)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)


def main():
    write('index.html', page_home())
    write('boutique/index.html', page_shop())
    for c in CATS:
        write(f'c/{c["slug"]}/index.html', page_cat(c))
    for p in PRODUCTS:
        write(f'p/{p["slug"]}/index.html', page_product(p))
    write('commande/index.html', page_checkout())
    write('404.html', page_404())
    write('js/i18n.js', client_i18n())
    urls = ['/', '/boutique/'] + [f'/c/{c["slug"]}/' for c in CATS] + [f'/p/{p["slug"]}/' for p in PRODUCTS]
    write('sitemap.xml', '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
          + ''.join(f'  <url><loc>{SITE}{u}</loc></url>\n' for u in urls) + '</urlset>\n')
    write('robots.txt', f'User-agent: *\nAllow: /\nDisallow: /commande/\n\nSitemap: {SITE}/sitemap.xml\n')
    print('ok', len(urls) + 2, 'pages')


if __name__ == '__main__':
    main()
