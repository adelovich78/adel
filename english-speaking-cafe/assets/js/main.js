/* =========================================================
   XY Academy — moteur d'interactions & animations
   GSAP + ScrollTrigger + Lenis (auto-hébergés, aucun build)
   ========================================================= */
(function () {
  'use strict';

  var doc = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var hasGsap = function () { return typeof window.gsap !== 'undefined'; };

  /* ---------- Thème clair / sombre ---------- */
  function currentTheme() {
    var t = doc.getAttribute('data-theme');
    if (t) return t;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  $$('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var next = currentTheme() === 'dark' ? 'light' : 'dark';
      doc.setAttribute('data-theme', next);
      try { localStorage.setItem('xy-theme', next); } catch (e) {}
    });
  });

  /* ---------- Toasts ---------- */
  $$('[data-toasts] .alert').forEach(function (a, i) {
    setTimeout(function () { a.style.transition = 'opacity .6s, transform .6s'; a.style.opacity = '0'; a.style.transform = 'translateY(10px)'; setTimeout(function () { a.remove(); }, 700); }, 6500 + i * 600);
    a.addEventListener('click', function () { a.remove(); });
  });

  /* ---------- Menu mobile ---------- */
  var menuBtn = $('[data-menu-toggle]'), menu = $('[data-menu]'), nav = $('[data-navbar]');
  if (menuBtn && menu) {
    menuBtn.addEventListener('click', function () {
      var open = !menu.classList.contains('is-open');
      menu.classList.toggle('is-open', open);
      nav.classList.toggle('menu-open', open);
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (window.__lenis) open ? window.__lenis.stop() : window.__lenis.start();
      if (open && hasGsap() && !reduce) gsap.fromTo($$('.navbar_link', menu), { yPercent: 120, opacity: 0 }, { yPercent: 0, opacity: 1, stagger: .05, duration: .9, ease: 'expo.out', delay: .25 });
    });
  }

  /* ---------- FAQ ---------- */
  $$('[data-faq]').forEach(function (item) {
    var q = $('.faq_question', item);
    q.addEventListener('click', function () {
      var open = !item.classList.contains('is-open');
      item.classList.toggle('is-open', open);
      q.setAttribute('aria-expanded', open ? 'true' : 'false');
      setTimeout(function () { if (window.ScrollTrigger) ScrollTrigger.refresh(); }, 650);
    });
  });

  /* ---------- Rotateur de mots (hero) ---------- */
  $$('[data-rotator]').forEach(function (r) {
    var items = $$('span', r), i = 0;
    if (items.length < 2) return;
    setInterval(function () {
      var cur = items[i]; i = (i + 1) % items.length; var nxt = items[i];
      if (hasGsap() && !reduce) {
        // y:0 neutralise la translation CSS initiale lue par GSAP
        gsap.to(cur, { y: 0, yPercent: -110, duration: .8, ease: 'expo.inOut', onComplete: function () { cur.classList.remove('is-active'); } });
        gsap.fromTo(nxt, { y: 0, yPercent: 110 }, { y: 0, yPercent: 0, duration: .8, ease: 'expo.inOut' });
        nxt.classList.add('is-active');
      } else { cur.classList.remove('is-active'); nxt.classList.add('is-active'); }
    }, 2600);
  });

  /* ---------- Témoignages ---------- */
  $$('[data-quotes]').forEach(function (q) {
    var slides = $$('.quotes_slider > *', q), navEl = $('[data-quotes-nav]', q), idx = 0, timer;
    var dots = slides.map(function (_, k) {
      var d = document.createElement('button'); d.type = 'button'; d.className = 'quotes_dot' + (k === 0 ? ' is-active' : ''); d.setAttribute('aria-label', 'Testimonial ' + (k + 1));
      d.addEventListener('click', function () { go(k); }); navEl.appendChild(d); return d;
    });
    function go(k) {
      slides[idx].classList.remove('is-active'); dots[idx].classList.remove('is-active');
      idx = k; slides[idx].classList.add('is-active');
      void dots[idx].offsetWidth; dots[idx].classList.add('is-active');
      clearTimeout(timer); timer = setTimeout(function () { go((idx + 1) % slides.length); }, 6000);
    }
    timer = setTimeout(function () { go(1 % slides.length); }, 6000);
  });

  /* ---------- Curseur personnalisé ---------- */
  var cursor = $('[data-cursor]');
  if (cursor && fine && !reduce) {
    var ring = $('[data-cursor-ring]'), dot = $('[data-cursor-dot]');
    var mx = innerWidth / 2, my = innerHeight / 2, rx = mx, ry = my;
    cursor.style.opacity = '0';
    window.addEventListener('mousemove', function (e) { mx = e.clientX; my = e.clientY; cursor.style.opacity = '1'; dot.style.transform = 'translate(' + mx + 'px,' + my + 'px)'; }, { passive: true });
    (function loop() { rx += (mx - rx) * .16; ry += (my - ry) * .16; ring.style.transform = 'translate(' + rx + 'px,' + ry + 'px)'; requestAnimationFrame(loop); })();
    dot.style.position = ring.style.position = 'absolute'; dot.style.left = ring.style.left = '0'; dot.style.top = ring.style.top = '0';
    document.addEventListener('mouseover', function (e) {
      var lbl = e.target.closest('[data-cursor-label]');
      var hov = e.target.closest('a, button, input, select, textarea, label, [data-faq]');
      cursor.classList.toggle('is-label', !!lbl);
      cursor.classList.toggle('is-hover', !lbl && !!hov);
      ring.textContent = lbl ? lbl.getAttribute('data-cursor-label') : '';
    });
    document.addEventListener('mouseleave', function () { cursor.style.opacity = '0'; });
    document.addEventListener('mouseenter', function () { cursor.style.opacity = '1'; });
  }

  /* ---------- Boutons magnétiques ---------- */
  if (fine && !reduce) {
    $$('[data-magnetic]').forEach(function (el) {
      el.addEventListener('mousemove', function (e) {
        var r = el.getBoundingClientRect();
        var x = (e.clientX - r.left - r.width / 2) * .28, y = (e.clientY - r.top - r.height / 2) * .38;
        el.style.transform = 'translate(' + x + 'px,' + y + 'px)';
      });
      el.addEventListener('mouseleave', function () { el.style.transform = ''; });
    });
  }

  /* ---------- Transition de page ---------- */
  var panel = $('[data-transition]');
  if (panel && !reduce) {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-transition') || /^(mailto|tel):/.test(href)) return;
      if (a.origin !== location.origin || /\/library\/download\//.test(a.pathname)) return;
      if (a.pathname === location.pathname && a.search === location.search) return;
      e.preventDefault();
      if (hasGsap()) {
        gsap.fromTo(panel, { yPercent: 100 }, { yPercent: 0, duration: .65, ease: 'expo.inOut', onComplete: function () { location.href = a.href; } });
        gsap.fromTo($('svg', panel), { scale: .6, opacity: 0 }, { scale: 1, opacity: 1, duration: .6, delay: .25, ease: 'expo.out' });
      } else location.href = a.href;
    });
    // Retour arrière (bfcache) : on remet le panneau hors champ
    window.addEventListener('pageshow', function (ev) { if (ev.persisted && hasGsap()) gsap.set(panel, { yPercent: 100 }); });
  }

  /* ---------- Si GSAP indisponible : tout afficher ---------- */
  function revealAllFallback() {
    $$('[data-reveal]').forEach(function (el) { el.style.opacity = 1; el.style.transform = 'none'; });
    $$('.manifesto_text .word').forEach(function (w) { w.style.opacity = 1; });
    var pre = $('[data-preloader]'); if (pre) pre.remove();
    doc.classList.add('is-loaded');
  }

  /* ---------- Découpage de texte (mots → masques) ---------- */
  function splitWords(el) {
    if (el.dataset.splitDone) return $$('.split-inner', el);
    var walk = function (node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (n) {
        if (n.nodeType === 3) {
          var frag = document.createDocumentFragment();
          n.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
            var w = document.createElement('span'); w.className = 'split-word'; w.style.overflow = 'hidden'; w.style.verticalAlign = 'top'; w.style.paddingBottom = '.2em'; w.style.marginBottom = '-.2em';
            var inner = document.createElement('span'); inner.className = 'split-inner'; inner.textContent = part;
            w.appendChild(inner); frag.appendChild(w);
          });
          n.parentNode.replaceChild(frag, n);
        } else if (n.nodeType === 1 && !n.classList.contains('split-word')) walk(n);
      });
    };
    walk(el); el.dataset.splitDone = '1';
    return $$('.split-inner', el);
  }

  /* ---------- Initialisation des animations ---------- */
  function init() {
    if (!hasGsap() || reduce) { revealAllFallback(); return; }
    gsap.registerPlugin(ScrollTrigger);

    // Défilement fluide
    if (typeof window.Lenis !== 'undefined') {
      var lenis = new Lenis({ duration: 1.15, easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); }, smoothWheel: true });
      window.__lenis = lenis;
      lenis.on('scroll', ScrollTrigger.update);
      gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
      gsap.ticker.lagSmoothing(0);
      $$('a[href^="#"]').forEach(function (a) { a.addEventListener('click', function (e) { var t = $(a.getAttribute('href')); if (t) { e.preventDefault(); lenis.scrollTo(t, { offset: -80 }); } }); });
    }

    // Navbar : fond au scroll + masquage en descente
    if (nav) {
      var lastY = 0;
      ScrollTrigger.create({ start: 0, end: 'max', onUpdate: function (self) {
        var y = self.scroll();
        nav.classList.toggle('is-scrolled', y > 40);
        if (!nav.classList.contains('menu-open')) nav.classList.toggle('is-hidden', y > 400 && y > lastY);
        lastY = y;
      } });
    }

    // Panneau de transition : sortie à l'arrivée
    if (panel) gsap.set(panel, { yPercent: 100 });

    // Titres découpés
    $$('[data-split]').forEach(function (el) {
      var words = splitWords(el);
      gsap.from(words, { yPercent: 115, rotate: 4, duration: 1.2, ease: 'expo.out', stagger: .045, scrollTrigger: { trigger: el, start: 'top 88%' } });
    });

    // Révélations génériques (regroupées pour un effet en cascade)
    ScrollTrigger.batch('[data-reveal]', {
      start: 'top 90%',
      onEnter: function (batch) { gsap.to(batch, { opacity: 1, y: 0, scale: 1, duration: 1.1, ease: 'expo.out', stagger: .08, overwrite: true }); }
    });

    // Parallaxe
    $$('[data-parallax]').forEach(function (el) {
      var amt = parseFloat(el.getAttribute('data-parallax')) || -10;
      gsap.fromTo(el, { yPercent: -amt / 2 }, { yPercent: amt / 2, ease: 'none', scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } });
    });

    // Compteurs
    $$('[data-count]').forEach(function (el) {
      var end = parseInt(el.getAttribute('data-count'), 10) || 0, o = { v: 0 };
      ScrollTrigger.create({ trigger: el, start: 'top 90%', once: true, onEnter: function () {
        gsap.to(o, { v: end, duration: 2.2, ease: 'power3.out', onUpdate: function () { el.textContent = Math.round(o.v).toLocaleString('en-US'); } });
      } });
    });

    // Manifesto : mots qui s'allument au scroll
    $$('[data-scrub-text]').forEach(function (el) {
      var walk = function (node) {
        Array.prototype.slice.call(node.childNodes).forEach(function (n) {
          if (n.nodeType === 3) {
            var frag = document.createDocumentFragment();
            n.textContent.split(/(\s+)/).forEach(function (p) {
              if (!p) return;
              if (/^\s+$/.test(p)) { frag.appendChild(document.createTextNode(' ')); return; }
              var s = document.createElement('span'); s.className = 'word'; s.textContent = p; frag.appendChild(s);
            });
            n.parentNode.replaceChild(frag, n);
          } else if (n.nodeType === 1 && !n.classList.contains('manifesto_inline-media')) walk(n);
        });
      };
      walk(el);
      gsap.to($$('.word', el), { opacity: 1, stagger: .1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 45%', scrub: .6 } });
      $$('.manifesto_inline-media', el).forEach(function (m) { gsap.from(m, { width: 0, duration: 1.2, ease: 'expo.out', scrollTrigger: { trigger: m, start: 'top 75%' } }); });
    });

    // Marquee : accélère/inverse selon la vitesse de scroll
    $$('[data-marquee]').forEach(function (track) {
      var anim = track.getAnimations ? track.getAnimations()[0] : null;
      if (!anim) return;
      ScrollTrigger.create({ start: 0, end: 'max', onUpdate: function (self) {
        var v = self.getVelocity() / 300;
        anim.playbackRate = gsap.utils.clamp(-6, 6, 1 + Math.abs(v)) * (self.direction === -1 ? -1 : 1);
        clearTimeout(track.__t); track.__t = setTimeout(function () { anim.playbackRate = 1; }, 200);
      } });
    });

    // Programmes : défilement horizontal épinglé (desktop)
    ScrollTrigger.matchMedia({
      '(min-width: 992px)': function () {
        $$('[data-hscroll]').forEach(function (sec) {
          var track = $('[data-hscroll-track]', sec), pin = $('[data-hscroll-pin]', sec), bar = $('[data-hscroll-progress]', sec);
          var dist = function () { return Math.max(0, track.scrollWidth - innerWidth); };
          gsap.to(track, { x: function () { return -dist(); }, ease: 'none', scrollTrigger: {
            trigger: pin, start: 'center center', end: function () { return '+=' + dist(); }, pin: sec, scrub: .8, invalidateOnRefresh: true, anticipatePin: 1,
            onUpdate: function (self) { if (bar) bar.style.transform = 'scaleX(' + self.progress + ')'; }
          } });
          $$('.program-card_component', track).forEach(function (card, k) { gsap.from(card, { y: 80 + k * 10, rotate: 2, opacity: 0, duration: 1.2, ease: 'expo.out', delay: k * .06, scrollTrigger: { trigger: sec, start: 'top 70%' } }); });
        });
      }
    });

    // Vidéo qui s'ouvre au scroll
    $$('[data-expand]').forEach(function (wrap) {
      var media = $('[data-expand-media]', wrap), cap = $('[data-expand-caption]', wrap), title = $('[data-expand-title]', wrap), vid = $('video', wrap);
      var tl = gsap.timeline({ scrollTrigger: { trigger: wrap, start: 'top top', end: 'bottom bottom', scrub: .8 } });
      tl.fromTo(media, { clipPath: 'inset(18% 22% 18% 22% round 2rem)' }, { clipPath: 'inset(0% 0% 0% 0% round 0rem)', ease: 'none', duration: 1 }, 0)
        .fromTo(vid, { scale: 1.25 }, { scale: 1, ease: 'none', duration: 1 }, 0)
        .to(title, { scale: 1.6, opacity: 0, ease: 'none', duration: .6 }, 0)
        .to(cap, { opacity: 1, duration: .3 }, .7);
    });

    // Table de 5 : les places s'installent
    $$('[data-table-visual]').forEach(function (t) {
      gsap.from($$('[data-seat]', t), { scale: 0, opacity: 0, duration: .9, ease: 'back.out(2)', stagger: .12, scrollTrigger: { trigger: t, start: 'top 75%' } });
      gsap.from($('.table-visual_table', t), { scale: .6, rotate: -30, opacity: 0, duration: 1.4, ease: 'expo.out', scrollTrigger: { trigger: t, start: 'top 80%' } });
    });

    // Livres : arrivée en éventail
    $$('[data-books]').forEach(function (g) {
      gsap.from($$('.book_component', g), { y: 60, rotateX: -25, opacity: 0, duration: 1.1, stagger: .06, ease: 'expo.out', scrollTrigger: { trigger: g, start: 'top 85%' } });
    });

    // Footer : grand mot-symbole
    $$('[data-footer-wordmark]').forEach(function (w) {
      gsap.from(w, { yPercent: 40, opacity: 0, duration: 1.4, ease: 'expo.out', scrollTrigger: { trigger: w, start: 'top 98%' } });
    });

    // Hero d'accueil (après préchargement)
    var hero = $('[data-hero]');
    function playHero() {
      if (!hero) return;
      var tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
      tl.fromTo($('[data-hero-media]', hero), { clipPath: 'inset(12% 8% 12% 8% round 2.5rem)', scale: 1.05 }, { clipPath: 'inset(0% 0% 0% 0% round 0rem)', scale: 1, duration: 1.8, ease: 'expo.inOut' })
        .from($$('[data-hero-line]', hero), { yPercent: 110, rotate: 3, duration: 1.4, stagger: .12 }, '-=.9')
        .from($$('[data-hero-fade]', hero), { y: 30, opacity: 0, duration: 1.1, stagger: .08 }, '-=1')
        .from('.hero_badge', { scale: 0, rotate: -90, duration: 1.2, ease: 'back.out(1.6)' }, '-=1')
        .fromTo(nav, { y: 0, yPercent: -100, opacity: 0 }, { y: 0, yPercent: 0, opacity: 1, duration: 1, clearProps: 'transform,opacity' }, .6);
      // Sortie du hero au scroll
      gsap.to($('[data-hero-video]', hero), { yPercent: 18, scale: 1.18, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
      gsap.to('.hero_heading', { yPercent: -30, opacity: .2, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
    }

    // Préchargeur (1re visite de la session uniquement)
    var pre = $('[data-preloader]');
    if (pre && !doc.classList.contains('is-loaded')) {
      var count = $('[data-preloader-count]', pre), o = { v: 0 };
      if (window.__lenis) window.__lenis.stop();
      var tl = gsap.timeline({ onComplete: function () {
        pre.remove(); doc.classList.add('is-loaded');
        try { sessionStorage.setItem('xy-loaded', '1'); } catch (e) {}
        if (window.__lenis) window.__lenis.start();
      } });
      tl.to($$('.logo_steam', pre), { strokeDashoffset: 0, duration: 1.4, stagger: .2, ease: 'power2.inOut' }, 0)
        .from($('.logo_bubble', pre), { scale: 0, transformOrigin: '50% 80%', duration: .8, ease: 'back.out(2)' }, .5)
        .to(o, { v: 100, duration: 1.8, ease: 'power2.inOut', onUpdate: function () { count.textContent = Math.round(o.v); } }, 0)
        .to(pre, { yPercent: -100, duration: 1.1, ease: 'expo.inOut' }, 2)
        .add(playHero, 2.1);
    } else {
      if (pre) pre.remove();
      playHero();
    }

    // Recalcul après chargement des médias
    window.addEventListener('load', function () { ScrollTrigger.refresh(); });
  }

  // Lecture vidéo économe : pause hors écran
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        var v = en.target;
        if (en.isIntersecting) { if (v.preload === 'none') v.preload = 'auto'; var p = v.play(); if (p && p.catch) p.catch(function () {}); }
        else v.pause();
      });
    }, { rootMargin: '200px' });
    $$('video[autoplay]').forEach(function (v) { io.observe(v); });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
