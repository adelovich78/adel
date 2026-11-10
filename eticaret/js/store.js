/* Bio&Baio — langue, panier, filtres, commande de démonstration */
(function () {
  'use strict';
  var root = document.documentElement;
  var I18N = window.BB_I18N || {};
  var CATALOG = window.BB_CATALOG || {};
  var FREE = window.BB_FREE_SHIP || 5000;
  var STD_SHIP = 490, EXP_SHIP = 990;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  function store(k, v) {
    try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; }
  }

  /* ---------- Langue ---------- */
  var lang = 'fr';
  var LOCALES = { fr: 'fr-FR', tr: 'tr-TR', en: 'en-IE', ar: 'ar-u-nu-latn' };
  function tr(key, vars) {
    var d = I18N[lang] || I18N.fr || {};
    var s = d[key] != null ? d[key] : ((I18N.fr || {})[key] || '');
    vars = vars || {};
    if (s.indexOf('{amount}') > -1 && vars.amount == null) vars.amount = money(FREE, true);
    Object.keys(vars).forEach(function (k) { s = s.split('{' + k + '}').join(vars[k]); });
    return s;
  }
  function money(cents, round) {
    var opts = { style: 'currency', currency: 'EUR' };
    if (round && cents % 100 === 0) { opts.minimumFractionDigits = 0; opts.maximumFractionDigits = 0; }
    try { return new Intl.NumberFormat(LOCALES[lang], opts).format(cents / 100); }
    catch (e) { return (cents / 100).toFixed(2) + ' €'; }
  }
  function applyLang(l, save) {
    if (!I18N[l]) l = 'fr';
    lang = l;
    root.lang = l;
    root.dir = l === 'ar' ? 'rtl' : 'ltr';
    if (save) store('bb-lang', l);
    $$('[data-i18n]').forEach(function (el) { el.textContent = tr(el.getAttribute('data-i18n')); });
    $$('[data-i18n-aria]').forEach(function (el) { el.setAttribute('aria-label', tr(el.getAttribute('data-i18n-aria'))); });
    $$('[data-i18n-ph]').forEach(function (el) { el.setAttribute('placeholder', tr(el.getAttribute('data-i18n-ph'))); });
    $$('[data-i18n-alt]').forEach(function (el) { el.setAttribute('alt', tr(el.getAttribute('data-i18n-alt'))); });
    $$('[data-price]').forEach(function (el) { el.textContent = money(+el.getAttribute('data-price')); });
    $$('.rating span').forEach(function (el) { el.textContent = el.textContent.replace(/[.,]/, l === 'en' ? '.' : ','); });
    $$('.lang_btn').forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-lang') === l)); });
    // Titre de l'onglet
    var tEl = $('title'), tKey = tEl && tEl.getAttribute('data-i18n-title');
    if (tKey) document.title = tr(tKey);
    else { var h1 = $('h1'); if (h1 && document.body.getAttribute('data-page') !== 'home') document.title = h1.textContent.trim() + ' — Bio&Baio'; }
    updateCount();
    renderCart();
    renderCheckout();
    document.dispatchEvent(new CustomEvent('bb:lang'));
    root.classList.remove('is-translating');
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.lang_btn');
    if (b) applyLang(b.getAttribute('data-lang'), true);
  });

  /* ---------- Panier (localStorage) ---------- */
  var cart = [];
  try { cart = JSON.parse(store('bb-cart') || '[]').filter(function (l) { return CATALOG[l.id] && l.qty > 0; }); } catch (e) { cart = []; }
  function save() { store('bb-cart', JSON.stringify(cart)); }
  function count() { return cart.reduce(function (n, l) { return n + l.qty; }, 0); }
  function subtotal() { return cart.reduce(function (n, l) { return n + CATALOG[l.id].price * l.qty; }, 0); }
  function add(id, qty) {
    var line = cart.find(function (l) { return l.id === id; });
    if (line) line.qty = Math.min(20, line.qty + qty); else cart.push({ id: id, qty: qty });
    save(); updateCount(true); renderCart(); renderCheckout();
  }
  function setQty(id, qty) {
    cart = cart.map(function (l) { return l.id === id ? { id: id, qty: Math.max(0, Math.min(20, qty)) } : l; })
      .filter(function (l) { return l.qty > 0; });
    save(); updateCount(); renderCart(); renderCheckout();
  }
  function updateCount(pop) {
    var el = $('[data-cart-count]'); if (!el) return;
    var n = count();
    el.textContent = n; el.hidden = n === 0;
    if (pop && !reduceMotion.matches) { el.classList.remove('is-pop'); void el.offsetWidth; el.classList.add('is-pop'); }
  }
  function lineImg(id, size) {
    var c = CATALOG[id].cat;
    return '<img src="/images/cat-' + c + '-480.webp" width="' + size + '" height="' + size + '" alt="" loading="lazy">';
  }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  function renderCart() {
    var list = $('[data-cart-lines]'); if (!list) return;
    var empty = cart.length === 0;
    $('[data-cart-empty]').hidden = !empty;
    $('[data-cart-foot]').hidden = empty;
    $('[data-cart-free]').hidden = empty;
    list.innerHTML = cart.map(function (l) {
      var p = CATALOG[l.id];
      return '<li class="cart_line">' + lineImg(l.id, 72) +
        '<div><a href="/p/' + p.slug + '/" class="cart_line-name">' + esc(tr('products.' + l.id + '.name')) + '</a>' +
        '<p class="cart_line-meta">' + esc(p.weight) + '</p>' +
        '<div class="qty"><button type="button" class="qty_btn" data-line="' + l.id + '" data-step="-1" aria-label="' + esc(tr('p.less')) + '"><svg class="icon" aria-hidden="true"><use href="#i-minus"/></svg></button>' +
        '<span class="qty_val">' + l.qty + '</span>' +
        '<button type="button" class="qty_btn" data-line="' + l.id + '" data-step="1" aria-label="' + esc(tr('p.more')) + '"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></button></div></div>' +
        '<div class="cart_line-side"><strong>' + money(p.price * l.qty) + '</strong><button type="button" class="cart_remove" data-remove="' + l.id + '">' + esc(tr('cart.remove')) + '</button></div></li>';
    }).join('');
    var sub = subtotal();
    $('[data-cart-subtotal]').textContent = money(sub);
    var left = FREE - sub;
    $('[data-cart-free-text]').textContent = left > 0 ? tr('cart.freeLeft', { left: money(left) }) : tr('cart.freeOk');
    $('[data-cart-free-bar]').style.setProperty('--p', Math.min(1, sub / FREE).toFixed(3));
  }
  document.addEventListener('click', function (e) {
    var step = e.target.closest('[data-line]');
    if (step) { var id = step.getAttribute('data-line'); var l = cart.find(function (x) { return x.id === id; }); if (l) setQty(id, l.qty + +step.getAttribute('data-step')); return; }
    var rm = e.target.closest('[data-remove]');
    if (rm) { setQty(rm.getAttribute('data-remove'), 0); return; }
    var btn = e.target.closest('[data-add]');
    if (!btn || btn.disabled) return;
    var qty = 1;
    if (btn.hasAttribute('data-add-qty')) { var inp = $('.qty_input'); qty = Math.max(1, Math.min(20, parseInt(inp && inp.value, 10) || 1)); }
    add(btn.getAttribute('data-add'), qty);
    confirmAdd(btn);
  });

  // Retour visuel : bouton « Ajouté » + toast (le panier ne s'ouvre pas tout seul)
  var toastEl = $('[data-toast]'), toastTimer;
  function toast(msg) {
    if (!toastEl) return;
    toastEl.textContent = msg; toastEl.classList.add('is-on');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2200);
  }
  function confirmAdd(btn) {
    var id = btn.getAttribute('data-add');
    toast(tr('p.added') + ' · ' + tr('products.' + id + '.name'));
    btn.classList.add('is-done');
    var label = btn.querySelector('[data-i18n]');
    if (label) { label.setAttribute('data-i18n', 'p.added'); label.textContent = tr('p.added'); }
    clearTimeout(btn._t);
    btn._t = setTimeout(function () {
      btn.classList.remove('is-done');
      if (label) { label.setAttribute('data-i18n', 'p.add'); label.textContent = tr('p.add'); }
    }, 1400);
  }

  /* ---------- Panneaux (panier, menu) ---------- */
  var lastFocus = null;
  function openSheet(name) {
    var s = $('[data-sheet="' + name + '"]'); if (!s) return;
    lastFocus = document.activeElement;
    s.hidden = false;
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () { requestAnimationFrame(function () { s.classList.add('is-open'); }); });
    var f = s.querySelector('[data-sheet-close]:not(.sheet_backdrop)');
    if (f) setTimeout(function () { f.focus(); }, 60);
    if (name === 'menu') $('[data-menu-open]').setAttribute('aria-expanded', 'true');
  }
  function closeSheet(s) {
    if (!s || s.hidden) return;
    s.classList.remove('is-open');
    document.body.style.overflow = '';
    if (s.getAttribute('data-sheet') === 'menu') $('[data-menu-open]').setAttribute('aria-expanded', 'false');
    var done = function () { if (!s.classList.contains('is-open')) s.hidden = true; };
    if (reduceMotion.matches) done(); else setTimeout(done, 400);
    if (lastFocus) lastFocus.focus();
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-cart-open]')) openSheet('cart');
    else if (e.target.closest('[data-menu-open]')) openSheet('menu');
    else if (e.target.closest('[data-sheet-close]')) closeSheet(e.target.closest('[data-sheet]'));
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') $$('.sheet.is-open').forEach(closeSheet);
  });

  /* ---------- Quantité sur la fiche produit ---------- */
  $$('[data-qty]').forEach(function (q) {
    var inp = q.querySelector('.qty_input');
    q.addEventListener('click', function (e) {
      var b = e.target.closest('[data-qty-step]'); if (!b) return;
      inp.value = Math.max(1, Math.min(20, (parseInt(inp.value, 10) || 1) + +b.getAttribute('data-qty-step')));
    });
    inp.addEventListener('change', function () { inp.value = Math.max(1, Math.min(20, parseInt(inp.value, 10) || 1)); });
  });

  /* ---------- Sélection de la semaine (onglets) ---------- */
  var sel = $('[data-selection]');
  if (sel) {
    var cards = $$('.product_card', sel);
    var showFilter = function (f, animate) {
      var shown = 0;
      cards.forEach(function (c) {
        var ok = c.getAttribute('data-' + f) === '1' && shown < 4;
        if (ok) shown++;
        c.classList.toggle('is-hidden', !ok);
        c.classList.remove('is-in');
        if (ok && animate && !reduceMotion.matches) { c.style.setProperty('--i', shown - 1); void c.offsetWidth; c.classList.add('is-in'); }
      });
    };
    showFilter('featured', false);
    $$('[data-filter]', sel).forEach(function (t) {
      t.addEventListener('click', function () {
        $$('[data-filter]', sel).forEach(function (x) { x.setAttribute('aria-selected', String(x === t)); });
        showFilter(t.getAttribute('data-filter'), true);
      });
    });
  }

  /* ---------- Tri de la boutique ---------- */
  var shop = $('[data-shop]');
  if (shop) {
    var gridEl = $('.product_grid', shop);
    var countEl = $('[data-count]', shop);
    var n = +countEl.getAttribute('data-n');
    countEl.removeAttribute('data-i18n');
    var setCount = function () { countEl.textContent = n === 1 ? tr('shop.count1') : tr('shop.count', { n: n }); };
    setCount();
    document.addEventListener('bb:lang', setCount);
    $('[data-sort]', shop).addEventListener('change', function (e) {
      var v = e.target.value;
      var items = $$('.product_card', gridEl);
      items.sort(function (a, b) {
        var pa = +a.getAttribute('data-price-sort'), pb = +b.getAttribute('data-price-sort');
        if (v === 'asc') return pa - pb;
        if (v === 'desc') return pb - pa;
        if (v === 'new') return (+b.getAttribute('data-new')) - (+a.getAttribute('data-new')) || (+b.getAttribute('data-pop')) - (+a.getAttribute('data-pop'));
        return (+b.getAttribute('data-pop')) - (+a.getAttribute('data-pop'));
      });
      items.forEach(function (it, i) {
        gridEl.appendChild(it);
        it.classList.remove('is-in');
        if (!reduceMotion.matches) { it.style.setProperty('--i', i); void it.offsetWidth; it.classList.add('is-in'); }
      });
    });
  }

  /* ---------- Commande (démonstration) ---------- */
  var co = $('[data-checkout]');
  function shipCost() {
    var exp = co && co.querySelector('input[name="ship"][value="exp"]').checked;
    if (exp) return EXP_SHIP;
    return subtotal() >= FREE ? 0 : STD_SHIP;
  }
  function renderCheckout() {
    if (!co) return;
    var sub = subtotal();
    $('[data-summary-lines]').innerHTML = cart.map(function (l) {
      var p = CATALOG[l.id];
      return '<li class="summary_line">' + lineImg(l.id, 56) + '<span>' + esc(tr('products.' + l.id + '.name')) +
        ' <span class="summary_qty">× ' + l.qty + '</span></span><span>' + money(p.price * l.qty) + '</span></li>';
    }).join('') || '<li class="text-color-muted">' + esc(tr('cart.empty')) + '</li>';
    var stdEl = $('[data-ship-std]');
    stdEl.textContent = sub >= FREE ? tr('co.free') : money(STD_SHIP);
    var ship = shipCost();
    $('[data-sum-sub]').textContent = money(sub);
    $('[data-sum-ship]').textContent = ship === 0 ? tr('co.free') : money(ship);
    $('[data-sum-total]').textContent = money(sub + ship);
  }
  if (co) {
    var form = $('[data-checkout-form]');
    var alertEl = $('[data-checkout-alert]');
    form.addEventListener('change', renderCheckout);
    form.addEventListener('input', function (e) {
      var f = e.target.closest('.form_field');
      if (f && f.classList.contains('is-invalid') && e.target.checkValidity()) { f.classList.remove('is-invalid'); f.querySelector('.form_error').hidden = true; }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      alertEl.hidden = true;
      if (!cart.length) { alertEl.textContent = tr('co.emptyCart'); alertEl.hidden = false; return; }
      var first = null;
      $$('.form_input[required]', form).forEach(function (inp) {
        var ok = inp.value.trim() !== '' && inp.checkValidity();
        var f = inp.closest('.form_field');
        f.classList.toggle('is-invalid', !ok);
        f.querySelector('.form_error').hidden = ok;
        if (!ok && !first) first = inp;
      });
      if (first) { first.focus(); return; }
      var num = 'BB-' + Date.now().toString(36).slice(-6).toUpperCase();
      cart = []; save(); updateCount(); renderCart();
      co.hidden = true;
      $('.demo_note').hidden = true;
      $('h1', co.parentNode).hidden = true;
      var done = $('[data-checkout-done]');
      done.hidden = false;
      var txt = $('[data-done-text]');
      txt.setAttribute('data-num', num);
      txt.textContent = tr('co.okText', { n: num });
      window.scrollTo({ top: 0, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
    });
    document.addEventListener('bb:lang', function () {
      var txt = $('[data-done-text]');
      if (txt && txt.getAttribute('data-num')) txt.textContent = tr('co.okText', { n: txt.getAttribute('data-num') });
    });
  }

  /* ---------- Newsletter (démonstration) ---------- */
  var news = $('[data-news]');
  if (news) {
    news.addEventListener('submit', function (e) {
      e.preventDefault();
      var inp = news.querySelector('input'), msg = $('[data-news-msg]');
      var ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(inp.value.trim());
      msg.classList.toggle('is-error', !ok);
      msg.setAttribute('data-i18n', ok ? 'news.ok' : 'news.err');
      msg.textContent = tr(ok ? 'news.ok' : 'news.err');
      if (ok) inp.value = '';
    });
  }

  /* ---------- Bandeau d'annonces ---------- */
  var ann = $$('[data-announce] .announce_item');
  if (ann.length > 1 && !reduceMotion.matches) {
    var ai = 0;
    setInterval(function () {
      if (document.hidden) return;
      ann[ai].classList.remove('is-active');
      ai = (ai + 1) % ann.length;
      ann[ai].classList.add('is-active');
    }, 4000);
  }

  /* ---------- Navigation ---------- */
  var nav = $('[data-nav]');
  var onScroll = function () { nav.classList.toggle('is-scrolled', window.scrollY > 8); };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  var path = location.pathname;
  $$('.nav_link').forEach(function (a) {
    var h = a.getAttribute('href');
    if (path === h || (h !== '/boutique/' && path.indexOf(h) === 0)) a.setAttribute('aria-current', 'page');
  });

  /* ---------- Apparitions ---------- */
  var reveals = $$('[data-reveal]');
  if ('IntersectionObserver' in window && !reduceMotion.matches) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ---------- Démarrage ---------- */
  var params = new URLSearchParams(location.search);
  applyLang(params.get('lang') || store('bb-lang') || 'fr', !!params.get('lang'));
  requestAnimationFrame(function () { root.classList.add('is-loaded'); });
})();
