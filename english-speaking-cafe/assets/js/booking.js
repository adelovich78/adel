/* =========================================================
   Assistant de réservation : niveau → objectif → programme →
   professeur → date & place (temps réel) → confirmation
   ========================================================= */
(function () {
  'use strict';
  var root = document.querySelector('[data-booking]');
  if (!root) return;
  var D = JSON.parse(document.getElementById('booking-data').textContent);
  var $ = function (s) { return root.querySelector(s); };
  var L = D.i18n || {};
  var fmt = function (str, o) { return String(str).replace(/:(\w+)/g, function (m, k) { return o && k in o ? o[k] : m; }); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  var state = { step: 0, level: null, goal: null, program: null, teacher: 0, session: null, sessions: [], day: null };
  var levelIdx = function () { return D.levels.findIndex(function (l) { return l.code === state.level; }); };

  /* --- Rendu des choix --- */
  function card(attrs, inner, selected, dim) {
    return '<button type="button" class="choice_card' + (selected ? ' is-selected' : '') + (dim ? ' is-dim' : '') + '" ' + attrs + '>' + inner + '</button>';
  }
  function renderLevels() {
    $('[data-levels]').innerHTML = D.levels.map(function (l) {
      return card('data-level="' + l.code + '"', '<span class="choice_level">' + l.code + '</span><span class="choice_desc">' + esc(l.label) + '</span>', state.level === l.code);
    }).join('');
  }
  function renderGoals() {
    $('[data-goals]').innerHTML = D.goals.map(function (g) {
      return card('data-goal="' + g.key + '"', '<span class="choice_title">' + esc(g.title) + '</span><span class="choice_desc">' + esc(g.desc) + '</span>', state.goal === g.key);
    }).join('');
  }
  function renderPrograms() {
    var li = levelIdx();
    var list = D.programs.slice().sort(function (a, b) { return score(b) - score(a); });
    function fits(p) { return li < 0 || (li >= p.min && li <= p.max); }
    function score(p) { return (fits(p) ? 2 : 0) + (p.goals.indexOf(state.goal) > -1 ? 1 : 0); }
    $('[data-programs]').innerHTML = list.map(function (p) {
      var lv = D.levels[p.min].code + '–' + D.levels[p.max].code;
      return card('data-program="' + p.slug + '"' + (fits(p) ? '' : ' disabled'),
        '<span class="text-size-tiny text-style-allcaps text-color-muted">' + esc(p.category) + '</span><span class="choice_title">' + esc(p.name) + '</span><span class="choice_desc">' + esc(p.tagline) + '</span><span class="choice_desc" style="margin-top:.4rem"><b>' + esc(p.price) + '</b> · ' + p.duration + ' ' + esc(L.min) + ' · ' + lv + (p.capacity > 1 ? ' · ' + esc(L.max) + ' ' + p.capacity : ' · ' + esc(L.private)) + '</span>' + (fits(p) ? '' : '<span class="badge is-red" style="margin-top:.4rem">' + esc(L.notFor) + ' ' + esc(state.level) + '</span>'),
        state.program === p.slug, !fits(p) || (state.goal && p.goals.indexOf(state.goal) < 0));
    }).join('');
  }
  function renderTeachers() {
    var html = card('data-teacher="0"', '<span class="choice_title">' + esc(L.anyTeacher) + '</span><span class="choice_desc">' + esc(L.mostAvailability) + '</span>', !state.teacher);
    html += D.teachers.map(function (t, i) {
      return card('data-teacher="' + t.id + '"', '<span class="quote_avatar mono-bg-' + (i % 6) + '" style="width:2.6rem;height:2.6rem;font-size:.8rem">' + esc(t.initials) + '</span><span class="choice_title">' + esc(t.name) + '</span><span class="choice_desc">' + esc(t.accent) + ' · ' + esc(t.tags) + '</span>', state.teacher === t.id);
    }).join('');
    $('[data-teachers]').innerHTML = html;
  }

  /* --- Séances (API temps réel) --- */
  function loadSessions() {
    $('[data-slots]').innerHTML = '<div class="empty_state loading_dots">' + esc(L.loading) + '</div>';
    $('[data-days]').innerHTML = '';
    var q = new URLSearchParams({ program: state.program || '', level: state.level || '', teacher: state.teacher || '' });
    fetch(D.api + '?' + q.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        state.sessions = j.data || [];
        var days = [];
        state.sessions.forEach(function (s) { if (days.indexOf(s.date) < 0) days.push(s.date); });
        if (state.session) { var pre = state.sessions.find(function (s) { return s.id === state.session.id || s.id === state.session; }); if (pre) { state.session = pre; state.day = pre.date; } else state.session = null; }
        if (!state.day || days.indexOf(state.day) < 0) state.day = days[0] || null;
        renderDays(days); renderSlots(); update();
      })
      .catch(function () { $('[data-slots]').innerHTML = '<div class="empty_state">' + esc(L.loadError) + '</div>'; });
  }
  function renderDays(days) {
    $('[data-days]').innerHTML = days.map(function (d) {
      var dt = new Date(d + 'T12:00:00');
      return '<button type="button" class="slot_day' + (d === state.day ? ' is-active' : '') + '" data-day="' + d + '"><small>' + dt.toLocaleDateString(D.locale, { weekday: 'short' }) + '</small><b>' + dt.getDate() + '</b><small>' + dt.toLocaleDateString(D.locale, { month: 'short' }) + '</small></button>';
    }).join('');
  }
  function renderSlots() {
    var list = state.sessions.filter(function (s) { return s.date === state.day; });
    if (!state.sessions.length) { $('[data-slots]').innerHTML = '<div class="empty_state">' + esc(L.noSessions) + '</div>'; return; }
    $('[data-slots]').innerHTML = list.map(function (s) {
      var dots = '<span class="seat_dot is-teacher" title="' + esc(L.teacher) + '"></span>';
      for (var k = 0; k < s.capacity; k++) dots += '<span class="seat_dot' + (k >= s.taken ? ' is-free' : '') + '"></span>';
      var full = s.seats_left <= 0;
      return '<button type="button" class="slot_item' + (state.session && state.session.id === s.id ? ' is-selected' : '') + '" data-session="' + s.id + '">' +
        '<span class="slot_time">' + esc(s.time) + '</span>' +
        '<span><b>' + esc(s.title || s.program) + '</b><br><span class="text-size-small text-color-muted">' + (s.teacher ? esc(fmt(L.withName, { name: s.teacher })) + ' · ' : '') + (s.level ? esc(s.level) + ' · ' : '') + (s.room ? esc(s.room) : '') + '</span><span class="seat_dots">' + dots + '</span></span>' +
        '<span style="text-align:right"><b>' + esc(s.price_label) + '</b><br>' + (full ? '<span class="badge is-amber">' + esc(fmt(L.waitlistWaiting, { n: s.waiting })) + '</span>' : '<span class="badge is-green">' + esc(fmt(s.seats_left > 1 ? L.seatsLeft : L.seatLeft, { n: s.seats_left })) + '</span>') + '</span></button>';
    }).join('');
  }

  /* --- Navigation entre étapes --- */
  function canGo(step) {
    return [true, !!state.level, !!state.goal, !!state.program, true, !!state.session][step];
  }
  function go(step) {
    if (step < 0 || step > 5) return;
    for (var i = 1; i <= step; i++) if (!canGo(i)) return;
    state.step = step;
    root.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.toggle('is-active', +p.dataset.panel === step); });
    root.querySelectorAll('[data-step-tab]').forEach(function (t) {
      var k = +t.dataset.stepTab;
      t.classList.toggle('is-active', k === step); t.classList.toggle('is-done', k < step);
      t.disabled = !(k <= step || canGoChain(k));
    });
    if (step === 2) renderPrograms();
    if (step === 3) renderTeachers();
    if (step === 4) loadSessions();
    $('[data-prev]').hidden = step === 0;
    $('[data-next]').hidden = step === 5;
    update();
    var top = root.getBoundingClientRect().top + window.scrollY - 110;
    if (window.__lenis) window.__lenis.scrollTo(top); else window.scrollTo({ top: top, behavior: 'smooth' });
  }
  function canGoChain(k) { for (var i = 1; i <= k; i++) if (!canGo(i)) return false; return true; }

  function update() {
    var p = D.programs.find(function (x) { return x.slug === state.program; });
    var t = D.teachers.find(function (x) { return x.id === state.teacher; });
    var g = D.goals.find(function (x) { return x.key === state.goal; });
    $('[data-s-level]').textContent = state.level || '—';
    $('[data-s-goal]').textContent = g ? g.title : '—';
    $('[data-s-program]').textContent = p ? p.name : '—';
    $('[data-s-teacher]').textContent = state.session && state.session.teacher ? state.session.teacher : (t ? t.name : L.any);
    $('[data-s-when]').textContent = state.session ? state.session.day + ' · ' + state.session.time : '—';
    $('[data-s-seats]').textContent = state.session ? (state.session.seats_left > 0 ? fmt(L.freeOf, { free: state.session.seats_left, cap: state.session.capacity }) : L.fullWaitlist) : '—';
    $('[data-s-price]').textContent = state.session ? state.session.price_label : (p ? p.price : '—');
    $('[data-next]').disabled = !canGo(state.step + 1);

    // Formulaire final
    var f = root.querySelector('[data-book-form]');
    if (f && state.session) {
      f.querySelector('[data-f-session]').value = state.session.id;
      f.querySelector('[data-f-level]').value = state.level || '';
      f.querySelector('[data-f-goal]').value = state.goal || '';
      var full = state.session.seats_left <= 0;
      f.querySelector('[data-waitlist-note]').hidden = !full;
      f.querySelector('[data-pay-options]').hidden = full;
      f.querySelector('[data-pay-title]').hidden = full;
      var credit = f.querySelector('[data-pay-credit]');
      if (credit) { var ok = p && p.credit; credit.hidden = !ok; if (!ok) { var s = f.querySelector('input[value=stripe]'); if (s) s.checked = true; } }
      f.querySelector('[data-submit]').firstChild.textContent = (full ? L.joinWaitlist : L.confirmSeat) + ' ';
    }
    // Lien de connexion qui ramène à la réservation
    root.querySelectorAll('[data-auth-link]').forEach(function (a) {
      var back = location.pathname + '?' + new URLSearchParams({ level: state.level || '', goal: state.goal || '', program: state.program || '', teacher: state.teacher || '', session: state.session ? state.session.id : '' }).toString();
      a.href = (a.dataset.authLink === 'login' ? D.loginUrl : D.registerUrl) + '?next=' + encodeURIComponent(back);
    });
  }

  /* --- Événements --- */
  root.addEventListener('click', function (e) {
    var b;
    if ((b = e.target.closest('[data-level]'))) { state.level = b.dataset.level; renderLevels(); update(); setTimeout(function () { go(1); }, 260); }
    else if ((b = e.target.closest('[data-goal]'))) { state.goal = b.dataset.goal; renderGoals(); update(); setTimeout(function () { go(2); }, 260); }
    else if ((b = e.target.closest('[data-program]'))) { if (b.disabled) return; state.program = b.dataset.program; state.session = null; renderPrograms(); update(); setTimeout(function () { go(3); }, 260); }
    else if ((b = e.target.closest('[data-teacher]'))) { state.teacher = +b.dataset.teacher; state.session = null; renderTeachers(); update(); setTimeout(function () { go(4); }, 260); }
    else if ((b = e.target.closest('[data-day]'))) { state.day = b.dataset.day; root.querySelectorAll('[data-day]').forEach(function (d) { d.classList.toggle('is-active', d === b); }); renderSlots(); }
    else if ((b = e.target.closest('[data-session]'))) { state.session = state.sessions.find(function (s) { return s.id === +b.dataset.session; }); renderSlots(); update(); setTimeout(function () { go(5); }, 300); }
    else if ((b = e.target.closest('[data-step-tab]'))) { if (!b.disabled) go(+b.dataset.stepTab); }
    else if (e.target.closest('[data-next]')) go(state.step + 1);
    else if (e.target.closest('[data-prev]')) go(state.step - 1);
  });
  var form = root.querySelector('[data-book-form]');
  if (form) form.addEventListener('submit', function () { var s = form.querySelector('[data-submit]'); s.disabled = true; s.classList.add('is-disabled'); });

  /* --- Pré-remplissage (liens profonds) --- */
  var P = D.pre || {};
  if (P.level && D.levels.some(function (l) { return l.code === P.level; })) state.level = P.level;
  if (P.goal && D.goals.some(function (g) { return g.key === P.goal; })) state.goal = P.goal;
  if (P.program && D.programs.some(function (p) { return p.slug === P.program; })) {
    state.program = P.program;
    var pr = D.programs.find(function (p) { return p.slug === P.program; });
    if (!state.goal) state.goal = pr.goals[0] || 'conversation';
    if (!state.level) state.level = D.levels[pr.min].code;
  }
  if (P.teacher) state.teacher = +P.teacher || 0;
  if (P.session) state.session = +P.session;
  renderLevels(); renderGoals();
  var start = 0;
  if (state.level) start = 1;
  if (state.level && state.goal) start = 2;
  if (state.level && state.goal && state.program) start = (P.teacher || P.session) ? 4 : 3;
  if (start) setTimeout(function () { go(start); }, 50); else update();
})();
