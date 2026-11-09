/* Webina — calculateur de devis (Teklif Al)
   Même barème (pricing.json) et même envoi (/api/contact.php, kind=quote) que le site actuel. */
(function () {
  'use strict';

  var root = document.querySelector('[data-quote]');
  if (!root) return;

  var I18N = window.WEBINA_I18N;
  var form = root.querySelector('.quote_form');
  var aside = root.querySelector('.quote_aside');
  var bar = root.querySelector('[data-quote-bar]');
  var success = root.querySelector('[data-quote-success]');
  var layout = root.querySelector('[data-quote-form]');
  var errorEl = root.querySelector('[data-quote-error]');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var LOCALES = { tr: 'tr-TR', en: 'en-US', fr: 'fr-FR', ar: 'ar-EG' };

  var pricing = null;
  var shownTotal = 0;
  var sending = false;

  function lang() { return window.webinaLang ? window.webinaLang() : 'tr'; }
  function Q() { return I18N[lang()].quote; }

  // Paramètres d'URL repris du site actuel (?ad=, ?firma=, ?sektor=, ?sehir=)
  var params = new URLSearchParams(location.search);

  var state = {
    logo: '', domain: '', domainName: '', pages: [], customPages: 0, customDesc: '',
    langs: [], booking: ['form'], content: 'ready', photos: 'own', example: '', promo: ''
  };

  function defaultLangs(l) { return l === 'en' ? ['en', 'tr'] : [l, 'en']; }

  /* ---------- Calcul ---------- */
  function converted() {
    var cur = pricing.currencyByLang[lang()] || 'USD';
    var rate = pricing.tlPer[cur] || 1;
    var step = pricing.rounding[cur] || 1;
    var t = {};
    Object.keys(pricing.prices).forEach(function (k) {
      t[k] = Math.round(pricing.prices[k] / rate / step) * step;
    });
    return { currency: cur, t: t };
  }

  function compute() {
    var c = converted(), t = c.t, lines = [{ key: 'base', amount: t.base }];
    if (state.pages.indexOf('gallery') > -1) lines.push({ key: 'gallery', amount: t.gallery });
    if (state.pages.indexOf('blog') > -1) lines.push({ key: 'blog', amount: t.blog });
    if (state.pages.indexOf('shop') > -1) lines.push({ key: 'shop', amount: t.shop });
    var cp = Math.max(0, Math.min(20, state.customPages));
    if (cp) lines.push({ key: 'extraPages', qty: cp, amount: cp * t.extraPage });
    var incLangs = pricing.includedLanguages || 2;
    var extraL = Math.max(0, state.langs.length - incLangs);
    if (extraL) lines.push({ key: 'extraLangs', qty: extraL, amount: extraL * t.extraLanguage });
    if (state.booking.indexOf('calendar') > -1) lines.push({ key: 'booking', amount: t.bookingCalendar });
    if (state.content === 'partial') lines.push({ key: 'content', amount: t.contentPartial });
    if (state.content === 'need') lines.push({ key: 'content', amount: t.contentFull });
    if (state.photos === 'pro') lines.push({ key: 'photos', amount: t.photographer });
    if (state.logo !== 'yes') lines.push({ key: 'logo', amount: 0, free: true, was: t.logoValue });
    lines.push({ key: 'domain', amount: 0, free: true, was: t.renewalYearly });
    if (state.promo && t.promoDiscount) lines.push({ key: 'promo', amount: -t.promoDiscount, code: state.promo });
    var total = lines.reduce(function (s, l) { return s + l.amount; }, 0);
    var deposit = Math.round(total * pricing.depositPercent / 100);
    return {
      currency: c.currency, t: t, lines: lines, total: total, deposit: deposit, balance: total - deposit,
      depositPercent: pricing.depositPercent, renewal: t.renewalYearly,
      includedPages: pricing.includedPages, includedLangs: incLangs
    };
  }

  // Livre turque écrite « TL » : le symbole ₺ manque dans beaucoup de polices
  function money(v, cur) {
    if (cur === 'TRY') {
      var n = new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 0 }).format(Math.abs(v));
      return (v < 0 ? '−' : '') + n + ' TL';
    }
    var s = new Intl.NumberFormat(LOCALES[lang()], { style: 'currency', currency: cur, maximumFractionDigits: 0 }).format(Math.abs(v));
    return (v < 0 ? '−' : '') + s;
  }

  /* ---------- Affichage ---------- */
  // Le total s'anime vers sa nouvelle valeur (ease-out, 500 ms)
  var tweenFrame = 0;
  function tweenTotal(to, cur) {
    cancelAnimationFrame(tweenFrame);
    var els = root.querySelectorAll('[data-total]');
    var from = shownTotal;
    if (reduceMotion.matches || from === to) {
      shownTotal = to;
      els.forEach(function (e) { e.textContent = money(to, cur); });
      return;
    }
    var t0 = performance.now();
    var step = function (now) {
      var p = Math.min((now - t0) / 500, 1);
      var v = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3)));
      shownTotal = v;
      els.forEach(function (e) { e.textContent = money(v, cur); });
      if (p < 1) tweenFrame = requestAnimationFrame(step);
    };
    tweenFrame = requestAnimationFrame(step);
  }

  function fill(name, text) {
    root.querySelectorAll('[data-fill="' + name + '"]').forEach(function (e) { e.textContent = text; });
  }

  var prevKeys = '';
  function render() {
    if (!pricing) return;
    var q = Q(), res = compute(), cur = res.currency;
    var fmt = function (v) { return money(v, cur); };

    // Prix affichés sur chaque option
    root.querySelectorAll('[data-price]').forEach(function (el) {
      var k = el.getAttribute('data-price');
      if (k === 'free') el.textContent = q.free;
      else if (k === 'included') el.textContent = q.included;
      else if (res.t[k] != null) el.textContent = res.t[k] ? '+' + fmt(res.t[k]) : q.included;
      else el.textContent = '';
    });

    fill('pagesHint', q.q.pagesHint.replace('{n}', String(res.includedPages)));
    fill('customHint', q.q.customHint.replace('{x}', fmt(res.t.extraPage)));
    fill('langsHint', q.q.langsHint.replace('{x}', fmt(res.t.extraLanguage)));
    fill('langCount', state.langs.length + ' / ' + res.includedLangs);
    root.querySelector('[data-fill="langCount"]').classList.toggle('is-over', state.langs.length > res.includedLangs);
    fill('renewal', q.sum.renewal.replace('{x}', fmt(res.renewal)));
    fill('deposit', res.depositPercent > 0
      ? q.sum.deposit.replace('{p}', String(res.depositPercent)) + ' · ' + fmt(res.deposit)
      : q.sum.noDeposit);

    // Lignes du récapitulatif : seules les nouvelles lignes s'animent
    var list = root.querySelector('[data-lines]');
    var keys = res.lines.map(function (l) { return l.key + (l.qty || ''); }).join('|');
    var old = prevKeys.split('|');
    list.innerHTML = '';
    res.lines.forEach(function (l) {
      var li = document.createElement('li');
      li.className = 'summary_line' + (l.key === 'promo' ? ' is-promo' : '');
      if (old.indexOf(l.key + (l.qty || '')) > -1) li.style.animation = 'none';
      var label = q.lines[l.key]
        .replace('{n}', String(l.key === 'base' ? res.includedPages : (l.qty || '')))
        .replace('{l}', String(res.includedLangs))
        .replace('{c}', l.code || '');
      var a = document.createElement('span'); a.className = 'summary_line-label'; a.textContent = label;
      var b = document.createElement('span'); b.className = 'summary_line-value'; b.dir = 'ltr';
      if (l.free) {
        if (l.was) { var w = document.createElement('span'); w.className = 'summary_line-was'; w.textContent = fmt(l.was); b.appendChild(w); }
        var f = document.createElement('span'); f.className = 'summary_line-free'; f.textContent = q.free; b.appendChild(f);
      } else {
        b.textContent = fmt(l.amount);
      }
      li.appendChild(a); li.appendChild(b);
      list.appendChild(li);
    });
    prevKeys = keys;

    tweenTotal(res.total, cur);
    updateReady();
    return res;
  }

  /* ---------- Saisie ---------- */
  function syncInputs() {
    form.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = state[r.name] === r.value; });
    form.querySelectorAll('input[type="checkbox"]').forEach(function (c) { c.checked = state[c.name].indexOf(c.value) > -1; });
    form.querySelector('[data-stepper-value]').textContent = String(state.customPages);
    form.querySelector('[data-step="-1"]').disabled = state.customPages === 0;
    form.querySelector('[data-stepper]').classList.toggle('is-active', state.customPages > 0);
    toggleReveal('domain', !!state.domain);
    toggleReveal('customPages', state.customPages > 0);
    var dn = form.querySelector('[name="domainName"]');
    var phKey = state.domain === 'yes' ? 'domainYesPh' : 'domainNoPh';
    dn.placeholder = Q().q[phKey];
  }
  function toggleReveal(key, show) {
    var el = form.querySelector('[data-show-if="' + key + '"]');
    if (el && el.hidden === show) el.hidden = !show;
  }

  form.addEventListener('change', function (e) {
    var el = e.target;
    if (el.type === 'radio') state[el.name] = el.value;
    else if (el.type === 'checkbox') {
      var arr = state[el.name], i = arr.indexOf(el.value);
      // Langues et moyens de contact : au moins une case reste cochée
      if (!el.checked && (el.name === 'langs' || el.name === 'booking') && arr.length === 1) { el.checked = true; return; }
      if (el.checked && i === -1) arr.push(el.value);
      if (!el.checked && i > -1) arr.splice(i, 1);
    } else return;
    syncInputs();
    render();
  });
  form.addEventListener('input', function (e) {
    var el = e.target;
    if (el.name === 'domainName' || el.name === 'customDesc' || el.name === 'example') state[el.name] = el.value;
    el.removeAttribute('aria-invalid');
    updateReady();
  });
  form.querySelectorAll('[data-step]').forEach(function (b) {
    b.addEventListener('click', function () {
      state.customPages = Math.max(0, Math.min(20, state.customPages + Number(b.dataset.step)));
      syncInputs();
      render();
    });
  });
  form.addEventListener('submit', function (e) { e.preventDefault(); });

  function contact() {
    return {
      name: form.querySelector('[name="name"]').value.trim(),
      email: form.querySelector('[name="email"]').value.trim(),
      phone: form.querySelector('[name="phone"]').value.trim(),
      company: form.querySelector('[name="company"]').value.trim(),
      sector: form.querySelector('[name="sector"]').value,
      city: form.querySelector('[name="city"]').value.trim(),
      notes: form.querySelector('[name="notes"]').value.trim(),
      website: form.querySelector('[name="website"]').value
    };
  }
  function emailOk(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  function updateReady() {
    var c = contact();
    var ready = c.name && emailOk(c.email) && c.city.length > 1;
    root.querySelectorAll('[data-fill-hint]').forEach(function (e) { e.hidden = !!ready; });
  }

  function fail(field, msg) {
    errorEl.textContent = msg;
    closeSheet();
    var target = field || document.getElementById('quote-contact');
    target.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'center' });
    if (field) { field.setAttribute('aria-invalid', 'true'); setTimeout(function () { field.focus({ preventScroll: true }); }, 300); }
  }

  function setSending(on) {
    sending = on;
    root.querySelectorAll('[data-quote-send]').forEach(function (b) {
      b.disabled = on;
      b.querySelector('[data-send-label]').textContent = on ? Q().sum.sending : Q().sum.send;
    });
  }

  function send() {
    if (sending || !pricing) return;
    var q = Q(), c = contact();
    errorEl.textContent = '';
    if (!c.name) return fail(form.querySelector('[name="name"]'), q.errName);
    if (!emailOk(c.email)) return fail(form.querySelector('[name="email"]'), q.errEmail);
    if (c.city.length < 2) return fail(form.querySelector('[name="city"]'), q.errCity);
    var res = compute();
    setSending(true);
    var details = JSON.parse(JSON.stringify(state));
    delete details._langsTouched;
    details.customPages = String(state.customPages);
    details.city = c.city;
    details.promo = state.promo;
    details.lang = lang();
    fetch('/api/contact.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'webina' },
      body: JSON.stringify({
        kind: 'quote', name: c.name, email: c.email, phone: c.phone, company: c.company,
        message: c.notes, sector: c.sector, details: details, website: c.website
      })
    })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { if (!r.ok) throw d; return d; }); })
      .then(function () {
        closeSheet();
        layout.hidden = true;
        bar.hidden = true;
        success.hidden = false;
        fill('sentP', q.sentP.replace('{e}', c.email));
        success.dataset.email = c.email;
        shownTotal = res.total;
        root.querySelectorAll('[data-total]').forEach(function (e) { e.textContent = money(res.total, res.currency); });
        window.scrollTo({ top: 0, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
        success.focus({ preventScroll: true });
      })
      .catch(function () { errorEl.textContent = q.errSend; if (window.innerWidth < 992) openSheet(); })
      .then(function () { setSending(false); });
  }
  root.querySelectorAll('[data-quote-send]').forEach(function (b) { b.addEventListener('click', send); });

  /* ---------- Récapitulatif mobile (feuille) ---------- */
  var toggle = root.querySelector('[data-bar-toggle]');
  function openSheet() { aside.classList.add('is-sheet'); toggle.setAttribute('aria-expanded', 'true'); }
  function closeSheet() { aside.classList.remove('is-sheet'); toggle.setAttribute('aria-expanded', 'false'); }
  toggle.addEventListener('click', function () {
    aside.classList.contains('is-sheet') ? closeSheet() : openSheet();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheet(); });

  /* ---------- Code de réduction ----------
     pricing.json ne contient que l'empreinte SHA-256 de chaque code ;
     le serveur refait la même vérification avant de calculer le prix. */
  var promoBox = root.querySelector('[data-promo]');
  var promoInput = promoBox.querySelector('input');
  var promoField = promoBox.querySelector('[data-promo-field]');
  var promoApplied = promoBox.querySelector('[data-promo-applied]');
  var promoError = promoBox.querySelector('[data-promo-error]');

  function sha256(text) {
    if (!window.crypto || !crypto.subtle) return Promise.reject(new Error('crypto'));
    return crypto.subtle.digest('SHA-256', new TextEncoder().encode(text)).then(function (buf) {
      return Array.prototype.map.call(new Uint8Array(buf), function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
    });
  }
  function normalise(v) { return v.trim().toUpperCase().replace(/\s+/g, ''); }
  function showPromo() {
    var on = !!state.promo;
    promoField.hidden = on;
    promoApplied.hidden = !on;
    promoBox.querySelector('[data-promo-code]').textContent = state.promo;
  }
  function applyPromo(silent) {
    var code = normalise(promoInput.value);
    promoError.textContent = '';
    if (!code || !pricing) return;
    sha256(code).then(function (h) {
      if ((pricing.promoCodes || []).indexOf(h) > -1) {
        state.promo = code;
        showPromo();
        render();
      } else if (!silent) {
        promoError.textContent = Q().promo.invalid;
        promoField.classList.remove('is-shake');
        promoField.getBoundingClientRect();
        promoField.classList.add('is-shake');
        promoInput.focus();
      }
    }).catch(function () { if (!silent) promoError.textContent = Q().promo.invalid; });
  }
  promoBox.querySelector('[data-promo-apply]').addEventListener('click', function () { applyPromo(false); });
  promoInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); applyPromo(false); } });
  promoInput.addEventListener('input', function () { promoError.textContent = ''; });
  promoBox.querySelector('[data-promo-remove]').addEventListener('click', function () {
    state.promo = '';
    promoInput.value = '';
    showPromo();
    render();
    promoInput.focus();
  });

  /* ---------- Titre personnalisé (?ad=) ---------- */
  function personalise() {
    var name = params.get('ad');
    if (!name) return;
    var h = root.ownerDocument.querySelector('[data-quote-title]');
    h.innerHTML = '';
    var a = document.createElement('span'); a.className = 'line text-color-accent'; a.textContent = name;
    var b = document.createElement('span'); b.className = 'line quote_prepared'; b.textContent = Q().preparedFor;
    var m1 = document.createElement('span'); m1.className = 'line-mask'; m1.appendChild(a);
    var m2 = document.createElement('span'); m2.className = 'line-mask'; m2.appendChild(b);
    h.appendChild(m1); h.appendChild(m2);
  }

  /* ---------- Langue ---------- */
  document.addEventListener('webina:lang', function (e) {
    // Les langues cochées par défaut suivent la langue tant que l'utilisateur n'y a pas touché
    if (!state._langsTouched) state.langs = defaultLangs(e.detail.lang);
    if (success.hidden === false && success.dataset.email) fill('sentP', Q().sentP.replace('{e}', success.dataset.email));
    setSending(sending);
    personalise();
    syncInputs();
    render();
  });
  form.addEventListener('change', function (e) { if (e.target.name === 'langs') state._langsTouched = true; }, true);

  /* ---------- Démarrage ---------- */
  state.langs = defaultLangs(lang());
  if (params.get('ad')) form.querySelector('[name="name"]').value = params.get('ad');
  if (params.get('firma')) form.querySelector('[name="company"]').value = params.get('firma');
  if (params.get('sehir')) form.querySelector('[name="city"]').value = params.get('sehir');
  if (params.get('sektor')) {
    var sel = form.querySelector('[name="sector"]');
    var val = params.get('sektor');
    if (!Array.prototype.some.call(sel.options, function (o) { return o.value === val; })) {
      var o = document.createElement('option'); o.value = val; o.textContent = val; sel.appendChild(o);
    }
    sel.value = val;
  }
  personalise();
  syncInputs();

  fetch('/pricing.json?v=' + Date.now())
    .then(function (r) { if (!r.ok) throw new Error('pricing'); return r.json(); })
    .then(function (p) {
      pricing = p;
      render();
      // Lien direct avec code : /teklif?kod=WB-XXXX-XXXX
      var kod = params.get('kod') || params.get('code');
      if (kod) { promoInput.value = kod; applyPromo(true); }
    })
    .catch(function () { errorEl.textContent = Q().errSend; });
})();
