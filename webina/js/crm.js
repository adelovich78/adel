/* Webina — CRM : tableau de bord, clients, ventes, tâches, projets, dépenses */
(function () {
  'use strict';

  var W = window.WebinaAdmin;
  if (!W) return;
  var api = W.api, toast = W.toast, esc = W.esc;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  var STAGES = [
    ['new', 'Nouveau'], ['contacted', 'Contacté'], ['proposal', 'Proposition'], ['won', 'Gagné'], ['lost', 'Perdu']
  ];
  var STAGE = {}; STAGES.forEach(function (s) { STAGE[s[0]] = s[1]; });
  var STATUS = { lead: 'Prospect', client: 'Client', archived: 'Archivé' };
  var PSTATUS = { in_progress: 'En cours', delivered: 'Livré', paused: 'En pause', cancelled: 'Annulé' };
  var KIND = { todo: 'À faire', call: 'Appel', meeting: 'Réunion', email: 'E-mail' };
  var KIND_ICON = { todo: 'i-check-square', call: 'i-phone', meeting: 'i-calendar', email: 'i-mail' };
  var CAT = {
    ads: 'Publicité', domain: 'Noms de domaine', hosting: 'Hébergement & serveurs', software: 'Logiciels & abonnements',
    freelance: 'Sous-traitance', salary: 'Salaires', office: 'Bureau & loyer', equipment: 'Matériel', travel: 'Déplacements',
    bank: 'Banque & frais', tax: 'Impôts & taxes', other: 'Autre'
  };
  var PLATFORM = { instagram: 'Instagram', facebook: 'Facebook', youtube: 'YouTube', google: 'Google Ads', tiktok: 'TikTok', linkedin: 'LinkedIn' };
  var RECUR = { none: 'Une seule fois', monthly: 'Chaque mois', quarterly: 'Chaque trimestre', yearly: 'Chaque année' };
  var CAT_COLOR = { ads: '#2563eb', domain: '#0891b2', hosting: '#7c3aed', software: '#db2777', freelance: '#ea580c', salary: '#16a34a',
    office: '#ca8a04', equipment: '#4b5563', travel: '#0d9488', bank: '#64748b', tax: '#b91c1c', other: '#9ca3af' };

  var state = { clients: [], deals: [], tasks: [], projects: [], dash: null, filter: '', search: '', showDone: false, sheetId: null, exp: null, expCat: '' };

  function money(n, cur) {
    n = Number(n) || 0;
    if (!cur || cur === 'TRY') return Math.round(n).toLocaleString('tr-TR') + ' TL';
    if (cur === 'USD') return (n < 0 ? '−$' : '$') + Math.abs(Math.round(n)).toLocaleString('en-US');
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: cur, maximumFractionDigits: 0 }).format(n);
  }
  function d(s) { return s ? new Date(String(s).replace(' ', 'T')) : null; }
  function fmtDate(s) { var x = d(s); return x ? x.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: x.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined }) : '—'; }
  function fmtDue(s) {
    var x = d(s); if (!x) return 'Sans date';
    var today = new Date(); today.setHours(0, 0, 0, 0);
    var day = new Date(x); day.setHours(0, 0, 0, 0);
    var diff = Math.round((day - today) / 864e5);
    var time = x.getHours() || x.getMinutes() ? ' · ' + x.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '';
    if (diff === 0) return "Aujourd'hui" + time;
    if (diff === 1) return 'Demain' + time;
    if (diff === -1) return 'Hier' + time;
    if (diff < 0) return 'En retard · ' + fmtDate(s);
    return fmtDate(s) + time;
  }
  function ago(s) {
    var x = d(s); if (!x) return '—';
    var m = Math.round((Date.now() - x) / 6e4);
    if (m < 60) return m <= 1 ? "à l'instant" : 'il y a ' + m + ' min';
    var h = Math.round(m / 60); if (h < 24) return 'il y a ' + h + ' h';
    var j = Math.round(h / 24); if (j < 30) return 'il y a ' + j + ' j';
    return fmtDate(s);
  }
  function isOverdue(s) { var x = d(s); return x && x < new Date(new Date().setHours(0, 0, 0, 0)); }
  function toLocalInput(s) { var x = d(s); if (!x) return ''; x.setMinutes(x.getMinutes() - x.getTimezoneOffset()); return x.toISOString().slice(0, 16); }
  function icon(id) { return '<svg class="icon" aria-hidden="true"><use href="#' + id + '"/></svg>'; }
  function crm(path, opts) { return api('crm.php?' + path, opts); }

  /* ---------- Chargement ---------- */
  function load() {
    return Promise.all([
      crm('r=dashboard').then(function (x) { state.dash = x; }),
      crm('r=deals').then(function (x) { state.deals = x.items; }),
      crm('r=tasks&done=' + (state.showDone ? 1 : 0)).then(function (x) { state.tasks = x.items; }),
      crm('r=projects').then(function (x) { state.projects = x.items; }),
      crm('r=expenses&months=6').then(function (x) { state.exp = x; }),
      loadClients()
    ]).then(renderAll).catch(function (e) { toast(e.message); });
  }
  function loadClients() {
    return crm('r=clients&status=' + encodeURIComponent(state.filter) + '&q=' + encodeURIComponent(state.search))
      .then(function (x) { state.clients = x.items; });
  }
  function renderAll() { renderDashboard(); renderClients(); renderBoard(); renderTasks(); renderProjects(); renderExpenses(); renderBadge(); }
  function renderBadge() {
    var n = state.tasks.filter(function (t) { return !t.done_at && t.due_at && d(t.due_at) < new Date(new Date().setHours(24, 0, 0, 0)); }).length;
    var b = $('[data-count="tasks"]'); if (b) b.textContent = n || '';
  }

  /* ---------- Tableau de bord ---------- */
  function renderDashboard() {
    var x = state.dash; if (!x) return;
    var open = { n: 0, total: 0 }, stages = {};
    x.pipeline.forEach(function (p) {
      stages[p.stage] = stages[p.stage] || { n: 0, total: 0 };
      stages[p.stage].n += Number(p.n);
      if (p.currency === 'TRY') stages[p.stage].total += Number(p.total);
      if (['new', 'contacted', 'proposal'].indexOf(p.stage) > -1) { open.n += Number(p.n); if (p.currency === 'TRY') open.total += Number(p.total); }
    });
    var late = x.tasks.filter(function (t) { return isOverdue(t.due_at); }).length;
    var stats = [
      ['Ventes en cours', open.n, money(open.total)],
      ['Tâches en retard', late, late ? 'à traiter' : 'rien en retard', late ? 'is-warn' : ''],
      ['Encaissé ce mois', money(x.paidThisMonth), 'dépensé ' + money(x.spentThisMonth || 0)],
      ['Reste à encaisser', money(x.toCollect), 'projets en cours et livrés'],
      ['Résultat du mois', money(x.paidThisMonth - (x.spentThisMonth || 0)), 'encaissé − dépenses', x.paidThisMonth - (x.spentThisMonth || 0) < 0 ? 'is-warn' : '']
    ];
    var html = '<div class="admin_stats">' + stats.map(function (s) {
      return '<div class="admin_stat ' + (s[3] || '') + '"><p class="admin_stat-value">' + esc(s[1]) + '</p><p class="admin_stat-label">' + esc(s[0]) + (s[2] ? ' · <span class="text-color-muted">' + esc(s[2]) + '</span>' : '') + '</p></div>';
    }).join('') + '</div>';

    // Entonnoir des ventes
    var max = Math.max.apply(null, STAGES.slice(0, 3).map(function (s) { return (stages[s[0]] || {}).n || 0; }).concat([1]));
    html += '<div class="crm_grid"><section class="admin_card crm_funnel"><h2 class="heading-style-h5">Ventes en cours</h2>' +
      STAGES.slice(0, 4).map(function (s) {
        var v = stages[s[0]] || { n: 0, total: 0 };
        return '<button type="button" class="crm_funnel-row" data-goto="deals"><span>' + s[1] + '</span><span class="crm_funnel-bar"><span style="--w:' + (s[0] === 'won' ? 100 : Math.round(v.n / max * 100)) + '%" class="' + (s[0] === 'won' ? 'is-won' : '') + '"></span></span><span class="crm_funnel-n">' + v.n + '</span><span class="crm_funnel-total">' + money(v.total) + '</span></button>';
      }).join('') + '</section>';

    // Tâches de la semaine
    html += '<section class="admin_card"><div class="crm_card-head"><h2 class="heading-style-h5">À faire cette semaine</h2><button type="button" class="text-link" data-goto="tasks">Toutes les tâches' + icon('i-arrow') + '</button></div>' +
      (x.tasks.length ? '<ul class="crm_tasks" role="list">' + x.tasks.slice(0, 8).map(taskRow).join('') + '</ul>' : '<p class="crm_empty">Rien à faire pour le moment.</p>') + '</section>';

    // Renouvellements
    html += '<section class="admin_card"><h2 class="heading-style-h5">Renouvellements d\'hébergement (60 jours)</h2>' +
      (x.renewals.length ? '<ul class="crm_list" role="list">' + x.renewals.map(function (r) {
        return '<li><button type="button" class="crm_list-item" data-open-client="' + esc(r.client_id) + '"><span><b>' + esc(r.client_name) + '</b><span class="text-color-muted"> · ' + esc(r.name) + '</span></span><span class="' + (isOverdue(r.hosting_renewal) ? 'crm_late' : 'text-color-secondary') + '">' + fmtDate(r.hosting_renewal) + (r.hosting_amount ? ' · ' + money(r.hosting_amount, r.currency) : '') + '</span></button></li>';
      }).join('') + '</ul>' : '<p class="crm_empty">Aucun renouvellement proche.</p>') + '</section>';

    // Activité récente
    html += '<section class="admin_card"><h2 class="heading-style-h5">Activité récente</h2>' +
      (x.recent.length ? '<ul class="crm_feed" role="list">' + x.recent.map(function (a) {
        return '<li><button type="button" class="crm_feed-item" ' + (a.client_id ? 'data-open-client="' + esc(a.client_id) + '"' : '') + '><span class="crm_feed-dot is-' + esc(a.kind) + '"></span><span><b>' + esc(a.client_name || '') + '</b> ' + esc(String(a.text).split('\n')[0]) + '</span><span class="text-color-muted">' + ago(a.created_at) + '</span></button></li>';
      }).join('') + '</ul>' : '<p class="crm_empty">Pas encore d\'activité.</p>') + '</section></div>';
    $('[data-crm-dashboard]').innerHTML = html;
  }

  /* ---------- Clients ---------- */
  function renderClients() {
    var tb = $('[data-client-rows]');
    if (!state.clients.length) { tb.innerHTML = '<tr><td colspan="5" class="is-muted">Aucun client' + (state.search ? ' pour « ' + esc(state.search) + ' »' : '') + '</td></tr>'; return; }
    tb.innerHTML = state.clients.map(function (c) {
      var busy = [];
      if (Number(c.open_deals)) busy.push(c.open_deals + ' vente' + (c.open_deals > 1 ? 's' : ''));
      if (Number(c.open_tasks)) busy.push(c.open_tasks + ' tâche' + (c.open_tasks > 1 ? 's' : ''));
      return '<tr class="crm_row" data-open-client="' + esc(c.id) + '" tabindex="0">' +
        '<td><span class="crm_name">' + (c.kind === 'company' ? '<span class="crm_avatar is-company">' : '<span class="crm_avatar">') + esc(initials(c.name)) + '</span><span><b>' + esc(c.name) + '</b>' + (c.company && c.kind !== 'company' ? '<span class="crm_sub">' + esc(c.company) + '</span>' : '') + '</span></span></td>' +
        '<td><span class="crm_sub">' + esc(c.email || '') + '</span><span class="crm_sub">' + esc(c.phone || '') + '</span></td>' +
        '<td><span class="admin_badge is-' + esc(c.status) + '">' + esc(STATUS[c.status] || c.status) + '</span></td>' +
        '<td class="is-muted">' + (busy.join(' · ') || '—') + '</td>' +
        '<td class="is-muted">' + ago(c.last_activity_at || c.created_at) + '</td></tr>';
    }).join('');
  }
  function initials(n) { return String(n || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase(); }
  var searchTimer;
  $('[data-client-search]').addEventListener('input', function (e) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () { state.search = e.target.value.trim(); loadClients().then(renderClients); }, 200);
  });
  $('[data-client-filter]').addEventListener('change', function (e) { state.filter = e.target.value; loadClients().then(renderClients); });

  /* ---------- Ventes : tableau à colonnes ---------- */
  function renderBoard() {
    $('[data-board]').innerHTML = STAGES.map(function (s) {
      var items = state.deals.filter(function (x) { return x.stage === s[0]; });
      var total = items.reduce(function (a, x) { return a + (x.currency === 'TRY' ? Number(x.amount) : 0); }, 0);
      return '<section class="crm_col is-' + s[0] + '" data-stage="' + s[0] + '"><header class="crm_col-head"><span>' + s[1] + ' <span class="text-color-muted">' + items.length + '</span></span><span class="crm_col-total">' + money(total) + '</span></header>' +
        '<div class="crm_col-body" data-drop="' + s[0] + '">' + items.map(dealCard).join('') + '</div></section>';
    }).join('');
  }
  function dealCard(x) {
    return '<article class="crm_card" draggable="true" data-deal="' + esc(x.id) + '" data-open-client="' + esc(x.client_id) + '" tabindex="0">' +
      '<p class="crm_card-title">' + esc(x.title) + '</p><p class="crm_card-client">' + esc(x.client_name) + '</p>' +
      '<div class="crm_card-foot"><span class="crm_card-amount">' + (Number(x.amount) ? money(x.amount, x.currency) : '—') + '</span><span class="text-color-muted">' + ago(x.stage_changed_at) + '</span></div>' +
      '<select class="crm_card-stage" data-stage-select aria-label="Étape">' + STAGES.map(function (s) { return '<option value="' + s[0] + '"' + (s[0] === x.stage ? ' selected' : '') + '>' + s[1] + '</option>'; }).join('') + '</select>' +
      '</article>';
  }
  function moveDeal(id, stage) {
    var deal = state.deals.filter(function (x) { return x.id === id; })[0];
    if (!deal || deal.stage === stage) return;
    var from = deal.stage;
    deal.stage = stage; deal.stage_changed_at = new Date().toISOString();
    renderBoard();
    var card = $('[data-deal="' + id + '"]');
    if (card && !reduceMotion.matches) card.classList.add('is-landed');
    crm('r=deals&id=' + id, { method: 'PATCH', body: { stage: stage } }).then(function () {
      if (stage === 'won') toast('Gagné · projet créé automatiquement');
      else toast('Déplacé : ' + STAGE[stage]);
      return load();
    }).catch(function (e) { deal.stage = from; renderBoard(); toast(e.message); });
  }
  var board = $('[data-board]');
  board.addEventListener('dragstart', function (e) {
    var c = e.target.closest('[data-deal]'); if (!c) return;
    e.dataTransfer.setData('text/plain', c.dataset.deal);
    e.dataTransfer.effectAllowed = 'move';
    requestAnimationFrame(function () { c.classList.add('is-dragging'); });
  });
  board.addEventListener('dragend', function (e) {
    var c = e.target.closest('[data-deal]'); if (c) c.classList.remove('is-dragging');
    $$('.crm_col.is-over').forEach(function (x) { x.classList.remove('is-over'); });
  });
  board.addEventListener('dragover', function (e) {
    var col = e.target.closest('.crm_col'); if (!col) return;
    e.preventDefault();
    $$('.crm_col.is-over').forEach(function (x) { if (x !== col) x.classList.remove('is-over'); });
    col.classList.add('is-over');
  });
  board.addEventListener('drop', function (e) {
    var col = e.target.closest('.crm_col'); if (!col) return;
    e.preventDefault();
    col.classList.remove('is-over');
    moveDeal(e.dataTransfer.getData('text/plain'), col.dataset.stage);
  });
  board.addEventListener('change', function (e) {
    if (e.target.matches('[data-stage-select]')) moveDeal(e.target.closest('[data-deal]').dataset.deal, e.target.value);
  });
  board.addEventListener('click', function (e) { if (e.target.closest('[data-stage-select]')) e.stopPropagation(); }, true);

  /* ---------- Tâches ---------- */
  function taskRow(t) {
    var late = !t.done_at && isOverdue(t.due_at);
    var ctx = [t.client_name, t.deal_title || t.project_name].filter(Boolean).join(' · ');
    return '<li class="crm_task' + (t.done_at ? ' is-done' : '') + '" data-task="' + esc(t.id) + '">' +
      '<button type="button" class="crm_check" data-toggle-task aria-pressed="' + (t.done_at ? 'true' : 'false') + '" aria-label="Terminer">' + icon('i-check') + '</button>' +
      '<span class="crm_task-main"><span class="crm_task-title">' + icon(KIND_ICON[t.kind] || 'i-check-square') + esc(t.title) + (t.auto_key ? ' <span class="crm_auto" title="Créée automatiquement">auto</span>' : '') + '</span>' +
      (ctx ? '<button type="button" class="crm_task-ctx" ' + (t.client_id ? 'data-open-client="' + esc(t.client_id) + '"' : '') + '>' + esc(ctx) + '</button>' : '') + '</span>' +
      '<span class="crm_task-due' + (late ? ' crm_late' : '') + '">' + (t.done_at ? 'Fait ' + ago(t.done_at) : fmtDue(t.due_at)) + '</span>' +
      '<button type="button" class="icon-button is-danger crm_task-del" data-delete-task aria-label="Supprimer">' + icon('i-trash') + '</button></li>';
  }
  function renderTasks() {
    var box = $('[data-task-groups]');
    if (state.showDone) {
      box.innerHTML = state.tasks.length ? '<ul class="crm_tasks" role="list">' + state.tasks.map(taskRow).join('') + '</ul>' : '<p class="admin_empty">Aucune tâche terminée</p>';
      return;
    }
    var now = new Date(), today = new Date(now); today.setHours(0, 0, 0, 0);
    var tomorrow = new Date(today.getTime() + 864e5), week = new Date(today.getTime() + 7 * 864e5);
    var groups = [['En retard', []], ["Aujourd'hui", []], ['Cette semaine', []], ['Plus tard', []], ['Sans date', []]];
    state.tasks.forEach(function (t) {
      var x = d(t.due_at);
      if (!x) groups[4][1].push(t);
      else if (x < today) groups[0][1].push(t);
      else if (x < tomorrow) groups[1][1].push(t);
      else if (x < week) groups[2][1].push(t);
      else groups[3][1].push(t);
    });
    var html = groups.filter(function (g) { return g[1].length; }).map(function (g, i) {
      return '<section class="crm_group' + (g[0] === 'En retard' ? ' is-late' : '') + '"><h2 class="crm_group-title">' + g[0] + ' <span class="text-color-muted">' + g[1].length + '</span></h2><ul class="crm_tasks" role="list">' + g[1].map(taskRow).join('') + '</ul></section>';
    }).join('');
    box.innerHTML = html || '<p class="admin_empty">Tout est fait. Les nouvelles demandes du site créent leurs tâches automatiquement.</p>';
  }
  function toggleTask(id, li) {
    var done = !li.classList.contains('is-done');
    li.classList.toggle('is-done', done);
    li.querySelector('[data-toggle-task]').setAttribute('aria-pressed', String(done));
    crm('r=tasks&id=' + id, { method: 'PATCH', body: { done: done } }).then(function () {
      if (done) toast('Tâche terminée');
      setTimeout(function () { load().then(function () { if (state.sheetId) openClient(state.sheetId, true); }); }, done ? 450 : 0);
    }).catch(function (e) { toast(e.message); });
  }
  $('[data-show-done]').addEventListener('change', function (e) {
    state.showDone = e.target.checked;
    crm('r=tasks&done=' + (state.showDone ? 1 : 0)).then(function (x) { state.tasks = x.items; renderTasks(); });
  });
  $('[data-quick-task]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;
    crm('r=tasks', { method: 'POST', body: { title: f.title.value, due_at: f.due_at.value || null } }).then(function () {
      f.reset(); toast('Tâche ajoutée'); load();
    }).catch(function (ex) { toast(ex.message); });
  });

  /* ---------- Projets ---------- */
  function renderProjects() {
    var tb = $('[data-project-rows]');
    if (!state.projects.length) { tb.innerHTML = '<tr><td colspan="6" class="is-muted">Aucun projet. Une vente « Gagné » crée son projet automatiquement.</td></tr>'; return; }
    tb.innerHTML = state.projects.map(function (p) {
      var rest = Number(p.amount) - Number(p.paid);
      return '<tr class="crm_row" data-open-client="' + esc(p.client_id) + '" tabindex="0"><td><b>' + esc(p.name) + '</b></td><td>' + esc(p.client_name) + '</td>' +
        '<td><span class="admin_badge is-p-' + esc(p.status) + '">' + esc(PSTATUS[p.status] || p.status) + '</span></td>' +
        '<td>' + money(p.amount, p.currency) + '</td><td class="' + (rest > 0 ? 'crm_due' : 'is-muted') + '">' + (rest > 0 ? money(rest, p.currency) : 'Soldé') + '</td>' +
        '<td class="' + (p.hosting_renewal && isOverdue(p.hosting_renewal) ? 'crm_late' : 'is-muted') + '">' + (p.hosting_renewal ? fmtDate(p.hosting_renewal) : '—') + '</td></tr>';
    }).join('');
  }

  /* ---------- Dépenses ---------- */
  function monthLabel(m) { var p = m.split('-'); return new Date(+p[0], +p[1] - 1, 1).toLocaleDateString('fr-FR', { month: 'short' }); }
  function renderExpenses() {
    var box = $('[data-expenses]'), x = state.exp;
    if (!box || !x) return;
    var keys = Object.keys(x.months), cur = x.months[keys[keys.length - 1]], prev = x.months[keys[keys.length - 2]] || { total: 0 };
    var adsCur = Object.keys(cur.ads).reduce(function (a, k) { return a + cur.ads[k]; }, 0);
    // Coût par prospect : prospects déclarés par les plateformes, sinon ceux arrivés par le site
    var leadsN = cur.leads || cur.siteLeads;
    var cpl = leadsN ? adsCur / leadsN : 0;
    var leadsTxt = (cur.leads ? cur.leads + ' déclaré' + (cur.leads > 1 ? 's' : '') + ' par les plateformes · ' : '') + cur.siteLeads + ' arrivé' + (cur.siteLeads > 1 ? 's' : '') + ' par le site';
    var trend = prev.total ? Math.round((cur.total - prev.total) / prev.total * 100) : null;
    var stats = [
      ['Dépensé ce mois', money(cur.total), trend === null ? '' : (trend >= 0 ? '+' : '') + trend + ' % vs mois dernier'],
      ['Publicité ce mois', money(adsCur), Object.keys(cur.ads).map(function (k) { return (PLATFORM[k] || k) + ' ' + money(cur.ads[k]); }).join(' · ')],
      ['Coût par prospect', cpl ? money(cpl) : '—', leadsTxt],
      ['Résultat du mois', money(cur.revenue - cur.total), 'encaissé ' + money(cur.revenue), cur.revenue - cur.total < 0 ? 'is-warn' : '']
    ];
    var html = '<div class="admin_stats is-4">' + stats.map(function (st) {
      return '<div class="admin_stat ' + (st[3] || '') + '"><p class="admin_stat-value">' + esc(st[1]) + '</p><p class="admin_stat-label">' + esc(st[0]) + (st[2] ? '<span class="crm_sub">' + esc(st[2]) + '</span>' : '') + '</p></div>';
    }).join('') + '</div>';

    // 6 derniers mois : dépenses empilées par catégorie, encaissé en regard
    var max = Math.max.apply(null, keys.map(function (k) { return Math.max(x.months[k].total, x.months[k].revenue); }).concat([1]));
    var cats = Object.keys(CAT).filter(function (c) { return keys.some(function (k) { return x.months[k].byCat[c]; }); });
    html += '<div class="crm_grid"><section class="admin_card"><h2 class="heading-style-h5">Dépenses et encaissements (6 mois)</h2><div class="crm_chart">' + keys.map(function (k) {
      var m = x.months[k];
      return '<div class="crm_chart-col" title="' + esc(monthLabel(k)) + ' : dépensé ' + esc(money(m.total)) + ', encaissé ' + esc(money(m.revenue)) + '">' +
        '<div class="crm_chart-bars"><span class="crm_chart-stack" style="--h:' + (m.total / max * 100) + '%">' +
        cats.map(function (c) { return m.byCat[c] ? '<i style="flex:' + m.byCat[c] + ';background:' + CAT_COLOR[c] + '"></i>' : ''; }).join('') + '</span>' +
        '<span class="crm_chart-rev" style="--h:' + (m.revenue / max * 100) + '%"></span></div><span class="crm_chart-label">' + esc(monthLabel(k)) + '</span></div>';
    }).join('') + '</div><ul class="crm_legend" role="list">' + cats.map(function (c) { return '<li><i style="background:' + CAT_COLOR[c] + '"></i>' + esc(CAT[c]) + '</li>'; }).join('') +
      '<li><i class="is-rev"></i>Encaissé</li></ul></section>';

    // Répartition du mois
    var byCat = Object.keys(cur.byCat).sort(function (a, b) { return cur.byCat[b] - cur.byCat[a]; });
    html += '<section class="admin_card"><h2 class="heading-style-h5">Ce mois par catégorie</h2>' + (byCat.length ? '<ul class="crm_split" role="list">' + byCat.map(function (c) {
      return '<li><span><i style="background:' + CAT_COLOR[c] + '"></i>' + esc(CAT[c]) + '</span><span class="crm_split-bar"><span style="--w:' + (cur.byCat[c] / cur.total * 100) + '%;background:' + CAT_COLOR[c] + '"></span></span><b>' + money(cur.byCat[c]) + '</b></li>';
    }).join('') + '</ul>' : '<p class="crm_empty">Aucune dépense ce mois-ci.</p>') + '</section></div>';

    // Abonnements récurrents
    html += '<section class="admin_card"><div class="crm_card-head"><h2 class="heading-style-h5">Abonnements et renouvellements</h2><span class="text-size-small text-color-secondary">' +
      money(x.subscriptions.reduce(function (a, e) { return a + e.amount_try * ({ monthly: 12, quarterly: 4, yearly: 1 }[e.recurrence] || 0); }, 0)) + ' / an</span></div>' +
      (x.subscriptions.length ? '<ul class="crm_list" role="list">' + x.subscriptions.map(function (e) {
        return '<li class="crm_sub-line" data-expense="' + esc(e.id) + '"><span>' + icon('i-repeat') + '<b>' + esc(e.label) + '</b><span class="text-color-muted"> · ' + esc(CAT[e.category]) + (e.supplier ? ' · ' + esc(e.supplier) : '') + '</span></span>' +
          '<span class="crm_inline"><span class="' + (isOverdue(e.next_due) ? 'crm_late' : 'text-color-secondary') + '">' + esc(RECUR[e.recurrence]) + ' · prochain ' + fmtDate(e.next_due) + '</span><b>' + money(e.amount, e.currency) + '</b>' +
          '<button type="button" class="button is-small is-secondary" data-stop-sub>Arrêter</button></span></li>';
      }).join('') + '</ul>' : '<p class="crm_empty">Aucun abonnement. Choisissez « Chaque mois » ou « Chaque année » en ajoutant une dépense (domaine, hébergement, logiciel…).</p>') + '</section>';

    // Liste des dépenses
    var items = x.items.filter(function (e) { return !state.expCat || e.category === state.expCat; });
    html += '<div class="admin_toolbar"><div class="choice_group" data-exp-filter><label class="choice_chip"><input type="radio" name="ef" value=""' + (state.expCat ? '' : ' checked') + '><span>Toutes</span></label>' +
      Object.keys(CAT).filter(function (c) { return x.items.some(function (e) { return e.category === c; }); }).map(function (c) {
        return '<label class="choice_chip"><input type="radio" name="ef" value="' + c + '"' + (state.expCat === c ? ' checked' : '') + '><span>' + esc(CAT[c]) + '</span></label>';
      }).join('') + '</div><span class="text-size-small text-color-secondary">' + items.length + ' dépense' + (items.length > 1 ? 's' : '') + ' · ' + money(items.reduce(function (a, e) { return a + e.amount_try; }, 0)) + '</span></div>';
    html += '<div class="admin_table-wrap"><table class="admin_table"><thead><tr><th>Date</th><th>Dépense</th><th>Catégorie</th><th>Résultats</th><th class="is-num">Montant</th><th class="is-actions"><span class="sr-only">Actions</span></th></tr></thead><tbody>' +
      (items.length ? items.map(function (e) {
        var res = [e.impressions ? Number(e.impressions).toLocaleString('fr-FR') + ' vues' : '', e.clicks ? Number(e.clicks).toLocaleString('fr-FR') + ' clics' : '', e.leads ? e.leads + ' prospects' : ''].filter(Boolean).join(' · ');
        return '<tr data-expense="' + esc(e.id) + '"><td class="is-muted">' + fmtDate(e.spent_on) + '</td>' +
          '<td><b>' + esc(e.label) + '</b>' + (e.parent_id ? ' ' + icon('i-repeat') : '') + '<span class="crm_sub">' + esc([e.supplier, e.client_name ? 'pour ' + e.client_name : '', e.note].filter(Boolean).join(' · ')) + '</span></td>' +
          '<td><span class="crm_cat"><i style="background:' + CAT_COLOR[e.category] + '"></i>' + esc(CAT[e.category]) + (e.platform ? ' · ' + esc(PLATFORM[e.platform] || e.platform) : '') + '</span></td>' +
          '<td class="is-muted">' + (res || '—') + '</td>' +
          '<td class="is-num"><b>' + money(e.amount, e.currency) + '</b>' + (e.currency !== 'TRY' ? '<span class="crm_sub">≈ ' + money(e.amount_try) + '</span>' : '') + '</td>' +
          '<td class="is-actions"><span class="admin_row-actions"><button type="button" class="icon-button" data-edit-expense aria-label="Modifier">' + icon('i-edit') + '</button>' +
          '<button type="button" class="icon-button is-danger" data-delete-expense aria-label="Supprimer">' + icon('i-trash') + '</button></span></td></tr>';
      }).join('') : '<tr><td colspan="6" class="is-muted">Aucune dépense sur cette période.</td></tr>') + '</tbody></table></div>';
    box.innerHTML = html;
  }
  $('[data-expenses]').addEventListener('change', function (e) {
    if (e.target.name === 'ef') { state.expCat = e.target.value; renderExpenses(); }
  });
  $('[data-expenses]').addEventListener('click', function (e) {
    var row = e.target.closest('[data-expense]'); if (!row) return;
    var id = row.dataset.expense;
    var item = (state.exp.items.concat(state.exp.subscriptions)).filter(function (x) { return x.id === id; })[0];
    if (e.target.closest('[data-delete-expense]')) {
      if (!W.confirm('Supprimer cette dépense ?')) return;
      crm('r=expenses&id=' + id, { method: 'DELETE' }).then(function () { toast('Dépense supprimée'); load(); });
    } else if (e.target.closest('[data-stop-sub]')) {
      if (!W.confirm('Arrêter cet abonnement ? Les dépenses déjà enregistrées restent.')) return;
      crm('r=expenses&id=' + id + '&stop=1', { method: 'DELETE' }).then(function () { toast('Abonnement arrêté'); load(); });
    } else if (e.target.closest('[data-edit-expense]') && item) {
      expenseForm(item);
    }
  });
  function expenseForm(e) {
    e = e || {};
    openModal(e.id ? 'Modifier la dépense' : 'Nouvelle dépense', [
      { label: 'Catégorie', name: 'category', type: 'select', options: Object.keys(CAT).map(function (k) { return [k, CAT[k]]; }), value: e.category || 'ads' },
      { label: 'Plateforme', name: 'platform', type: 'select', options: Object.keys(PLATFORM).map(function (k) { return [k, PLATFORM[k]]; }), value: e.platform || 'instagram', show: 'ads' },
      { label: 'Libellé', name: 'label', value: e.label || '', full: true, placeholder: 'ex. Campagne Instagram novembre, webina.com.tr, Adobe…' },
      { label: 'Montant', name: 'amount', type: 'number', step: '0.01', required: true, value: e.amount ? Number(e.amount) : '' },
      { label: 'Devise', name: 'currency', type: 'select', options: [['TRY', 'TL'], ['USD', '$'], ['EUR', '€']], value: e.currency || 'TRY' },
      { label: 'Date', name: 'spent_on', type: 'date', value: e.spent_on || new Date().toISOString().slice(0, 10) },
      { label: 'Fréquence', name: 'recurrence', type: 'select', options: Object.keys(RECUR).map(function (k) { return [k, RECUR[k]]; }), value: e.recurrence || 'none', hide: !!e.parent_id },
      { label: 'Fournisseur', name: 'supplier', value: e.supplier || '', placeholder: 'ex. Meta, Google, Hostinger, GoDaddy' },
      { label: 'Vues (impressions)', name: 'impressions', type: 'number', value: e.impressions || '', show: 'ads' },
      { label: 'Clics', name: 'clicks', type: 'number', value: e.clicks || '', show: 'ads' },
      { label: 'Prospects obtenus', name: 'leads', type: 'number', value: e.leads || '', show: 'ads' },
      { label: 'Pour un client (facultatif)', name: 'client_id', type: 'client', value: e.client_id || '', label2: e.client_name || '', full: true },
      { label: 'Note', name: 'note', value: e.note || '', full: true }
    ], function (data) {
      if (!data.client_id) data.client_id = null;
      if (data.category !== 'ads') { data.platform = null; delete data.impressions; delete data.clicks; delete data.leads; }
      var req = e.id ? crm('r=expenses&id=' + e.id, { method: 'PATCH', body: data }) : crm('r=expenses', { method: 'POST', body: data });
      return req.then(function () { toast(e.id ? 'Dépense mise à jour' : 'Dépense ajoutée'); load(); });
    });
  }

  /* ---------- Fiche client (panneau latéral) ---------- */
  var sheet = $('[data-sheet]'), sheetPanel = $('[data-sheet-panel]'), lastFocus = null;
  function openClient(id, refresh) {
    state.sheetId = id;
    return Promise.all([crm('r=clients&id=' + id), api('clientarea.php?r=client&id=' + id).catch(function () { return null; })]).then(function (res) {
      var x = res[0]; x.portal = res[1];
      sheetPanel.innerHTML = renderSheet(x);
      if (!refresh) {
        lastFocus = document.activeElement;
        sheet.hidden = false;
        sheet.classList.add('is-closed');
        requestAnimationFrame(function () { requestAnimationFrame(function () { sheet.classList.remove('is-closed'); }); });
        sheetPanel.focus({ preventScroll: true });
      }
    }).catch(function (e) { toast(e.message); });
  }
  function closeSheet() {
    if (sheet.hidden) return;
    sheet.classList.add('is-closed');
    state.sheetId = null;
    setTimeout(function () { sheet.hidden = true; }, reduceMotion.matches ? 0 : 280);
    if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
  }
  function field(label, name, value, type) {
    var input = type === 'textarea'
      ? '<textarea class="form_input is-area" name="' + name + '" rows="3">' + esc(value || '') + '</textarea>'
      : '<input class="form_input" name="' + name + '" type="' + (type || 'text') + '" value="' + esc(value || '') + '">';
    return '<label class="crm_field"><span class="form_label">' + label + '</span>' + input + '</label>';
  }
  function renderSheet(x) {
    var c = x.client;
    var openDeals = x.deals.filter(function (dl) { return ['won', 'lost'].indexOf(dl.stage) < 0; });
    var html = '<header class="crm_sheet-head"><span class="crm_avatar is-lg' + (c.kind === 'company' ? ' is-company' : '') + '">' + esc(initials(c.name)) + '</span>' +
      '<div class="crm_sheet-id"><h2 class="crm_sheet-name">' + esc(c.name) + '</h2><p class="text-size-small text-color-secondary">' +
      [c.company && c.kind !== 'company' ? c.company : null, c.source, 'créé ' + fmtDate(c.created_at)].filter(Boolean).map(esc).join(' · ') +
      (x.parent ? ' · <button type="button" class="text-link" data-open-client="' + esc(x.parent.id) + '">' + esc(x.parent.name) + '</button>' : '') + '</p></div>' +
      '<button type="button" class="icon-button" data-sheet-close aria-label="Fermer">' + icon('i-x') + '</button></header>';

    // Actions rapides
    html += '<div class="crm_actions">' +
      '<select class="form_input is-select crm_status" data-client-status aria-label="Statut">' + Object.keys(STATUS).map(function (k) { return '<option value="' + k + '"' + (k === c.status ? ' selected' : '') + '>' + STATUS[k] + '</option>'; }).join('') + '</select>' +
      (c.phone ? '<a class="button is-small is-secondary" href="tel:' + esc(c.phone.replace(/\s/g, '')) + '">' + icon('i-phone') + 'Appeler</a>' +
        '<a class="button is-small is-secondary" target="_blank" rel="noopener" href="https://wa.me/' + esc(c.phone.replace(/\D/g, '')) + '">WhatsApp</a>' : '') +
      (c.email ? '<a class="button is-small is-secondary" href="mailto:' + esc(c.email) + '">' + icon('i-mail') + 'E-mail</a>' : '') +
      '</div>';

    // Coordonnées (formulaire enregistré à la sortie de chaque champ)
    html += '<section class="crm_section"><h3 class="crm_section-title">Coordonnées</h3><form class="crm_fields" data-client-form data-id="' + esc(c.id) + '">' +
      field('Nom', 'name', c.name) + field('Société', 'company', c.company) + field('E-mail', 'email', c.email, 'email') + field('Téléphone', 'phone', c.phone, 'tel') +
      field('Secteur', 'sector', c.sector) + field('Ville', 'city', c.city) + field('Site web', 'website', c.website, 'url') +
      '<label class="crm_field"><span class="form_label">Type</span><select class="form_input is-select" name="kind"><option value="person"' + (c.kind === 'person' ? ' selected' : '') + '>Personne</option><option value="company"' + (c.kind === 'company' ? ' selected' : '') + '>Société</option></select></label>' +
      '<div class="crm_field is-full">' + field('Notes', 'notes', c.notes, 'textarea') + '</div></form></section>';

    if (x.contacts.length) {
      html += '<section class="crm_section"><h3 class="crm_section-title">Contacts</h3><ul class="crm_list" role="list">' + x.contacts.map(function (p) {
        return '<li><button type="button" class="crm_list-item" data-open-client="' + esc(p.id) + '"><b>' + esc(p.name) + '</b><span class="text-color-muted">' + esc(p.email || p.phone || '') + '</span></button></li>';
      }).join('') + '</ul></section>';
    }

    // Ventes
    html += '<section class="crm_section"><div class="crm_section-head"><h3 class="crm_section-title">Ventes</h3><button type="button" class="button is-small is-secondary" data-crm-new="deal" data-client="' + esc(c.id) + '">' + icon('i-plus') + 'Vente</button></div>' +
      (x.deals.length ? '<ul class="crm_list" role="list">' + x.deals.map(function (dl) {
        return '<li class="crm_deal-line" data-deal-line="' + esc(dl.id) + '"><span><b>' + esc(dl.title) + '</b><span class="text-color-muted"> · ' + (Number(dl.amount) ? money(dl.amount, dl.currency) : '—') + '</span></span>' +
          '<span class="crm_inline"><select class="form_input is-select crm_mini" data-deal-stage aria-label="Étape">' + STAGES.map(function (s) { return '<option value="' + s[0] + '"' + (s[0] === dl.stage ? ' selected' : '') + '>' + s[1] + '</option>'; }).join('') + '</select>' +
          '<button type="button" class="icon-button" data-edit-deal aria-label="Modifier">' + icon('i-edit') + '</button></span></li>';
      }).join('') + '</ul>' : '<p class="crm_empty">Aucune vente.</p>') + '</section>';

    // Tâches
    html += '<section class="crm_section"><h3 class="crm_section-title">Tâches</h3>' +
      '<form class="crm_quick is-compact" data-sheet-task data-client="' + esc(c.id) + '" data-deal="' + esc(openDeals[0] ? openDeals[0].id : '') + '"><input class="form_input" name="title" placeholder="Nouvelle tâche" required aria-label="Nouvelle tâche"><input class="form_input" name="due_at" type="datetime-local" aria-label="Échéance"><button class="button is-small" type="submit">Ajouter</button></form>' +
      (x.tasks.length ? '<ul class="crm_tasks" role="list">' + x.tasks.map(taskRow).join('') + '</ul>' : '') + '</section>';

    // Projets et paiements
    html += '<section class="crm_section"><div class="crm_section-head"><h3 class="crm_section-title">Projets</h3><button type="button" class="button is-small is-secondary" data-crm-new="project" data-client="' + esc(c.id) + '">' + icon('i-plus') + 'Projet</button></div>' +
      (x.projects.length ? x.projects.map(function (p) {
        var rest = Number(p.amount) - Number(p.paid);
        var pays = x.payments.filter(function (pay) { return pay.project_id === p.id; });
        return '<div class="crm_project" data-project="' + esc(p.id) + '"><div class="crm_project-head"><b>' + esc(p.name) + '</b>' +
          '<span class="crm_inline"><select class="form_input is-select crm_mini" data-project-status aria-label="Statut">' + Object.keys(PSTATUS).map(function (k) { return '<option value="' + k + '"' + (k === p.status ? ' selected' : '') + '>' + PSTATUS[k] + '</option>'; }).join('') + '</select>' +
          '<button type="button" class="icon-button" data-edit-project aria-label="Modifier">' + icon('i-edit') + '</button></span></div>' +
          '<div class="crm_money"><span>Total <b>' + money(p.amount, p.currency) + '</b></span><span>Payé <b>' + money(p.paid, p.currency) + '</b></span><span class="' + (rest > 0 ? 'crm_due' : '') + '">Reste <b>' + money(Math.max(0, rest), p.currency) + '</b></span>' +
          '<span class="crm_progress"><span style="--w:' + (Number(p.amount) ? Math.min(100, Math.round(Number(p.paid) / Number(p.amount) * 100)) : 0) + '%"></span></span></div>' +
          '<p class="text-size-small text-color-secondary">' + [p.start_date ? 'Début ' + fmtDate(p.start_date) : '', p.delivery_date ? 'Livraison ' + fmtDate(p.delivery_date) : '', p.hosting_renewal ? 'Hébergement jusqu\'au ' + fmtDate(p.hosting_renewal) + (p.hosting_amount ? ' (' + money(p.hosting_amount, p.currency) + '/an)' : '') : ''].filter(Boolean).join(' · ') + '</p>' +
          (pays.length ? '<ul class="crm_pays" role="list">' + pays.map(function (pay) { return '<li><span>' + fmtDate(pay.paid_at) + (pay.method ? ' · ' + esc(pay.method) : '') + (pay.note ? ' · ' + esc(pay.note) : '') + '</span><b>' + money(pay.amount, pay.currency) + '</b><button type="button" class="icon-button is-danger" data-delete-payment="' + esc(pay.id) + '" aria-label="Supprimer le paiement">' + icon('i-trash') + '</button></li>'; }).join('') + '</ul>' : '') +
          (p.domain || p.plan ? '<p class="text-size-small crm_portal-line">' + [p.domain ? '<b>' + esc(p.domain) + '</b>' : '', p.plan ? 'Formule ' + esc(PLAN[p.plan] || p.plan) : '', 'Site ' + esc(SITE[p.site_status] || p.site_status), 'SSL ' + esc(SSL[p.ssl_status] || p.ssl_status), p.domain_expires ? 'Domaine jusqu\'au ' + fmtDate(p.domain_expires) : ''].filter(Boolean).join(' · ') + '</p>' : '') +
          '<button type="button" class="button is-small is-secondary" data-crm-new="payment" data-project="' + esc(p.id) + '">' + icon('i-plus') + 'Paiement reçu</button></div>';
      }).join('') : '<p class="crm_empty">Aucun projet.</p>') + '</section>';

    html += portalSection(c, x);

    // Historique
    html += '<section class="crm_section"><h3 class="crm_section-title">Historique</h3>' +
      '<form class="crm_note" data-note-form data-client="' + esc(c.id) + '"><textarea class="form_input is-area" name="text" rows="2" placeholder="Ajouter une note (appel, rendez-vous, décision…)" aria-label="Note"></textarea><button type="submit" class="button is-small">Ajouter la note</button></form>' +
      '<ol class="crm_timeline">' + x.activity.map(function (a) {
        return '<li class="is-' + esc(a.kind) + '"><span class="crm_feed-dot is-' + esc(a.kind) + '"></span><div><p class="crm_timeline-text">' + esc(a.text) + '</p><p class="text-size-small text-color-muted">' + (d(a.created_at) ? d(a.created_at).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : '') + '</p></div></li>';
      }).join('') + '</ol></section>';

    html += '<footer class="crm_sheet-foot"><button type="button" class="button is-small is-secondary" data-archive-client>' + (c.status === 'archived' ? 'Désarchiver' : 'Archiver') + '</button><button type="button" class="button is-small is-secondary crm_danger" data-delete-client>Supprimer définitivement</button></footer>';
    return html;
  }

  sheetPanel.addEventListener('change', function (e) {
    var t = e.target, id = state.sheetId;
    if (t.closest('[data-client-form]')) {
      var body = {}; body[t.name] = t.value;
      crm('r=clients&id=' + id, { method: 'PATCH', body: body }).then(function () { toast('Enregistré'); loadClients().then(renderClients); }).catch(function (ex) { toast(ex.message); });
    } else if (t.matches('[data-client-status]')) {
      crm('r=clients&id=' + id, { method: 'PATCH', body: { status: t.value } }).then(function () { toast('Statut : ' + STATUS[t.value]); load(); openClient(id, true); });
    } else if (t.matches('[data-deal-stage]')) {
      var did = t.closest('[data-deal-line]').dataset.dealLine;
      crm('r=deals&id=' + did, { method: 'PATCH', body: { stage: t.value } }).then(function () {
        toast(t.value === 'won' ? 'Gagné · projet créé automatiquement' : 'Étape : ' + STAGE[t.value]);
        load(); openClient(id, true);
      });
    } else if (t.matches('[data-project-status]')) {
      var pid = t.closest('[data-project]').dataset.project;
      crm('r=projects&id=' + pid, { method: 'PATCH', body: { status: t.value } }).then(function () { toast('Projet : ' + PSTATUS[t.value]); load(); openClient(id, true); });
    }
  });
  sheetPanel.addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target, id = state.sheetId;
    if (f.matches('[data-note-form]')) {
      if (!f.text.value.trim()) return;
      crm('r=notes', { method: 'POST', body: { client_id: id, text: f.text.value } }).then(function () { openClient(id, true); load(); });
    } else if (f.matches('[data-sheet-task]')) {
      crm('r=tasks', { method: 'POST', body: { title: f.title.value, due_at: f.due_at.value || null, client_id: id, deal_id: f.dataset.deal || null } })
        .then(function () { toast('Tâche ajoutée'); openClient(id, true); load(); });
    }
  });
  sheetPanel.addEventListener('click', function (e) {
    var id = state.sheetId, t = e.target;
    if (t.closest('[data-archive-client]')) {
      var archived = t.closest('[data-archive-client]').textContent === 'Désarchiver';
      crm('r=clients&id=' + id, { method: 'PATCH', body: { status: archived ? 'client' : 'archived' } }).then(function () { toast(archived ? 'Désarchivé' : 'Archivé'); load(); openClient(id, true); });
    } else if (t.closest('[data-delete-client]')) {
      if (!W.confirm('Supprimer cette fiche et tout son historique ? Cette action est définitive.')) return;
      crm('r=clients&id=' + id, { method: 'DELETE' }).then(function () { closeSheet(); toast('Fiche supprimée'); load(); });
    } else if (t.closest('[data-delete-payment]')) {
      if (!W.confirm('Supprimer ce paiement ?')) return;
      crm('r=payments&id=' + t.closest('[data-delete-payment]').dataset.deletePayment, { method: 'DELETE' }).then(function () { openClient(id, true); load(); });
    } else if (t.closest('[data-edit-deal]')) {
      var did = t.closest('[data-deal-line]').dataset.dealLine;
      crm('r=clients&id=' + id).then(function (x) { editDeal(x.deals.filter(function (dl) { return dl.id === did; })[0]); });
    } else if (t.closest('[data-edit-project]')) {
      var pid = t.closest('[data-project]').dataset.project;
      crm('r=clients&id=' + id).then(function (x) { editProject(x.projects.filter(function (p) { return p.id === pid; })[0]); });
    } else if (t.closest('[data-portal-access],[data-portal-link],[data-portal-invoice],[data-portal-paid],[data-portal-del-invoice],[data-portal-notice],[data-portal-del-notice],[data-portal-done]')) {
      var pr = portalAction(t, id);
      if (pr && pr.catch) pr.catch(function (ex) { toast(ex.message); });
    }
  });

  /* ---------- Fenêtre de création / modification ---------- */
  var modal = $('[data-modal]'), modalForm = $('[data-modal-form]'), modalSubmit = null;
  function openModal(title, fields, onSubmit) {
    $('[data-modal-title]').textContent = title;
    $('[data-modal-error]').textContent = '';
    $('[data-modal-fields]').innerHTML = fields.map(function (f) {
      var v = f.value == null ? '' : f.value;
      var input;
      if (f.type === 'select') input = '<select class="form_input is-select" name="' + f.name + '">' + f.options.map(function (o) { return '<option value="' + esc(o[0]) + '"' + (String(o[0]) === String(v) ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('') + '</select>';
      else if (f.type === 'textarea') input = '<textarea class="form_input is-area" name="' + f.name + '" rows="3">' + esc(v) + '</textarea>';
      else if (f.type === 'client') input = '<div class="crm_picker"><input class="form_input" type="search" placeholder="Rechercher un client…" data-picker-input autocomplete="off" value="' + esc(f.label2 || '') + '"><input type="hidden" name="' + f.name + '" value="' + esc(v) + '"><ul class="crm_picker-list" data-picker-list hidden></ul></div>';
      else input = '<input class="form_input" name="' + f.name + '" type="' + (f.type || 'text') + '" value="' + esc(v) + '"' + (f.required ? ' required' : '') + (f.step ? ' step="' + f.step + '"' : '') + (f.placeholder ? ' placeholder="' + esc(f.placeholder) + '"' : '') + '>';
      if (f.hide) return '';
      return '<label class="crm_field' + (f.full ? ' is-full' : '') + '"' + (f.show ? ' data-show-for="' + f.show + '"' : '') + '><span class="form_label">' + esc(f.label) + '</span>' + input + '</label>';
    }).join('');
    modalSubmit = onSubmit;
    syncConditional();
    modal.hidden = false;
    modal.classList.add('is-closed');
    requestAnimationFrame(function () { requestAnimationFrame(function () { modal.classList.remove('is-closed'); }); });
    var first = modalForm.querySelector('input:not([type=hidden]), select, textarea'); if (first) first.focus();
  }
  // Champs qui n'apparaissent que pour une catégorie (ex. plateforme et résultats pour la publicité)
  function syncConditional() {
    var cat = modalForm.querySelector('[name="category"]');
    $$('[data-show-for]', modalForm).forEach(function (f) { f.hidden = !cat || cat.value !== f.dataset.showFor; });
  }
  modalForm.addEventListener('change', function (e) { if (e.target.name === 'category') syncConditional(); });
  function closeModal() { modal.classList.add('is-closed'); setTimeout(function () { modal.hidden = true; }, reduceMotion.matches ? 0 : 180); }
  modalForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var data = {};
    $$('[name]', modalForm).forEach(function (i) { if (!i.closest('[hidden]')) data[i.name] = i.value; });
    modalSubmit(data).then(function () { closeModal(); }).catch(function (ex) { $('[data-modal-error]').textContent = ex.message; });
  });
  // Recherche de client dans la fenêtre
  var pickTimer;
  modalForm.addEventListener('input', function (e) {
    if (!e.target.matches('[data-picker-input]')) return;
    var box = e.target.closest('.crm_picker'), list = $('[data-picker-list]', box);
    $('input[type=hidden]', box).value = '';
    clearTimeout(pickTimer);
    pickTimer = setTimeout(function () {
      crm('r=search&q=' + encodeURIComponent(e.target.value)).then(function (x) {
        list.innerHTML = x.items.map(function (c) { return '<li><button type="button" data-pick="' + esc(c.id) + '" data-name="' + esc(c.name) + '"><b>' + esc(c.name) + '</b><span class="text-color-muted">' + esc(c.company || c.email || '') + '</span></button></li>'; }).join('') || '<li class="crm_empty">Aucun résultat</li>';
        list.hidden = false;
      });
    }, 150);
  });
  modalForm.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pick]'); if (!b) return;
    var box = b.closest('.crm_picker');
    $('input[type=hidden]', box).value = b.dataset.pick;
    $('[data-picker-input]', box).value = b.dataset.name;
    $('[data-picker-list]', box).hidden = true;
  });

  var CUR = [['TRY', 'TL'], ['USD', '$'], ['EUR', '€']];

  /* ---------- Espace client (my.webina.com.tr) ---------- */
  var PLAN = { vitrin: 'Vitrin', randevu: 'Randevu', eticaret: 'E-ticaret', ozel: 'Sur mesure' };
  var SITE = { building: 'En création', preview: 'En prévisualisation', live: 'En ligne', suspended: 'Suspendu' };
  var SSL = { active: 'Actif', pending: 'En attente', expired: 'Expiré' };
  var REQ = { renew: 'Renouvellement', upgrade: 'Changement de formule', support: 'Assistance', approve: 'Site validé' };
  var LEVEL = { info: 'Information', warning: 'Important', critical: 'Urgent' };
  function portalSection(c, x) {
    var pt = x.portal || { invoices: [], notices: [], requests: [] };
    var on = Number(c.portal_enabled) === 1;
    var today = new Date().toISOString().slice(0, 10);
    var html = '<section class="crm_section"><div class="crm_section-head"><h3 class="crm_section-title">Espace client</h3>' +
      '<span class="crm_inline"><button type="button" class="button is-small ' + (on ? 'is-secondary' : '') + '" data-portal-access="' + (on ? 0 : 1) + '">' + (on ? 'Fermer l\'accès' : 'Ouvrir l\'accès') + '</button>' +
      (on ? '<button type="button" class="button is-small is-secondary" data-portal-link>' + icon('i-link') + 'Lien de connexion</button>' : '') + '</span></div>' +
      '<p class="text-size-small text-color-secondary">' + (on ? 'Le client se connecte sur my.webina.com.tr avec ' + esc(c.email || '—') + (c.portal_last_login ? ' · dernière connexion ' + fmtDate(c.portal_last_login) : ' · jamais connecté') : 'Accès fermé. Le client a besoin d\'une adresse e-mail sur sa fiche.') + '</p>';
    // Factures
    html += '<div class="crm_section-head is-sub"><h4 class="crm_sub-title">Factures</h4><button type="button" class="button is-small is-secondary" data-portal-invoice>' + icon('i-plus') + 'Facture</button></div>' +
      (pt.invoices.length ? '<ul class="crm_pays" role="list">' + pt.invoices.map(function (i) {
        var st = i.paid_at ? 'Payée' : (i.due_on < today ? 'En retard' : 'À payer');
        return '<li><span><b>' + esc(i.number) + '</b> · ' + esc(i.label) + ' · échéance ' + fmtDate(i.due_on) + ' · <span class="' + (i.paid_at ? 'is-muted' : 'crm_due') + '">' + st + '</span></span><b>' + money(i.amount, i.currency) + '</b>' +
          '<span class="crm_inline"><button type="button" class="button is-small is-secondary" data-portal-paid="' + esc(i.id) + '" data-paid="' + (i.paid_at ? 0 : 1) + '">' + (i.paid_at ? 'Annuler' : 'Payée') + '</button>' +
          (i.paid_at ? '' : '<button type="button" class="icon-button is-danger" data-portal-del-invoice="' + esc(i.id) + '" aria-label="Supprimer la facture">' + icon('i-trash') + '</button>') + '</span></li>';
      }).join('') + '</ul>' : '<p class="crm_empty">Aucune facture.</p>');
    // Messages affichés au client
    html += '<div class="crm_section-head is-sub"><h4 class="crm_sub-title">Messages au client</h4><button type="button" class="button is-small is-secondary" data-portal-notice>' + icon('i-plus') + 'Message</button></div>' +
      (pt.notices.length ? '<ul class="crm_pays" role="list">' + pt.notices.map(function (n) {
        return '<li><span><b>' + esc(n.title) + '</b> · ' + esc(LEVEL[n.level] || n.level) + (n.expires_on ? ' · jusqu\'au ' + fmtDate(n.expires_on) : '') + '</span><span></span><button type="button" class="icon-button is-danger" data-portal-del-notice="' + esc(n.id) + '" aria-label="Supprimer le message">' + icon('i-trash') + '</button></li>';
      }).join('') + '</ul>' : '<p class="crm_empty">Aucun message.</p>');
    if (pt.requests.length) {
      html += '<h4 class="crm_sub-title">Demandes du client</h4><ul class="crm_pays" role="list">' + pt.requests.map(function (q) {
        return '<li><span><b>' + esc(REQ[q.kind] || q.kind) + '</b> · ' + fmtDate(q.created_at) + (q.detail ? ' · ' + esc(q.detail) : '') + '</span><span class="' + (q.status === 'open' ? 'crm_due' : 'is-muted') + '">' + (q.status === 'open' ? 'Ouverte' : 'Traitée') + '</span>' +
          (q.status === 'open' ? '<button type="button" class="button is-small is-secondary" data-portal-done="' + esc(q.id) + '">Traitée</button>' : '<span></span>') + '</li>';
      }).join('') + '</ul>';
    }
    return html + '</section>';
  }
  function portalApi(path, opts) { return api('clientarea.php?' + path, opts); }
  function portalAction(t, id) {
    var el;
    if ((el = t.closest('[data-portal-access]'))) {
      return portalApi('r=access', { method: 'POST', body: { client_id: id, enabled: el.dataset.portalAccess === '1' } }).then(function () { toast('Espace client mis à jour'); openClient(id, true); });
    }
    if (t.closest('[data-portal-link]')) {
      return portalApi('r=link', { method: 'POST', body: { client_id: id } }).then(function (x) {
        var msg = 'Merhaba, Webina müşteri panelinize bu bağlantıyla giriş yapabilirsiniz (7 gün geçerli): ' + x.url;
        if (navigator.clipboard) navigator.clipboard.writeText(x.url).catch(function () {});
        if (x.phone && W.confirm('Lien copié. L\'envoyer aussi sur WhatsApp ?')) window.open('https://wa.me/' + x.phone + '?text=' + encodeURIComponent(msg), '_blank', 'noopener');
        else toast('Lien copié : valable 7 jours, une seule connexion');
        openClient(id, true);
      });
    }
    if (t.closest('[data-portal-invoice]')) {
      return crm('r=clients&id=' + id).then(function (x) {
        openModal('Nouvelle facture', [
          { label: 'Service', name: 'project_id', type: 'select', options: [['', '—']].concat(x.projects.map(function (p) { return [p.id, p.name + (p.domain ? ' (' + p.domain + ')' : '')]; })), full: true },
          { label: 'Libellé', name: 'label', required: true, value: 'Renouvellement annuel : domaine + hébergement + maintenance', full: true },
          { label: 'Montant', name: 'amount', type: 'number', required: true, step: '1' }, { label: 'Devise', name: 'currency', type: 'select', options: CUR },
          { label: 'Date', name: 'issued_on', type: 'date', value: new Date().toISOString().slice(0, 10) }, { label: 'Échéance', name: 'due_on', type: 'date' }
        ], function (data) {
          data.client_id = id;
          return portalApi('r=invoices', { method: 'POST', body: data }).then(function () { toast('Facture créée'); openClient(id, true); });
        });
      });
    }
    if ((el = t.closest('[data-portal-paid]'))) {
      return portalApi('r=invoices&id=' + el.dataset.portalPaid, { method: 'PATCH', body: { paid: el.dataset.paid === '1' } }).then(function () { toast('Facture mise à jour'); load(); openClient(id, true); });
    }
    if ((el = t.closest('[data-portal-del-invoice]'))) {
      if (!W.confirm('Supprimer cette facture ?')) return;
      return portalApi('r=invoices&id=' + el.dataset.portalDelInvoice, { method: 'DELETE' }).then(function () { openClient(id, true); });
    }
    if (t.closest('[data-portal-notice]')) {
      return openModal('Message pour le client', [
        { label: 'Titre', name: 'title', required: true, full: true }, { label: 'Texte', name: 'body', type: 'textarea', full: true },
        { label: 'Niveau', name: 'level', type: 'select', options: Object.keys(LEVEL).map(function (k) { return [k, LEVEL[k]]; }) }, { label: 'Afficher jusqu\'au', name: 'expires_on', type: 'date' }
      ], function (data) {
        data.client_id = id;
        return portalApi('r=notices', { method: 'POST', body: data }).then(function () { toast('Message publié'); openClient(id, true); });
      });
    }
    if ((el = t.closest('[data-portal-del-notice]'))) {
      return portalApi('r=notices&id=' + el.dataset.portalDelNotice, { method: 'DELETE' }).then(function () { openClient(id, true); });
    }
    if ((el = t.closest('[data-portal-done]'))) {
      return portalApi('r=requests&id=' + el.dataset.portalDone, { method: 'PATCH', body: {} }).then(function () { openClient(id, true); });
    }
  }
  function newClient() {
    openModal('Nouveau client', [
      { label: 'Nom', name: 'name', required: true }, { label: 'Type', name: 'kind', type: 'select', options: [['person', 'Personne'], ['company', 'Société']] },
      { label: 'Société', name: 'company' }, { label: 'E-mail', name: 'email', type: 'email' }, { label: 'Téléphone', name: 'phone', type: 'tel' },
      { label: 'Secteur', name: 'sector' }, { label: 'Ville', name: 'city' }, { label: 'Statut', name: 'status', type: 'select', options: [['lead', 'Prospect'], ['client', 'Client']] }
    ], function (data) {
      return crm('r=clients', { method: 'POST', body: data }).then(function (x) { toast('Client ajouté'); load(); openClient(x.client.id); });
    });
  }
  function newDeal(clientId, clientName) {
    openModal('Nouvelle vente', [
      { label: 'Client', name: 'client_id', type: 'client', value: clientId || '', label2: clientName || '', full: true },
      { label: 'Titre', name: 'title', required: true, value: 'Site web' }, { label: 'Montant', name: 'amount', type: 'number', step: '1' },
      { label: 'Devise', name: 'currency', type: 'select', options: CUR }, { label: 'Date de signature prévue', name: 'close_date', type: 'date' }
    ], function (data) {
      if (!data.client_id) return Promise.reject(new Error('Choisissez un client dans la liste.'));
      return crm('r=deals', { method: 'POST', body: data }).then(function () { toast('Vente ajoutée'); load(); if (state.sheetId) openClient(state.sheetId, true); });
    });
  }
  function editDeal(dl) {
    openModal('Modifier la vente', [
      { label: 'Titre', name: 'title', value: dl.title, full: true }, { label: 'Montant', name: 'amount', type: 'number', value: Number(dl.amount), step: '1' },
      { label: 'Devise', name: 'currency', type: 'select', options: CUR, value: dl.currency }, { label: 'Date de signature prévue', name: 'close_date', type: 'date', value: dl.close_date },
      { label: 'Notes', name: 'notes', type: 'textarea', value: dl.notes, full: true }
    ], function (data) {
      return crm('r=deals&id=' + dl.id, { method: 'PATCH', body: data }).then(function () { toast('Vente mise à jour'); load(); openClient(state.sheetId, true); });
    });
  }
  function newProject(clientId) {
    openModal('Nouveau projet', [
      { label: 'Client', name: 'client_id', type: 'client', value: clientId || '', full: true },
      { label: 'Nom du projet', name: 'name', required: true, value: 'Site web' }, { label: 'Montant', name: 'amount', type: 'number', step: '1' },
      { label: 'Devise', name: 'currency', type: 'select', options: CUR }, { label: 'Livraison prévue', name: 'delivery_date', type: 'date' },
      { label: 'Renouvellement hébergement', name: 'hosting_renewal', type: 'date' }, { label: 'Prix hébergement / an', name: 'hosting_amount', type: 'number', step: '1' }
    ], function (data) {
      if (!data.client_id) return Promise.reject(new Error('Choisissez un client dans la liste.'));
      return crm('r=projects', { method: 'POST', body: data }).then(function () { toast('Projet ajouté'); load(); if (state.sheetId) openClient(state.sheetId, true); });
    });
  }
  function editProject(p) {
    openModal('Modifier le projet', [
      { label: 'Nom', name: 'name', value: p.name, full: true }, { label: 'Montant', name: 'amount', type: 'number', value: Number(p.amount), step: '1' },
      { label: 'Devise', name: 'currency', type: 'select', options: CUR, value: p.currency }, { label: 'Début', name: 'start_date', type: 'date', value: p.start_date },
      { label: 'Livraison', name: 'delivery_date', type: 'date', value: p.delivery_date }, { label: 'Renouvellement hébergement', name: 'hosting_renewal', type: 'date', value: p.hosting_renewal },
      { label: 'Prix hébergement / an', name: 'hosting_amount', type: 'number', value: p.hosting_amount, step: '1' },
      { label: 'Domaine du site', name: 'domain', value: p.domain, placeholder: 'exemple.com' },
      { label: 'Formule', name: 'plan', type: 'select', options: [['', '—']].concat(Object.keys(PLAN).map(function (k) { return [k, PLAN[k]]; })), value: p.plan },
      { label: 'Statut du site', name: 'site_status', type: 'select', options: Object.keys(SITE).map(function (k) { return [k, SITE[k]]; }), value: p.site_status },
      { label: 'Lien de prévisualisation', name: 'preview_url', value: p.preview_url, placeholder: 'https://test.webina.com.tr/…' },
      { label: 'Expiration du domaine', name: 'domain_expires', type: 'date', value: p.domain_expires },
      { label: 'SSL', name: 'ssl_status', type: 'select', options: Object.keys(SSL).map(function (k) { return [k, SSL[k]]; }), value: p.ssl_status },
      { label: 'Expiration SSL', name: 'ssl_expires', type: 'date', value: p.ssl_expires },
      { label: 'Notes', name: 'notes', type: 'textarea', value: p.notes, full: true }
    ], function (data) {
      if (!data.plan) delete data.plan;
      return crm('r=projects&id=' + p.id, { method: 'PATCH', body: data }).then(function () { toast('Projet mis à jour'); load(); openClient(state.sheetId, true); });
    });
  }
  function newPayment(projectId) {
    openModal('Paiement reçu', [
      { label: 'Montant', name: 'amount', type: 'number', required: true, step: '1' }, { label: 'Date', name: 'paid_at', type: 'date', value: new Date().toISOString().slice(0, 10) },
      { label: 'Moyen', name: 'method', type: 'select', options: [['Virement', 'Virement'], ['Carte', 'Carte'], ['Espèces', 'Espèces'], ['Autre', 'Autre']] },
      { label: 'Note', name: 'note' }
    ], function (data) {
      data.project_id = projectId;
      return crm('r=payments', { method: 'POST', body: data }).then(function () { toast('Paiement enregistré'); load(); if (state.sheetId) openClient(state.sheetId, true); });
    });
  }
  function newTask() {
    openModal('Nouvelle tâche', [
      { label: 'Tâche', name: 'title', required: true, full: true }, { label: 'Type', name: 'kind', type: 'select', options: Object.keys(KIND).map(function (k) { return [k, KIND[k]]; }) },
      { label: 'Échéance', name: 'due_at', type: 'datetime-local' }, { label: 'Client (facultatif)', name: 'client_id', type: 'client', full: true }
    ], function (data) {
      if (!data.client_id) delete data.client_id;
      return crm('r=tasks', { method: 'POST', body: data }).then(function () { toast('Tâche ajoutée'); load(); });
    });
  }

  /* ---------- Clics globaux ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.closest('[data-modal-close]')) { closeModal(); return; }
    if (t.closest('[data-sheet-close]')) { closeSheet(); return; }
    var nw = t.closest('[data-crm-new]');
    if (nw) {
      var k = nw.dataset.crmNew;
      if (k === 'client') newClient();
      else if (k === 'deal') newDeal(nw.dataset.client, nw.dataset.client ? $('.crm_sheet-name') && $('.crm_sheet-name').textContent : '');
      else if (k === 'project') newProject(nw.dataset.client);
      else if (k === 'payment') newPayment(nw.dataset.project);
      else if (k === 'task') newTask();
      else if (k === 'expense') expenseForm();
      return;
    }
    var li = t.closest('[data-task]');
    if (li && t.closest('[data-toggle-task]')) { toggleTask(li.dataset.task, li); return; }
    if (li && t.closest('[data-delete-task]')) {
      crm('r=tasks&id=' + li.dataset.task, { method: 'DELETE' }).then(function () { toast('Tâche supprimée'); load(); if (state.sheetId) openClient(state.sheetId, true); });
      return;
    }
    var go = t.closest('[data-goto]');
    if (go) { var tab = $('.admin_tab[data-tab="' + go.dataset.goto + '"]'); if (tab) tab.click(); return; }
    var oc = t.closest('[data-open-client]');
    if (oc && !t.closest('select')) { openClient(oc.dataset.openClient, !sheet.hidden); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { if (!modal.hidden) closeModal(); else closeSheet(); }
    if (e.key === 'Enter' && e.target.matches('.crm_row, .crm_card')) e.target.click();
  });

  window.CRM = { load: load, open: openClient };
})();
