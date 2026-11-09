/* Webina — interactions de la page d'accueil */
(function () {
  'use strict';

  var root = document.documentElement;
  var I18N = window.WEBINA_I18N || {};
  var LANGS = ['tr', 'en', 'fr', 'ar'];
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

  function store(key, value) {
    try {
      if (value === undefined) return localStorage.getItem(key);
      localStorage.setItem(key, value);
    } catch (e) { return null; }
  }

  /* ---------- Langue ---------- */
  function lookup(dict, key) {
    return key.split('.').reduce(function (o, k) { return o == null ? o : o[k]; }, dict);
  }

  var currentLang = 'tr';
  window.webinaLang = function () { return currentLang; };

  function setLang(lang, persist) {
    if (LANGS.indexOf(lang) === -1 || !I18N[lang]) lang = 'tr';
    var dict = I18N[lang];
    currentLang = lang;
    root.lang = lang;
    root.dir = lang === 'ar' ? 'rtl' : 'ltr';

    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      var v = lookup(dict, el.getAttribute('data-i18n'));
      if (typeof v === 'string') el.textContent = v;
    });
    document.querySelectorAll('[data-i18n-aria]').forEach(function (el) {
      var v = lookup(dict, el.getAttribute('data-i18n-aria'));
      if (typeof v === 'string') el.setAttribute('aria-label', v);
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
      var v = lookup(dict, el.getAttribute('data-i18n-placeholder'));
      if (typeof v === 'string') el.setAttribute('placeholder', v);
    });
    document.querySelectorAll('.lang_button').forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.dataset.lang === lang));
    });
    var pageKey = root.getAttribute('data-page');
    var pageTitle = pageKey ? lookup(dict, pageKey) : null;
    document.title = pageTitle ? pageTitle + ' — Webina' : 'Webina — ' + dict.footer.rights;

    if (persist) store('webina-lang', lang);
    document.dispatchEvent(new CustomEvent('webina:lang', { detail: { lang: lang, dict: dict } }));
    // Les largeurs changent avec la langue : on recale les indicateurs
    requestAnimationFrame(function () {
      syncNavIndicator(activeNavLink(), false);
      if (demos) demos.syncClip(false);
      if (typeof works !== 'undefined' && works) works.sync();
    });
  }

  document.querySelectorAll('.lang_button').forEach(function (b) {
    b.addEventListener('click', function () { setLang(b.dataset.lang, true); });
  });

  /* ---------- Thème ---------- */
  var themeBtn = document.querySelector('[data-theme-toggle]');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var isDark = root.getAttribute('data-theme') === 'dark' ||
        (!root.getAttribute('data-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
      var next = isDark ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      store('webina-theme', next);
    });
  }

  /* ---------- Navigation : indicateur glissant ---------- */
  var navMenu = document.querySelector('.nav_menu');
  var indicator = document.querySelector('.nav_indicator');

  function activeNavLink() {
    return navMenu && navMenu.querySelector('.nav_link[aria-current="page"]');
  }

  // L'indicateur fait 100px de large : on le place et l'étire avec transform seulement
  function syncNavIndicator(link, animate) {
    if (!indicator || !navMenu) return;
    if (!link) { indicator.style.setProperty('--s', '0'); return; }
    if (animate === false) indicator.style.transition = 'none';
    var pad = 12;
    indicator.style.setProperty('--x', (link.offsetLeft + pad) + 'px');
    indicator.style.setProperty('--s', String((link.offsetWidth - pad * 2) / 100));
    if (animate === false) {
      indicator.getBoundingClientRect();
      indicator.style.transition = '';
    }
  }

  if (navMenu) {
    navMenu.querySelectorAll('.nav_link').forEach(function (a) {
      a.addEventListener('pointerenter', function () { if (finePointer.matches) syncNavIndicator(a); });
      a.addEventListener('focus', function () { syncNavIndicator(a); });
    });
    navMenu.addEventListener('pointerleave', function () { syncNavIndicator(activeNavLink()); });
    navMenu.addEventListener('focusout', function (e) {
      if (!navMenu.contains(e.relatedTarget)) syncNavIndicator(activeNavLink());
    });
  }

  /* ---------- Navigation : bordure au défilement ---------- */
  var nav = document.querySelector('[data-nav]');
  var ticking = false;
  window.addEventListener('scroll', function () {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 8);
      ticking = false;
    });
  }, { passive: true });

  /* ---------- Menu mobile ---------- */
  var toggle = document.querySelector('.nav_toggle');
  var mobile = document.getElementById('nav-mobile');

  function setMenu(open) {
    if (!toggle || !mobile) return;
    toggle.setAttribute('aria-expanded', String(open));
    if (open) {
      mobile.hidden = false;
      mobile.classList.add('is-entering');
      requestAnimationFrame(function () {
        requestAnimationFrame(function () { mobile.classList.remove('is-entering'); });
      });
    } else {
      // Sortie plus rapide que l'entrée
      mobile.classList.add('is-entering');
      setTimeout(function () {
        if (toggle.getAttribute('aria-expanded') === 'false') mobile.hidden = true;
      }, 160);
    }
  }
  if (toggle) {
    toggle.addEventListener('click', function () {
      setMenu(toggle.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setMenu(false);
        toggle.focus();
      }
    });
    window.matchMedia('(min-width: 992px)').addEventListener('change', function (m) {
      if (m.matches) { toggle.setAttribute('aria-expanded', 'false'); mobile.hidden = true; }
    });
  }

  /* ---------- Héro : pile d'épreuves qui défile ----------
     Toutes les 4,8 s l'épreuve de devant glisse vers le bas et disparaît,
     les autres avancent d'une place, puis elle revient au fond de la pile. */
  var deck = (function () {
    var wrap = document.querySelector('[data-hero-deck]');
    if (!wrap) return null;
    var cards = Array.prototype.slice.call(wrap.querySelectorAll('.hero_proof'));
    var caption = wrap.querySelector('.hero_caption');
    var idxEl = wrap.querySelector('[data-hero-index]');
    var hostEl = wrap.querySelector('[data-hero-host]');
    var DUR = 4800;
    var timer = null, started = 0, remaining = DUR, paused = false, visible = true, front = 0;
    wrap.style.setProperty('--deck-dur', DUR + 'ms');

    function restartBar() {
      caption.classList.remove('is-running');
      caption.getBoundingClientRect();
      caption.classList.add('is-running');
    }
    function updateCaption() {
      var card = cards[front];
      idxEl.textContent = String(front + 1).padStart(2, '0');
      hostEl.classList.add('is-swapping');
      setTimeout(function () {
        hostEl.textContent = card.dataset.host;
        hostEl.classList.remove('is-swapping');
      }, 200);
    }
    function advance() {
      cards.forEach(function (c) {
        var pos = c.getAttribute('data-pos');
        if (pos === '0') {
          c.setAttribute('data-pos', 'out');
          c.setAttribute('tabindex', '-1');
          // Une fois sortie, elle reprend sa place au fond sans transition
          setTimeout(function () {
            c.classList.add('is-snap');
            c.setAttribute('data-pos', '3');
            c.getBoundingClientRect();
            c.classList.remove('is-snap');
          }, 820);
        } else if (pos !== 'out') {
          var next = String(Number(pos) - 1);
          c.setAttribute('data-pos', next);
          if (next === '0') { c.removeAttribute('tabindex'); front = cards.indexOf(c); }
        }
      });
      updateCaption();
      remaining = DUR;
      restartBar();
      schedule();
    }
    function schedule() {
      clearTimeout(timer);
      if (paused || !visible || document.hidden || reduceMotion.matches) {
        caption.classList.add('is-paused');
        return;
      }
      caption.classList.remove('is-paused');
      started = Date.now();
      timer = setTimeout(advance, remaining);
    }
    function pause() {
      if (timer) remaining = Math.max(400, remaining - (Date.now() - started));
      clearTimeout(timer); timer = null;
      caption.classList.add('is-paused');
    }
    var stack = wrap.querySelector('.hero_stack');
    stack.addEventListener('pointerenter', function () { if (finePointer.matches) { paused = true; pause(); } });
    stack.addEventListener('pointerleave', function () { if (paused) { paused = false; schedule(); } });
    stack.addEventListener('focusin', function () { paused = true; pause(); });
    stack.addEventListener('focusout', function (e) { if (!stack.contains(e.relatedTarget)) { paused = false; schedule(); } });
    document.addEventListener('visibilitychange', function () { document.hidden ? pause() : schedule(); });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) {
        visible = en[0].isIntersecting;
        visible ? schedule() : pause();
      }, { threshold: 0.25 }).observe(wrap);
    }
    return {
      start: function () {
        if (reduceMotion.matches) return;
        // Démarre après l'entrée de la pile
        setTimeout(function () { restartBar(); schedule(); }, 1400);
      }
    };
  })();

  /* ---------- Portfolyo : secteurs + vignettes ---------- */
  var works = (function () {
    var list = document.querySelector('.works_filters');
    if (!list) return null;
    var buttons = Array.prototype.slice.call(list.querySelectorAll('.works_filter'));
    var tiles = Array.prototype.slice.call(document.querySelectorAll('.works_tile'));
    var indicator = document.createElement('span');
    indicator.className = 'works_filter-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    list.insertBefore(indicator, list.firstChild);

    // Le surlignage glisse vers le secteur choisi (transform uniquement)
    function moveIndicator(animate) {
      var active = list.querySelector('[aria-pressed="true"]');
      if (!active) return;
      if (animate === false) indicator.style.transition = 'none';
      indicator.style.setProperty('--y', active.offsetTop + 'px');
      if (animate === false) { indicator.getBoundingClientRect(); indicator.style.transition = ''; }
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        var f = b.dataset.filter;
        buttons.forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        moveIndicator(true);
        var n = 0;
        tiles.forEach(function (t) {
          var show = f === 'all' || t.dataset.cat === f;
          t.classList.remove('is-filter-in', 'is-open');
          t.hidden = !show;
          if (show) {
            t.classList.add('is-in');
            if (!reduceMotion.matches) {
              t.style.setProperty('--n', String(Math.min(n++, 8)));
              t.getBoundingClientRect();
              t.classList.add('is-filter-in');
            }
          }
        });
        if (window.innerWidth < 992) b.scrollIntoView({ block: 'nearest', inline: 'center', behavior: reduceMotion.matches ? 'auto' : 'smooth' });
      });
    });

    // Écran tactile : 1er appui ouvre le panneau, 2e appui ouvre le site
    tiles.forEach(function (t) {
      t.addEventListener('click', function (e) {
        if (finePointer.matches) return;
        if (!t.classList.contains('is-open')) {
          e.preventDefault();
          tiles.forEach(function (x) { if (x !== t) x.classList.remove('is-open'); });
          t.classList.add('is-open');
        }
      });
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.works_tile')) tiles.forEach(function (x) { x.classList.remove('is-open'); });
    });

    window.addEventListener('resize', function () { moveIndicator(false); });
    moveIndicator(false);
    return { sync: function () { moveIndicator(false); } };
  })();

  /* ---------- Panneau détaillé (services, équipe, secteurs) ----------
     S'ouvre depuis le côté (ou le bas sur mobile), se ferme par ×, Échap,
     clic sur le fond ou glissé vers le bas. Les flèches passent à l'élément voisin. */
  (function () {
    var box = document.querySelector('[data-drawer]');
    if (!box) return;
    var panel = box.querySelector('.drawer_panel');
    var body = box.querySelector('[data-drawer-body]');
    var current = null, lastFocus = null, closing = null;
    var WA = 'https://wa.me/905399662557';

    function triggers(kind) {
      return Array.prototype.slice.call(document.querySelectorAll('.detail_cover[data-detail="' + kind + '"]'));
    }
    function content(kind, i) {
      var d = I18N[currentLang], det = d.details, item, eyebrow;
      if (kind === 'services') { item = d.services[i]; eyebrow = d.nav.services; }
      else if (kind === 'sectors') { item = d.sectors[i]; eyebrow = d.nav.sectors; }
      else { item = d.team.roles[i]; eyebrow = d.team.depts[item.dept]; }
      return {
        eyebrow: eyebrow, title: item.title, intro: det[kind][i].intro, points: det[kind][i].points,
        label: kind === 'roles' ? det.ui.role : (kind === 'sectors' ? det.ui.forYou : det.ui.included)
      };
    }
    function fill() {
      var c = content(current.kind, current.index);
      box.querySelector('[data-drawer-eyebrow]').textContent = c.eyebrow;
      box.querySelector('[data-drawer-number]').textContent = String(current.index + 1).padStart(2, '0');
      box.querySelector('[data-drawer-title]').textContent = c.title;
      box.querySelector('[data-drawer-intro]').textContent = c.intro;
      box.querySelector('[data-drawer-label]').textContent = c.label;
      var ul = box.querySelector('[data-drawer-points]');
      ul.innerHTML = '';
      c.points.forEach(function (pt) {
        var li = document.createElement('li');
        li.className = 'drawer_point';
        li.innerHTML = '<svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>';
        var sp = document.createElement('span'); sp.textContent = pt; li.appendChild(sp);
        ul.appendChild(li);
      });
      var msg = { tr: 'Merhaba, «{x}» hakkında bilgi almak istiyorum.', en: 'Hello, I would like to know more about “{x}”.',
                  fr: 'Bonjour, je souhaite en savoir plus sur « {x} ».', ar: 'مرحباً، أود معرفة المزيد عن «{x}».' }[currentLang];
      box.querySelector('[data-drawer-wa]').href = WA + '?text=' + encodeURIComponent(msg.replace('{x}', c.title));
    }
    function open(kind, index, from) {
      current = { kind: kind, index: index, total: triggers(kind).length };
      lastFocus = from || document.activeElement;
      clearTimeout(closing);
      fill();
      box.hidden = false;
      box.classList.add('is-closed');
      box.classList.remove('is-leaving');
      document.documentElement.style.overflow = 'hidden';
      requestAnimationFrame(function () { requestAnimationFrame(function () { box.classList.remove('is-closed'); }); });
      panel.focus({ preventScroll: true });
    }
    function close() {
      if (box.hidden) return;
      box.classList.add('is-leaving', 'is-closed');
      panel.style.transform = '';
      document.documentElement.style.overflow = '';
      closing = setTimeout(function () { box.hidden = true; box.classList.remove('is-leaving'); }, reduceMotion.matches ? 0 : 300);
      if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
    }
    function step(dir) {
      if (!current) return;
      current.index = (current.index + dir + current.total) % current.total;
      if (reduceMotion.matches) { fill(); return; }
      body.style.setProperty('--swap-dir', String(dir * (root.dir === 'rtl' ? -1 : 1)));
      body.classList.add('is-swapping');
      setTimeout(function () {
        fill();
        body.scrollTop = 0;
        body.style.setProperty('--swap-dir', String(-dir * (root.dir === 'rtl' ? -1 : 1)));
        requestAnimationFrame(function () { body.classList.remove('is-swapping'); });
      }, 180);
    }

    document.addEventListener('click', function (e) {
      var t = e.target.closest('.detail_cover');
      if (t) { open(t.dataset.detail, Number(t.dataset.index), t); return; }
      if (e.target.closest('[data-drawer-close]')) close();
      var st = e.target.closest('[data-drawer-step]');
      if (st) step(Number(st.dataset.drawerStep));
    });
    document.addEventListener('keydown', function (e) {
      if (box.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        if (/input|textarea/i.test(document.activeElement.tagName)) return;
        var d = e.key === 'ArrowRight' ? 1 : -1;
        step(root.dir === 'rtl' ? -d : d);
      } else if (e.key === 'Tab') {
        var f = Array.prototype.filter.call(panel.querySelectorAll('button, a[href]'), function (el) { return el.offsetParent !== null; });
        if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
      }
    });
    document.addEventListener('webina:lang', function () { if (current && !box.hidden) fill(); });

    // Mobile : glisser vers le bas pour fermer (distance ou vitesse suffisante)
    var startY = 0, startT = 0, dy = 0, dragging = false;
    panel.addEventListener('pointerdown', function (e) {
      if (window.innerWidth > 767 || e.pointerType === 'mouse') return;
      if (!e.target.closest('.drawer_handle, .drawer_head') && body.scrollTop > 0) return;
      if (e.target.closest('.drawer_body') && body.scrollTop > 0) return;
      dragging = true; startY = e.clientY; startT = Date.now(); dy = 0;
    });
    window.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      dy = e.clientY - startY;
      if (dy <= 0) { panel.style.transform = ''; return; }
      box.classList.add('is-dragging');
      panel.style.transform = 'translateY(' + dy + 'px)';
    });
    window.addEventListener('pointerup', function () {
      if (!dragging) return;
      dragging = false;
      box.classList.remove('is-dragging');
      var v = dy / Math.max(1, Date.now() - startT);
      if (dy > 120 || v > 0.6) close();
      else panel.style.transform = '';
    });
  })();

  /* ---------- Consentement cookies ---------- */
  (function () {
    var box = document.querySelector('[data-cookie]');
    if (!box) return;
    var KEY = 'webina-consent';
    var dialog = box.querySelector('.cookie_dialog');
    var views = { main: box.querySelector('[data-cookie-view="main"]'), settings: box.querySelector('[data-cookie-view="settings"]') };
    var switches = box.querySelectorAll('[data-cookie-switch]');
    var lastFocus = null;

    function read() {
      try { return JSON.parse(localStorage.getItem(KEY)); } catch (e) { return null; }
    }
    // Consentement lisible par d'autres scripts (ex. statistiques) : window.webinaConsent()
    window.webinaConsent = function () { return read() || { necessary: true, analytics: false, marketing: false }; };

    function show(view) {
      views.main.hidden = view !== 'main';
      views.settings.hidden = view !== 'settings';
      var c = window.webinaConsent();
      switches.forEach(function (sw) { sw.setAttribute('aria-checked', String(!!c[sw.dataset.cookieSwitch])); });
    }
    function open(view) {
      lastFocus = document.activeElement;
      show(view || 'main');
      box.hidden = false;
      if (!reduceMotion.matches) {
        box.classList.add('is-entering');
        requestAnimationFrame(function () { requestAnimationFrame(function () { box.classList.remove('is-entering'); }); });
      }
      dialog.focus({ preventScroll: true });
    }
    function close() {
      if (reduceMotion.matches) { box.hidden = true; }
      else {
        box.classList.add('is-leaving');
        setTimeout(function () { box.hidden = true; box.classList.remove('is-leaving'); }, 170);
      }
      if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
    }
    function save(c) {
      c.necessary = true;
      c.date = new Date().toISOString();
      try { localStorage.setItem(KEY, JSON.stringify(c)); } catch (e) {}
      document.dispatchEvent(new CustomEvent('webina:consent', { detail: c }));
      close();
    }

    box.querySelectorAll('[data-cookie-accept]').forEach(function (b) { b.addEventListener('click', function () { save({ analytics: true, marketing: true }); }); });
    box.querySelectorAll('[data-cookie-reject]').forEach(function (b) { b.addEventListener('click', function () { save({ analytics: false, marketing: false }); }); });
    box.querySelector('[data-cookie-settings]').addEventListener('click', function () { show('settings'); dialog.focus(); });
    box.querySelector('[data-cookie-back]').addEventListener('click', function () { show('main'); dialog.focus(); });
    box.querySelector('[data-cookie-save]').addEventListener('click', function () {
      var c = {};
      switches.forEach(function (sw) { c[sw.dataset.cookieSwitch] = sw.getAttribute('aria-checked') === 'true'; });
      save(c);
    });
    switches.forEach(function (sw) {
      sw.addEventListener('click', function () { sw.setAttribute('aria-checked', String(sw.getAttribute('aria-checked') !== 'true')); });
    });
    // Garde le focus dans la fenêtre au clavier
    box.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      var f = Array.prototype.filter.call(dialog.querySelectorAll('button, a[href]'), function (el) { return el.offsetParent !== null; });
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    });
    document.querySelectorAll('[data-cookie-open]').forEach(function (b) { b.addEventListener('click', function () { open('settings'); }); });

    if (!read()) setTimeout(function () { open('main'); }, 900);
  })();

  /* ---------- İletişim : formulaire ---------- */
  (function () {
    var form = document.querySelector('[data-contact-form]');
    if (!form) return;
    var err = form.querySelector('.form_error');
    var btn = form.querySelector('button[type="submit"]');
    var success = form.parentNode.querySelector('.form_success');
    var budget = form.querySelector('input[name="budget"]');

    form.querySelectorAll('input[name="budgetChoice"]').forEach(function (r) {
      r.addEventListener('change', function () {
        budget.value = I18N.tr.contact.budgetOpts[Number(r.value)] || '';
      });
    });
    form.addEventListener('input', function (e) { e.target.removeAttribute('aria-invalid'); });

    function fail(field, msg) {
      err.textContent = msg;
      if (field) { field.setAttribute('aria-invalid', 'true'); field.focus(); }
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var c = I18N[currentLang].contact;
      err.textContent = '';
      var data = {
        name: form.name.value.trim(), email: form.email.value.trim(), phone: form.phone.value.trim(),
        company: form.company.value.trim(), sector: form.sector.value, service: form.service.value,
        budget: budget.value, message: form.message.value.trim(), website: form.website.value
      };
      if (!data.name) return fail(form.name, c.errName);
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) return fail(form.email, c.errEmail);
      if (!data.message) return fail(form.message, c.errMsg);
      Object.keys(data).forEach(function (k) { if (data[k] === '' && k !== 'website') delete data[k]; });

      btn.disabled = true;
      btn.querySelector('[data-i18n]').textContent = c.sending;
      fetch('/api/contact.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'webina' },
        body: JSON.stringify(data)
      })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { if (!r.ok) throw d; return d; }); })
        .then(function () {
          form.hidden = true;
          success.hidden = false;
          success.focus({ preventScroll: true });
          success.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'center' });
        })
        .catch(function () { fail(null, c.errSend); })
        .then(function () {
          btn.disabled = false;
          btn.querySelector('[data-i18n]').textContent = I18N[currentLang].contact.send;
        });
    });
  })();

  /* ---------- Démos : onglets ---------- */
  var demos = (function () {
    var wrap = document.querySelector('[data-demos]');
    if (!wrap) return null;
    var list = wrap.querySelector('.demos_tabs:not(.is-overlay)');
    var overlay = wrap.querySelector('.demos_tabs.is-overlay');
    var tabs = Array.prototype.slice.call(list.querySelectorAll('[role="tab"]'));
    var panels = Array.prototype.slice.call(wrap.querySelectorAll('[role="tabpanel"]'));
    var current = tabs.findIndex(function (t) { return t.getAttribute('aria-selected') === 'true'; });

    // Copie visuelle des onglets (état actif), synchronisée avec la langue
    function buildOverlay() {
      overlay.innerHTML = list.innerHTML;
      overlay.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });
      overlay.querySelectorAll('button').forEach(function (b) {
        b.setAttribute('tabindex', '-1');
        b.removeAttribute('role');
        b.removeAttribute('aria-selected');
        b.removeAttribute('aria-controls');
      });
      overlay.scrollLeft = list.scrollLeft;
    }

    // clip-path qui ne laisse voir que l'onglet actif dans la copie
    function syncClip(animate) {
      buildOverlay();
      var tab = tabs[current];
      var w = list.clientWidth;
      var left = tab.offsetLeft - list.scrollLeft;
      var right = w - left - tab.offsetWidth;
      if (animate === false) overlay.style.transition = 'none';
      overlay.style.setProperty('--clip', '4px ' + right + 'px 4px ' + left + 'px');
      if (animate === false) {
        overlay.getBoundingClientRect();
        overlay.style.transition = '';
      }
    }

    // Distance de défilement de la page longue dans le cadre
    function measure(panel) {
      var screen = panel.querySelector('.demos_screen');
      var img = screen && screen.querySelector('img');
      if (!img) return;
      var set = function () {
        var dist = Math.max(0, img.offsetHeight - screen.clientHeight);
        screen.style.setProperty('--scroll', (-dist) + 'px');
        // Vitesse constante, environ 420px par seconde
        screen.style.setProperty('--scroll-dur', Math.max(3, dist / 420).toFixed(2) + 's');
      };
      if (img.complete && img.naturalHeight) set(); else img.addEventListener('load', set, { once: true });
    }

    function select(i, focus) {
      if (i === current) return;
      tabs[current].setAttribute('aria-selected', 'false');
      tabs[current].setAttribute('tabindex', '-1');
      panels[current].hidden = true;

      current = i;
      tabs[i].setAttribute('aria-selected', 'true');
      tabs[i].setAttribute('tabindex', '0');
      if (focus) tabs[i].focus();

      var p = panels[i];
      p.hidden = false;
      if (!reduceMotion.matches) {
        p.classList.add('is-entering');
        requestAnimationFrame(function () {
          requestAnimationFrame(function () { p.classList.remove('is-entering'); });
        });
      }
      var img = p.querySelector('.demos_screen img');
      if (img) img.loading = 'eager';
      var sc = p.querySelector('.demos_screen');
      if (sc) sc.scrollTop = 0;
      measure(p);
      syncClip(true);
    }

    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(i, false); });
      t.addEventListener('keydown', function (e) {
        var rtl = root.dir === 'rtl';
        var next = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1 }[e.key];
        if (next) {
          e.preventDefault();
          select((current + next + tabs.length) % tabs.length, true);
        } else if (e.key === 'Home') { e.preventDefault(); select(0, true); }
        else if (e.key === 'End') { e.preventDefault(); select(tabs.length - 1, true); }
      });
    });
    list.addEventListener('scroll', function () { syncClip(false); }, { passive: true });
    window.addEventListener('resize', function () { syncClip(false); measure(panels[current]); });

    syncClip(false);
    measure(panels[current]);
    return { syncClip: syncClip };
  })();

  /* ---------- Révélations au défilement ---------- */
  var revealables = document.querySelectorAll('[data-reveal]');
  if ('IntersectionObserver' in window && !reduceMotion.matches) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('is-in');
          io.unobserve(en.target);
        }
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });
    revealables.forEach(function (el) { io.observe(el); });
  } else {
    revealables.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ---------- Newsletter (même API que le site actuel) ---------- */
  var form = document.querySelector('[data-newsletter]');
  if (form) {
    var status = form.querySelector('.news_status');
    var btn = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var f = I18N[currentLang].footer;
      var email = form.email.value.trim();
      status.classList.remove('is-error');
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        status.textContent = f.newsErr;
        status.classList.add('is-error');
        form.email.focus();
        return;
      }
      btn.disabled = true;
      fetch('/api/newsletter.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'webina' },
        body: JSON.stringify({ email: email, lang: currentLang, website: form.website.value })
      })
        .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw d; return d; }); })
        .then(function (d) {
          status.textContent = d.already ? f.newsAlready : f.newsOk;
          if (!d.already) form.reset();
        })
        .catch(function () {
          status.textContent = f.newsErr;
          status.classList.add('is-error');
        })
        .then(function () { btn.disabled = false; });
    });
  }

  var year = document.querySelector('[data-year]');
  if (year) year.textContent = String(new Date().getFullYear());

  /* ---------- Démarrage ---------- */
  var params = new URLSearchParams(location.search);
  var initial = params.get('lang') || store('webina-lang') || 'tr';
  if (initial !== 'tr') setLang(initial, false);
  else syncNavIndicator(activeNavLink(), false);

  // Entrée du héro une fois les polices prêtes (évite un saut de ligne en cours d'animation)
  var start = function () {
    requestAnimationFrame(function () {
      root.classList.add('is-loaded');
      setTimeout(function () { root.classList.add('is-settled'); }, 1800);
      if (deck) deck.start();
      syncNavIndicator(activeNavLink(), false);
      if (demos) demos.syncClip(false);
    });
  };
  if (document.fonts && document.fonts.ready) {
    Promise.race([document.fonts.ready, new Promise(function (r) { setTimeout(r, 800); })]).then(start);
  } else {
    start();
  }
})();
