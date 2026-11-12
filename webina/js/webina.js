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
    var pad = parseFloat(getComputedStyle(link).paddingLeft) || 12;   // suit le resserrement de la barre sur petit écran
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

  /* ---------- Héro : titre animé ----------
     1. À l'arrivée, les lettres montent une à une en sortant d'un léger flou.
     2. Ensuite, le mot vert suit la scène : chaque réalisation a le sien
        (la boutique, le portfolio, le site d'actualités…) ; un trait se redessine dessous.
     L'arabe est animé mot par mot : couper les lettres casserait leur liaison. */
  var heroTitle = (function () {
    var h1 = document.querySelector('[data-hero-title]');
    if (!h1) return null;
    var lines = h1.querySelectorAll('.line');
    var rot = h1.querySelector('[data-rotator]');
    var words = [], shown = -1, drawTimer = null;

    // Mot → <span class="word"> contenant une <span class="char"> par lettre
    function makeWord(text, start) {
      var w = document.createElement('span');
      w.className = 'word';
      var parts = currentLang === 'ar' ? [text] : Array.from(text);
      parts.forEach(function (ch, i) {
        var c = document.createElement('span');
        c.className = 'char';
        c.textContent = ch;
        c.style.setProperty('--ci', String(start + i));
        w.appendChild(c);
      });
      return { el: w, count: parts.length };
    }
    // Texte (plusieurs mots) ajouté dans un conteneur ; renvoie le prochain index de lettre
    function fill(target, text, start) {
      var n = start;
      text.split(/(\s+)/).forEach(function (part) {
        if (!part) return;
        if (/^\s+$/.test(part)) { target.appendChild(document.createTextNode(' ')); return; }
        var w = makeWord(part, n);
        target.appendChild(w.el);
        n += w.count;
      });
      return n;
    }
    function wordEl(text) {
      var box = document.createElement('span');
      box.className = 'hero_word';
      fill(box, text, 0);
      return box;
    }
    function currentWord() {
      var d = I18N[currentLang].home;
      return shown >= 0 && words[shown] ? words[shown] : d.h1b;
    }
    function draw(delay) {
      clearTimeout(drawTimer);
      drawTimer = setTimeout(function () { rot.classList.add('is-drawn'); }, reduceMotion.matches ? 0 : delay);
    }

    function render() {
      var d = I18N[currentLang].home;
      words = d.rotate || [];
      // Lecteurs d'écran : la phrase complète, sans les lettres découpées
      h1.setAttribute('aria-label', [d.h1a, d.h1b, d.h1c, d.h1d].join(' '));
      var n = 0;
      lines[0].textContent = ''; lines[0].setAttribute('aria-hidden', 'true');
      n = fill(lines[0], d.h1a, n);
      lines[0].appendChild(document.createTextNode(' '));
      rot = document.createElement('span');
      rot.className = 'hero_rotator text-color-accent';
      var first = document.createElement('span');
      first.className = 'hero_word';
      n = fill(first, currentWord(), n);
      rot.appendChild(first);
      lines[0].appendChild(rot);
      lines[1].textContent = ''; lines[1].setAttribute('aria-hidden', 'true');
      fill(lines[1], d.h1c + ' ' + d.h1d, n);
    }

    // Remplace le mot vert : l'ancien sort par le haut, le nouveau monte lettre par lettre
    function swap(text) {
      var old = rot.querySelector('.hero_word:not(.is-leaving)');
      if (old && old.textContent === text) return;
      var nw = wordEl(text);
      if (reduceMotion.matches) {
        if (old) rot.removeChild(old);
        rot.appendChild(nw);
        return;
      }
      nw.classList.add('is-entering');
      if (old) {
        Array.prototype.forEach.call(old.querySelectorAll('.char'), function (c, i) { c.style.setProperty('--ci', String(i)); });
        old.classList.add('is-leaving');
      }
      rot.appendChild(nw);
      rot.classList.remove('is-drawn');
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          nw.classList.remove('is-entering');
          rot.classList.add('is-drawn');
        });
      });
      setTimeout(function () { if (old && old.parentNode) old.parentNode.removeChild(old); }, 700);
    }

    document.addEventListener('webina:lang', function () {
      render();
      // Changement de langue après l'arrivée : la nouvelle phrase rejoue son entrée
      if (root.classList.contains('is-loaded') && !reduceMotion.matches) {
        h1.classList.add('is-replay');
        h1.getBoundingClientRect();
        requestAnimationFrame(function () { h1.classList.remove('is-replay'); });
      }
      draw(900);
    });

    render();
    return {
      start: function () { draw(900); },
      // Appelé par la scène à chaque changement de réalisation
      show: function (i) { shown = i; swap(currentWord()); }
    };
  })();

  /* ---------- Héro : scène 3D des réalisations ----------
     Les fenêtres sont rangées en arc (perspective CSS). Une seule valeur continue, pos,
     décrit toute la scène : un ressort l'amène vers la fenêtre visée, le glisser la pilote
     directement, et la vitesse du geste est conservée au relâchement — on peut donc
     rattraper la scène en plein mouvement sans à-coup. */
  var stage = (function () {
    var el = document.querySelector('[data-stage]');
    if (!el) return null;
    var scene = el.querySelector('[data-stage-scene]');
    var track = el.querySelector('.stage_track');
    var cards = Array.prototype.slice.call(el.querySelectorAll('[data-stage-card]'));
    var shades = cards.map(function (c) { return c.querySelector('.stage_shade'); });
    var segs = Array.prototype.slice.call(el.querySelectorAll('.stage_seg'));
    var labels = Array.prototype.slice.call(el.querySelectorAll('.stage_label-item'));
    var idxEl = el.querySelector('[data-stage-index]');
    var toggleBtn = el.querySelector('[data-stage-toggle]');
    var cursor = el.querySelector('[data-stage-cursor]');
    var crop = el.querySelector('.stage_crop');
    var n = cards.length;
    if (!n) return null;

    var pos = 0, vel = 0, target = 0, active = 0;        // position continue, en « fenêtres »
    var spread = 0, spreadVel = 0, spreadTo = 0;         // 0 = fenêtres empilées, 1 = déployées en arc
    var tiltX = 0, tiltY = 0, tiltXVel = 0, tiltYVel = 0, tiltXTo = 0, tiltYTo = 0;   // inclinaison vers la souris
    var raf = 0, last = 0, cardW = 0, started = false;
    var dragId = null, dragging = false, dragMoved = false, dragX = 0, dragPos = 0, samples = [];
    var hovered = null, visible = true, manual = false;
    var refreshHover = function () {};

    function dir() { return root.dir === 'rtl' ? -1 : 1; }
    function mod(i) { return ((i % n) + n) % n; }
    // Distance signée la plus courte entre une fenêtre et le centre, la scène étant une boucle
    function wrap(d) { d = mod(d); return d > n / 2 ? d - n : d; }

    function layout() {
      var sgnDir = dir();
      for (var i = 0; i < n; i++) {
        var d = wrap(i - pos);
        var a = Math.abs(d), sgn = d < 0 ? -1 : 1;
        var near = Math.min(a, 1), far = Math.max(0, a - 1);
        var x = sgn * sgnDir * cardW * (0.62 * near + 0.33 * far) * spread;
        var z = -cardW * (0.4 * near + 0.24 * far) * spread;
        var ry = -sgn * sgnDir * 36 * near * spread;
        var o = a <= 1.6 ? 1 : Math.max(0, 1 - (a - 1.6) / 0.8);
        if (a > 0.5) o *= Math.min(1, spread * 1.5);
        var c = cards[i];
        c.style.transform = 'translate3d(' + x.toFixed(1) + 'px,0,' + z.toFixed(1) + 'px) rotateY(' + ry.toFixed(2) + 'deg)';
        c.style.opacity = o.toFixed(3);
        c.style.zIndex = String(50 - Math.round(a * 10));
        c.style.pointerEvents = o < 0.4 ? 'none' : '';
        shades[i].style.opacity = (Math.min(a, 1.5) * 0.46).toFixed(3);
      }
      track.style.transform = 'rotateX(' + (-tiltY).toFixed(2) + 'deg) rotateY(' + tiltX.toFixed(2) + 'deg)';
    }

    // Ressort amorti (pas d'oscillation) : x rejoint sa cible en gardant sa vitesse courante
    function spring(x, v, to, k, c, dt) {
      v += (-k * (x - to) - c * v) * dt;
      return [x + v * dt, v];
    }
    function tick(now) {
      var dt = Math.min(0.034, (now - last) / 1000);
      last = now;
      var r;
      if (!dragging) { r = spring(pos, vel, target, 120, 22, dt); pos = r[0]; vel = r[1]; }
      r = spring(spread, spreadVel, spreadTo, 46, 13.6, dt); spread = r[0]; spreadVel = r[1];
      r = spring(tiltX, tiltXVel, tiltXTo, 60, 15.5, dt); tiltX = r[0]; tiltXVel = r[1];
      r = spring(tiltY, tiltYVel, tiltYTo, 60, 15.5, dt); tiltY = r[0]; tiltYVel = r[1];
      var still = !dragging && Math.abs(pos - target) < 0.0015 && Math.abs(vel) < 0.01 &&
        Math.abs(spread - spreadTo) < 0.0015 && Math.abs(spreadVel) < 0.01 &&
        Math.abs(tiltX - tiltXTo) < 0.01 && Math.abs(tiltY - tiltYTo) < 0.01;
      if (still) { pos = target; vel = 0; spread = spreadTo; tiltX = tiltXTo; tiltY = tiltYTo; }
      layout();
      raf = still ? 0 : requestAnimationFrame(tick);
      if (still) refreshHover();
    }
    function kick() {
      if (raf) return;
      last = performance.now();
      raf = requestAnimationFrame(tick);
    }

    function updatePaused() {
      var paused = !started || dragging || !visible || document.hidden ||
        (hovered !== null && hovered === cards[active]) || !!el.querySelector('.stage_scene :focus-visible');
      el.classList.toggle('is-paused', paused);
    }
    function setActive(i) {
      i = mod(i);
      if (i === active) return;
      active = i;
      idxEl.textContent = (i + 1 < 10 ? '0' : '') + (i + 1);
      cards.forEach(function (c, k) {
        if (k === i) c.removeAttribute('tabindex'); else c.setAttribute('tabindex', '-1');
      });
      labels.forEach(function (l, k) { l.classList.toggle('is-active', k === i); });
      segs.forEach(function (s, k) {
        s.classList.remove('is-active');
        if (k === i) { s.getBoundingClientRect(); s.classList.add('is-active'); }
      });
      if (heroTitle) heroTitle.show(i);
      // Les repères de coupe se resserrent au moment où la nouvelle fenêtre se cale au centre
      if (crop && crop.animate && started && !dragging && !reduceMotion.matches) {
        crop.animate([{ transform: 'translate(-50%, -50%) scale(1.03)' }, { transform: 'translate(-50%, -50%) scale(1)' }],
          { duration: 560, delay: 160, easing: 'cubic-bezier(0.23, 1, 0.32, 1)', fill: 'backwards' });
      }
      updatePaused();
    }
    function go(t) {
      target = t;
      setActive(Math.round(t));
      if (reduceMotion.matches) { pos = target; vel = 0; layout(); return; }
      kick();
    }

    // La barre de progression donne le tempo : quand elle est pleine, on passe à la suivante
    segs.forEach(function (s, i) {
      s.querySelector('.stage_seg-fill').addEventListener('animationend', function () {
        if (i === active && !manual) go(target + 1);
      });
      s.addEventListener('click', function () { go(target + wrap(i - target)); });
    });
    el.querySelector('[data-stage-prev]').addEventListener('click', function () { go(target - 1); });
    el.querySelector('[data-stage-next]').addEventListener('click', function () { go(target + 1); });
    function syncToggle() {
      el.classList.toggle('is-manual', manual);
      toggleBtn.setAttribute('aria-label', I18N[currentLang].stage[manual ? 'play' : 'pause']);
      toggleBtn.setAttribute('data-i18n-aria', manual ? 'stage.play' : 'stage.pause');
    }
    toggleBtn.addEventListener('click', function () { manual = !manual; syncToggle(); });

    /* Glisser : la scène suit le doigt, puis le ressort reprend avec la vitesse du geste */
    scene.addEventListener('pointerdown', function (e) {
      if (dragId !== null || (e.pointerType === 'mouse' && e.button !== 0)) return;
      dragId = e.pointerId; dragX = e.clientX; dragPos = pos; dragMoved = false;
      samples = [{ t: e.timeStamp, p: pos }];
      if (cursor) cursor.classList.add('is-down');
    });
    scene.addEventListener('pointermove', function (e) {
      if (e.pointerId !== dragId) return;
      var dx = e.clientX - dragX;
      if (!dragging) {
        if (Math.abs(dx) < 8) return;
        dragging = true; dragMoved = true;
        try { scene.setPointerCapture(dragId); } catch (err) {}
        dragX = e.clientX; dragPos = pos; dx = 0;
        updatePaused();
      }
      pos = dragPos - (dx / (cardW * 0.62)) * dir();
      vel = 0;
      setActive(Math.round(pos));          // le titre et la légende suivent la fenêtre qui passe au centre
      samples.push({ t: e.timeStamp, p: pos });
      if (samples.length > 5) samples.shift();
      kick();
    });
    function endDrag(e) {
      if (e.pointerId !== dragId) return;
      dragId = null;
      if (cursor) cursor.classList.remove('is-down');
      if (!dragging) return;
      dragging = false;
      var a = samples[0], b = samples[samples.length - 1];
      var v = b.t > a.t ? (b.p - a.p) / ((b.t - a.t) / 1000) : 0;
      if (e.timeStamp - b.t > 90) v = 0;                 // le doigt s'était arrêté avant de lâcher
      v = Math.max(-9, Math.min(9, v));
      vel = v;
      var from = Math.round(dragPos);
      go(Math.max(from - 3, Math.min(from + 3, Math.round(pos + v * 0.2))));
      updatePaused();
    }
    scene.addEventListener('pointerup', endDrag);
    scene.addEventListener('pointercancel', endDrag);
    scene.addEventListener('dragstart', function (e) { e.preventDefault(); });
    // Après un glisser, le clic qui suit n'ouvre rien ; un clic sur une fenêtre de côté l'amène au centre
    scene.addEventListener('click', function (e) {
      if (dragMoved) { dragMoved = false; e.preventDefault(); e.stopPropagation(); return; }
      var card = e.target.closest ? e.target.closest('[data-stage-card]') : null;
      if (!card) return;
      var i = cards.indexOf(card);
      if (i !== active) { e.preventDefault(); go(target + wrap(i - target)); }
    }, true);
    el.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
      var onCard = document.activeElement && document.activeElement.hasAttribute('data-stage-card');
      go(target + (e.key === 'ArrowRight' ? 1 : -1) * dir());
      if (onCard) cards[active].focus({ preventScroll: true });
      e.preventDefault();
    });
    el.addEventListener('focusin', updatePaused);
    el.addEventListener('focusout', function () { setTimeout(updatePaused, 0); });

    /* Souris : la scène s'incline légèrement vers le pointeur, et le carré du logo sert de curseur */
    if (finePointer.matches && !reduceMotion.matches) {
      if (cursor) el.classList.add('has-cursor');
      var rect = null, mx = -1, my = -1;
      // Fenêtre sous la souris : relue aussi quand la scène s'arrête, car elle a pu changer sans que la souris bouge
      refreshHover = function () {
        if (mx < 0) return;
        var under = document.elementFromPoint(mx, my);
        var card = under && under.closest ? under.closest('[data-stage-card]') : null;
        if (card !== hovered) { hovered = card; updatePaused(); }
        if (cursor) cursor.classList.toggle('is-open', !dragging && card !== null && card === cards[active]);
      };
      var onMove = function (e) {
        if (e.pointerType !== 'mouse') return;
        mx = e.clientX; my = e.clientY;
        if (cursor) {
          cursor.style.translate = mx + 'px ' + my + 'px';
          cursor.classList.add('is-on');
        }
        refreshHover();
        if (!rect) rect = scene.getBoundingClientRect();
        tiltXTo = ((mx - rect.left) / rect.width - 0.5) * 7;
        tiltYTo = ((my - rect.top) / rect.height - 0.5) * 5;
        kick();
      };
      scene.addEventListener('pointerenter', function (e) { rect = scene.getBoundingClientRect(); onMove(e); });
      scene.addEventListener('pointermove', onMove);
      scene.addEventListener('pointerleave', function () {
        hovered = null; rect = null; mx = my = -1; tiltXTo = 0; tiltYTo = 0;
        if (cursor) cursor.classList.remove('is-on');
        updatePaused(); kick();
      });
      // Si la page défile sous une souris immobile, le curseur carré ne doit pas rester à l'écran
      window.addEventListener('scroll', function () {
        rect = null;
        if (cursor) cursor.classList.remove('is-on');
      }, { passive: true });
    }

    function measure() { cardW = cards[0].offsetWidth; }
    // Captures des fenêtres éloignées : chargées une fois l'entrée jouée, pour laisser la priorité à celle du centre
    function loadRest() {
      Array.prototype.forEach.call(el.querySelectorAll('img[data-src]'), function (im) {
        im.srcset = im.getAttribute('data-srcset');
        im.src = im.getAttribute('data-src');
        im.removeAttribute('data-src'); im.removeAttribute('data-srcset');
      });
    }
    window.addEventListener('resize', function () { measure(); layout(); });
    document.addEventListener('visibilitychange', updatePaused);
    document.addEventListener('webina:lang', function () { syncToggle(); layout(); });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; updatePaused(); }, { threshold: 0.2 }).observe(el);
    }

    measure();
    updatePaused();
    layout();
    return {
      start: function () {
        measure();
        if (reduceMotion.matches) {
          // Mouvement réduit : scène déjà déployée, pas de défilement automatique
          spread = spreadTo = 1; manual = true; started = true;
          syncToggle(); layout(); el.classList.add('is-ready'); updatePaused(); loadRest();
          return;
        }
        // Les fenêtres se déploient de derrière celle du centre, puis le défilement démarre
        setTimeout(function () { spreadTo = 1; kick(); }, 220);
        setTimeout(loadRest, 700);
        setTimeout(function () {
          el.classList.add('is-ready');
          started = true;
          segs[active].classList.remove('is-active');
          segs[active].getBoundingClientRect();
          segs[active].classList.add('is-active');
          updatePaused();
        }, 1150);
      }
    };
  })();

  /* ---------- Héro : vitesse réelle de la page, mesurée chez le visiteur ----------
     Temps d'affichage du plus grand élément (LCP) ; à défaut, fin du chargement du document.
     N'est affiché que si la mesure existe et reste sous 2,5 s. */
  (function () {
    var box = document.querySelector('[data-speed]');
    if (!box || !window.performance || !performance.getEntriesByType) return;
    var out = box.querySelector('[data-speed-text]');
    var lcp = 0, seconds = 0;
    try {
      new PerformanceObserver(function (list) {
        var entries = list.getEntries();
        if (entries.length) lcp = entries[entries.length - 1].startTime;
      }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) {}
    function render() {
      if (!seconds) return;
      var num = seconds.toFixed(2);
      if (currentLang === 'tr' || currentLang === 'fr') num = num.replace('.', ',');
      out.textContent = I18N[currentLang].speed.replace('{t}', num);
      box.hidden = false;
      requestAnimationFrame(function () { requestAnimationFrame(function () { box.classList.add('is-in'); }); });
    }
    function measure() {
      var ms = lcp;
      if (!ms) {
        var nav = performance.getEntriesByType('navigation')[0];
        if (nav) ms = nav.domContentLoadedEventEnd - nav.startTime;
      }
      if (ms > 30 && ms < 2500) { seconds = ms / 1000; render(); }
    }
    if (document.readyState === 'complete') setTimeout(measure, 1600);
    else window.addEventListener('load', function () { setTimeout(measure, 1600); });
    document.addEventListener('webina:lang', render);
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

  /* ---------- Explications dépliables (services, équipe, secteurs) ----------
     Ordinateur : le survol du titre ou du texte déplie l'explication sous l'élément.
     Écran tactile et clavier : le bouton « + » ouvre et ferme. */
  (function () {
    var items = Array.prototype.slice.call(document.querySelectorAll('.has-detail'));
    if (!items.length) return;
    var WA = 'https://wa.me/905399662557';
    var MSG = { tr: 'Merhaba, «{x}» hakkında bilgi almak istiyorum.', en: 'Hello, I would like to know more about “{x}”.',
                fr: 'Bonjour, je souhaite en savoir plus sur « {x} ».', ar: 'مرحباً، أود معرفة المزيد عن «{x}».' };

    function setOpen(item, open) {
      if (item.classList.contains('is-open') === open) return;
      item.classList.toggle('is-open', open);
      var btn = item.querySelector('[data-detail-toggle]');
      if (btn) btn.setAttribute('aria-expanded', String(open));
      if (open) {
        // Lien WhatsApp pré-rempli avec le titre de l'élément, dans la langue affichée
        var title = item.querySelector('h2, h3, h4');
        var wa = item.querySelector('[data-detail-wa]');
        if (wa && title) wa.href = WA + '?text=' + encodeURIComponent((MSG[currentLang] || MSG.tr).replace('{x}', title.textContent.trim()));
      }
    }
    function closeOthers(keep) {
      items.forEach(function (it) { if (it !== keep) setOpen(it, false); });
    }

    items.forEach(function (item) {
      var enterTimer, leaveTimer;
      // Seuls le titre et le petit texte déclenchent l'ouverture au survol
      var zones = item.querySelectorAll('h2, h3, h4, p:not(.detail_intro):not(.detail_label), .services_index, .service-index_number');
      Array.prototype.forEach.call(zones, function (z) {
        if (z.closest('.detail_panel')) return;
        z.addEventListener('pointerenter', function (e) {
          if (e.pointerType !== 'mouse' || !finePointer.matches) return;
          clearTimeout(leaveTimer);
          // Petite intention de survol : évite d'ouvrir en traversant la liste
          enterTimer = setTimeout(function () { closeOthers(item); setOpen(item, true); }, 180);
        });
        z.addEventListener('pointerleave', function () { clearTimeout(enterTimer); });
      });
      item.addEventListener('pointerleave', function (e) {
        if (e.pointerType !== 'mouse') return;
        clearTimeout(enterTimer);
        leaveTimer = setTimeout(function () { setOpen(item, false); }, 260);
      });
      item.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') clearTimeout(leaveTimer); });
    });

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-detail-toggle]');
      var item = btn ? btn.closest('.has-detail') : null;
      // Sur écran tactile, toucher le titre ou le texte ouvre aussi
      if (!item && !finePointer.matches) {
        var it = e.target.closest('.has-detail');
        if (it && !e.target.closest('a, .detail_panel')) item = it;
      }
      if (!item) return;
      var open = !item.classList.contains('is-open');
      closeOthers(item);
      setOpen(item, open);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeOthers(null);
    });
  })();

  /* ---------- Logo animé du pied de page ---------- */
  (function () {
    var logo = document.querySelector('[data-wb-logo]');
    if (!logo) return;
    var wrap = logo.querySelector('.wb_dot-wrap');
    var played = 0;
    function play() {
      logo.classList.remove('is-in');
      logo.getBoundingClientRect();
      logo.classList.add('is-in');
      played = Date.now();
    }
    if ('IntersectionObserver' in window && !reduceMotion.matches) {
      var io = new IntersectionObserver(function (en) {
        if (en[0].isIntersecting) { play(); io.disconnect(); }
      }, { threshold: 0.6 });
      io.observe(logo);
    } else {
      logo.classList.add('is-in');
    }
    // Survol : l'animation se rejoue (pas plus d'une fois toutes les 2,5 s)
    logo.addEventListener('pointerenter', function (e) {
      if (e.pointerType !== 'mouse' || reduceMotion.matches) return;
      if (Date.now() - played > 2500) play();
    });
    // Le carré vert se penche légèrement vers la souris
    logo.addEventListener('pointermove', function (e) {
      if (e.pointerType !== 'mouse' || reduceMotion.matches) return;
      var r = logo.getBoundingClientRect();
      var x = (e.clientX - (r.left + r.width / 2)) / (r.width / 2);
      var y = (e.clientY - (r.top + r.height / 2)) / (r.height / 2);
      wrap.style.setProperty('--dx', (x * 6).toFixed(1) + 'px');
      wrap.style.setProperty('--dy', (y * 4).toFixed(1) + 'px');
    });
    logo.addEventListener('pointerleave', function () {
      wrap.style.setProperty('--dx', '0px');
      wrap.style.setProperty('--dy', '0px');
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

  /* ---------- Manifeste : les mots s'éclairent au fil du défilement ---------- */
  (function () {
    var el = document.querySelector('[data-manifesto]');
    if (!el) return;
    var words = [], lit = -1, inView = false, queued = false;
    function split() {
      var text = el.textContent;
      el.textContent = '';
      words = [];
      text.split(/(\s+)/).forEach(function (part) {
        if (!part) return;
        if (/^\s+$/.test(part)) { el.appendChild(document.createTextNode(' ')); return; }
        var w = document.createElement('span');
        w.className = 'manifesto_word';
        w.textContent = part;
        el.appendChild(w);
        words.push(w);
      });
      el.classList.add('is-split');
      lit = -1;
      update();
    }
    function update() {
      queued = false;
      var count = words.length;
      if (reduceMotion.matches) {
        words.forEach(function (w) { w.classList.add('is-lit'); });
        return;
      }
      var r = el.getBoundingClientRect();
      var vh = window.innerHeight;
      // 0 quand le haut du texte entre par le bas de l'écran, 1 quand le texte arrive vers le milieu
      var p = (vh * 0.88 - r.top) / (vh * 0.5 + r.height * 0.6);
      var n = Math.round(Math.max(0, Math.min(1, p)) * count);
      if (n === lit) return;
      for (var i = 0; i < count; i++) words[i].classList.toggle('is-lit', i < n);
      lit = n;
    }
    function onScroll() {
      if (!inView || queued) return;
      queued = true;
      requestAnimationFrame(update);
    }
    split();
    document.addEventListener('webina:lang', split);
    if ('IntersectionObserver' in window && !reduceMotion.matches) {
      new IntersectionObserver(function (en) { inView = en[0].isIntersecting; if (inView) update(); }, { rootMargin: '10% 0px 10% 0px' }).observe(el);
      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll);
    } else {
      words.forEach(function (w) { w.classList.add('is-lit'); });
    }
  })();

  /* ---------- Chiffres : comptent jusqu'à leur valeur à l'apparition ---------- */
  (function () {
    var nums = document.querySelectorAll('[data-count]');
    if (!nums.length || reduceMotion.matches || !('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        var el = en.target, to = Number(el.getAttribute('data-count')), t0 = 0, D = 900;
        el.textContent = '0';
        requestAnimationFrame(function step(now) {
          if (!t0) t0 = now;
          var x = Math.min(1, (now - t0) / D);
          el.textContent = String(Math.round(to * (1 - Math.pow(1 - x, 4))));
          if (x < 1) requestAnimationFrame(step);
        });
      });
    }, { threshold: 0.6 });
    nums.forEach(function (el) { io.observe(el); });
  })();

  /* ---------- Projets : la capture suit le curseur le long de l'index ---------- */
  (function () {
    var list = document.querySelector('[data-worklist]');
    var box = document.querySelector('[data-worklist-preview]');
    if (!list || !box || !finePointer.matches) return;
    var imgs = box.querySelectorAll('img');
    var loaded = false, on = false, raf = 0;
    var x = 0, y = 0, tx = 0, ty = 0, vx = 0, vy = 0, last = 0;
    function load() {
      if (loaded) return;
      loaded = true;
      Array.prototype.forEach.call(imgs, function (im) { im.src = im.getAttribute('data-src'); });
    }
    // Suivi au ressort : l'aperçu a un peu d'inertie, il ne colle pas au pixel près
    function tick(now) {
      var dt = Math.min(0.034, (now - last) / 1000);
      last = now;
      vx += (-170 * (x - tx) - 24 * vx) * dt; x += vx * dt;
      vy += (-170 * (y - ty) - 24 * vy) * dt; y += vy * dt;
      box.style.translate = x.toFixed(1) + 'px ' + y.toFixed(1) + 'px';
      var still = Math.abs(x - tx) < 0.3 && Math.abs(y - ty) < 0.3 && Math.abs(vx) < 1 && Math.abs(vy) < 1;
      raf = (still && !on) ? 0 : requestAnimationFrame(tick);
    }
    function aim(e) {
      var w = box.offsetWidth, h = box.offsetHeight;
      // À droite du curseur, ou à gauche s'il n'y a pas la place
      tx = e.clientX + 28;
      if (tx + w > window.innerWidth - 16) tx = e.clientX - 28 - w;
      ty = Math.max(16, Math.min(window.innerHeight - h - 16, e.clientY - h / 2));
    }
    function show(link, e) {
      load();
      var i = Number(link.getAttribute('data-preview'));
      Array.prototype.forEach.call(imgs, function (im, k) { im.classList.toggle('is-active', k === i); });
      aim(e);
      if (!on) { x = tx; y = ty; vx = vy = 0; box.style.translate = x + 'px ' + y + 'px'; }
      on = true;
      box.classList.add('is-on');
      if (!raf) { last = performance.now(); raf = requestAnimationFrame(tick); }
    }
    function hide() { on = false; box.classList.remove('is-on'); }
    list.addEventListener('pointerover', function (e) {
      if (e.pointerType !== 'mouse') return;
      var link = e.target.closest ? e.target.closest('.worklist_link') : null;
      if (link) show(link, e);
    });
    list.addEventListener('pointermove', function (e) { if (on) aim(e); });
    list.addEventListener('pointerleave', hide);
    window.addEventListener('scroll', function () { if (on) hide(); }, { passive: true });
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

  /* ---------- Arrivée sur le site : animation du logo ----------
     1. Le W puis le B se tracent, le carré vert tombe à sa place, WEBINA apparaît lettre par lettre,
        la barre verte se déroule, puis la signature.
     2. Le logo rétrécit et glisse exactement à la place de celui de l'en-tête (FLIP), le fond s'efface,
        et la page démarre son entrée au moment où le logo se pose.
     Un clic, une touche ou la molette accélèrent la fin. Une seule fois par visite. */
  var intro = (function () {
    var box = document.querySelector('[data-intro]');
    if (!box || !root.classList.contains('has-intro')) return null;
    var logo = box.querySelector('[data-intro-logo]');
    var q = function (s) { return box.querySelector(s); };
    var EASE_IO = 'cubic-bezier(0.77, 0, 0.175, 1)', EASE_OUT = 'cubic-bezier(0.23, 1, 0.32, 1)';
    var anims = [], done = false, flying = false, resolve;
    var finished = new Promise(function (r) { resolve = r; });
    try { sessionStorage.setItem('wb-intro', '1'); } catch (e) {}

    function play(el, frames, opts) {
      if (!el || !el.animate) return null;
      var a = el.animate(frames, Object.assign({ fill: 'both' }, opts));
      anims.push(a);
      return a;
    }
    play(q('[data-intro-w]'), [{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }], { duration: 640, delay: 80, easing: EASE_IO });
    play(q('[data-intro-b]'), [{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }], { duration: 640, delay: 320, easing: EASE_IO });
    play(q('[data-intro-dot]'), [
      { opacity: 0, transform: 'translateY(-70px) scale(0.6)' },
      { opacity: 1, transform: 'translateY(0) scale(1.1, 0.86)', offset: 0.55 },
      { transform: 'translateY(-8px) scale(0.96, 1.05)', offset: 0.78 },
      { opacity: 1, transform: 'none' }
    ], { duration: 560, delay: 860, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' });
    Array.prototype.forEach.call(box.querySelectorAll('[data-intro-name] text'), function (t, i) {
      play(t, [{ opacity: 0, transform: 'translateY(12px)', filter: 'blur(3px)' }, { opacity: 1, transform: 'none', filter: 'blur(0)' }],
        { duration: 420, delay: 1020 + i * 45, easing: EASE_OUT });
    });
    play(q('[data-intro-bar]'), [{ transform: 'scaleX(0)' }, { transform: 'scaleX(1)' }], { duration: 520, delay: 1260, easing: EASE_OUT });
    play(q('[data-intro-tag]'), [{ opacity: 0 }, { opacity: 1 }], { duration: 400, delay: 1480, easing: 'ease' });

    // Le logo rejoint la place exacte de celui de l'en-tête
    function fly(fast) {
      if (flying) return;
      flying = true;
      anims.forEach(function (a) { try { a.finish(); } catch (e) {} });
      var target = document.querySelector('.nav_brand .nav_logo:not([hidden])');
      var imgs = document.querySelectorAll('.nav_brand .nav_logo');
      for (var i = 0; i < imgs.length; i++) if (imgs[i].offsetParent !== null) { target = imgs[i]; break; }
      var from = logo.getBoundingClientRect(), to = target ? target.getBoundingClientRect() : { left: 32, top: 16, width: 40, height: 40 };
      var dx = to.left - from.left, dy = to.top - from.top, k = to.width / from.width;
      var dur = fast ? 420 : 760;
      var move = logo.animate([{ transform: 'none' }, { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(' + k + ')' }],
        { duration: dur, easing: EASE_IO, fill: 'forwards' });
      // Le fond s'efface pendant le trajet ; la page commence son entrée dès maintenant
      box.animate([{ backgroundColor: getComputedStyle(box).backgroundColor }, { backgroundColor: 'transparent' }],
        { duration: dur * 0.7, delay: dur * 0.2, easing: 'ease', fill: 'forwards' });
      resolve();
      move.onfinish = function () {
        root.classList.remove('has-intro');
        // Relais avec le vrai logo de l'en-tête, sans saut
        if (target && target.animate) target.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 160, easing: 'ease' });
        box.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 160, easing: 'ease', fill: 'forwards' }).onfinish = function () {
          if (box.parentNode) box.parentNode.removeChild(box);
          done = true;
        };
      };
    }
    var t = setTimeout(function () { fly(false); }, 1980);
    function skip() { clearTimeout(t); fly(true); }
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(function (ev) { window.addEventListener(ev, skip, { once: true, passive: true }); });
    // Sécurité : quoi qu'il arrive, la page n'est jamais bloquée derrière l'animation
    setTimeout(function () { if (!done) { root.classList.remove('has-intro'); if (box.parentNode) box.parentNode.removeChild(box); resolve(); } }, 4500);
    return { finished: finished };
  })();

  /* ---------- Démarrage ---------- */
  var params = new URLSearchParams(location.search);
  var initial = params.get('lang') || store('webina-lang') || 'tr';
  if (initial !== 'tr') setLang(initial, false);
  else syncNavIndicator(activeNavLink(), false);

  // Entrée du héro une fois les polices prêtes (évite un saut de ligne en cours d'animation)
  var start = function () {
    requestAnimationFrame(function () {
      root.classList.add('is-loaded');
      if (stage) stage.start();
      if (heroTitle) heroTitle.start();
      syncNavIndicator(activeNavLink(), false);
      if (demos) demos.syncClip(false);
    });
  };
  var ready = (document.fonts && document.fonts.ready)
    ? Promise.race([document.fonts.ready, new Promise(function (r) { setTimeout(r, 800); })])
    : Promise.resolve();
  // Avec l'animation du logo, la page démarre quand le logo commence à rejoindre l'en-tête
  Promise.all([ready, intro ? intro.finished : null]).then(start);
})();
