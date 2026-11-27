/* ==========================================================================
   /cowork — espace de travail technique partagé (Webina + développeur).
   Tâches, fiches des sites, bloc-notes commun, messagerie et mises en ligne.
   Rafraîchissement par interrogation régulière tant que l'onglet est visible.
   ========================================================================== */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var root = document.documentElement;
  var POLL_MS = 4000;          // rythme du rafraîchissement quand la page est visible
  var state = { me: null, team: [], projects: [], tasks: [], notes: { rev: 0 }, messages: [], deploys: [], tab: 'board' };
  var lastMsgAt = '';
  var pollTimer = null;
  var notesTimer = null;
  var notesDirty = false;

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function icon(id) { return '<svg class="icon" aria-hidden="true"><use href="#' + id + '"/></svg>'; }
  var toastTimer;
  function toast(msg) {
    var t = $('[data-toast]');
    t.textContent = msg; t.classList.add('is-visible');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.classList.remove('is-visible'); }, 3200);
  }
  function d(v) { if (!v) return null; var x = new Date(String(v).replace(' ', 'T')); return isNaN(x) ? null : x; }
  function fmtDate(v) { var x = d(v); return x ? x.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; }
  function fmtTime(v) { var x = d(v); return x ? x.toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''; }
  function kb(n) { var k = Math.max(1, Math.round(Number(n) / 1024)); return k > 1024 ? (k / 1024).toFixed(1) + ' Mo' : k + ' Ko'; }

  function api(path, opts) {
    opts = opts || {};
    var init = { method: opts.method || 'GET', credentials: 'same-origin', headers: { 'X-Requested-With': 'webina' } };
    if (opts.form) init.body = opts.form;
    else if (opts.body) { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opts.body); }
    return fetch('/api/' + path, init).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (j) {
        if (!res.ok) { var e = new Error(j.error || 'Erreur'); e.status = res.status; e.data = j; throw e; }
        return j;
      });
    });
  }

  /* ---------- Thème ---------- */
  $('[data-theme-toggle]').addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('webina-theme', next); } catch (e) {}
  });

  /* ---------- Connexion ---------- */
  function showView(name) {
    $('[data-view="auth"]').hidden = name !== 'auth';
    $('[data-view="app"]').hidden = name !== 'app';
  }
  $('[data-auth-form]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target, err = $('[data-auth-error]'), btn = $('[data-auth-submit]');
    err.textContent = ''; btn.disabled = true;
    api('auth.php?action=login', { method: 'POST', body: { email: f.email.value, password: f.password.value } })
      .then(function () { f.reset(); return boot(); })
      .catch(function (ex) { err.textContent = ex.message; })
      .then(function () { btn.disabled = false; });
  });
  $('[data-logout]').addEventListener('click', function () {
    api('auth.php?action=logout', { method: 'POST' }).then(function () { stopPoll(); location.reload(); });
  });

  function boot() {
    return load().then(function () { showView('app'); startPoll(); }).catch(function (ex) {
      showView('auth');
      if (ex.status === 403) $('[data-auth-error]').textContent = 'Ce compte n\'a pas accès à l\'espace technique.';
    });
  }

  /* ---------- Chargement complet ---------- */
  function load() {
    return api('cowork.php?r=state').then(function (x) {
      state.me = x.me; state.team = x.team; state.projects = x.projects;
      state.tasks = x.tasks; state.deploys = x.deploys;
      state.notes = x.notes;
      state.messages = x.messages;
      if (x.messages.length) lastMsgAt = x.messages[x.messages.length - 1].created_at;
      $('[data-user]').textContent = x.me.email; $('[data-user]').hidden = false;
      $('[data-logout]').hidden = false;
      renderAll();
    });
  }
  function renderAll() {
    renderPresence(); fillSelects(); renderBoard(); renderSites(); renderNotes(); renderMessages(); renderDeploys();
  }

  /* ---------- Qui est là ---------- */
  function renderPresence() {
    $('[data-presence]').innerHTML = state.team.map(function (u) {
      var me = state.me && u.id === state.me.id;
      return '<span class="cw_who' + (u.online ? ' is-on' : '') + '" title="' + esc(u.email) + (u.online ? ' · en ligne' : ' · hors ligne') + '">' +
        '<span class="cw_dot"></span>' + esc(u.label) + (me ? ' (vous)' : '') + '</span>';
    }).join('');
  }

  function fillSelects() {
    var opts = state.projects.map(function (p) {
      return '<option value="' + esc(p.id) + '">' + esc(p.name) + (p.domain ? ' — ' + esc(p.domain) : '') + '</option>';
    }).join('');
    $$('[data-project-select]').forEach(function (sel) {
      var v = sel.value;
      sel.innerHTML = '<option value="">Sans site</option>' + opts;
      sel.value = v;
    });
    var team = state.team.map(function (u) { return '<option value="' + esc(u.id) + '">' + esc(u.label) + '</option>'; }).join('');
    $$('[data-assignee-select]').forEach(function (sel) {
      var v = sel.value;
      sel.innerHTML = '<option value="">Non assignée</option>' + team;
      sel.value = v;
    });
  }

  /* ---------- Tâches ---------- */
  var COLS = [['todo', 'À faire'], ['doing', 'En cours'], ['review', 'À tester'], ['done', 'Terminé']];
  function renderBoard() {
    var open = state.tasks.filter(function (t) { return t.status !== 'done'; }).length;
    var badge = $('[data-count="tasks"]'); if (badge) badge.textContent = open || '';
    $('[data-board]').innerHTML = COLS.map(function (c) {
      var items = state.tasks.filter(function (t) { return t.status === c[0]; });
      return '<section class="cw_col" data-col="' + c[0] + '">' +
        '<header class="cw_col-head">' + esc(c[1]) + '<span>' + items.length + '</span></header>' +
        '<ul class="cw_col-list" role="list">' + items.map(taskCard).join('') + '</ul></section>';
    }).join('');
  }
  function taskCard(t) {
    var late = t.due_on && t.status !== 'done' && new Date(t.due_on) < new Date(new Date().toDateString());
    return '<li class="cw_task" data-task="' + esc(t.id) + '" draggable="true">' +
      '<p class="cw_task-title">' + esc(t.title) + '</p>' +
      (t.detail ? '<p class="cw_task-detail">' + esc(t.detail) + '</p>' : '') +
      '<p class="cw_task-meta">' +
        (t.project_name ? '<span class="cw_tag">' + esc(t.project_name) + '</span>' : '') +
        (t.assignee_label ? '<span class="cw_tag is-who">' + esc(t.assignee_label) + '</span>' : '') +
        (t.due_on ? '<span class="cw_tag' + (late ? ' is-late' : '') + '">' + fmtDate(t.due_on) + '</span>' : '') +
      '</p>' +
      '<div class="cw_task-acts">' +
        '<select class="form_input is-select cw_mini" data-task-status aria-label="Statut">' +
          COLS.map(function (c) { return '<option value="' + c[0] + '"' + (c[0] === t.status ? ' selected' : '') + '>' + c[1] + '</option>'; }).join('') +
        '</select>' +
        '<button type="button" class="icon-button is-danger" data-task-del aria-label="Supprimer">' + icon('i-trash') + '</button>' +
      '</div></li>';
  }
  $('[data-task-form]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;
    api('cowork.php?r=tasks', { method: 'POST', body: { title: f.title.value, project_id: f.project_id.value, assignee: f.assignee.value, due_on: f.due_on.value } })
      .then(function () { f.title.value = ''; f.due_on.value = ''; toast('Tâche ajoutée'); return load(); })
      .catch(function (ex) { toast(ex.message); });
  });
  $('[data-board]').addEventListener('change', function (e) {
    if (!e.target.matches('[data-task-status]')) return;
    var id = e.target.closest('[data-task]').dataset.task;
    api('cowork.php?r=tasks&id=' + encodeURIComponent(id), { method: 'PATCH', body: { status: e.target.value } })
      .then(load).catch(function (ex) { toast(ex.message); });
  });
  $('[data-board]').addEventListener('click', function (e) {
    var del = e.target.closest('[data-task-del]');
    if (!del) return;
    var id = del.closest('[data-task]').dataset.task;
    if (!window.confirm('Supprimer cette tâche ?')) return;
    api('cowork.php?r=tasks&id=' + encodeURIComponent(id), { method: 'DELETE' }).then(load).catch(function (ex) { toast(ex.message); });
  });
  // Glisser-déposer d'une colonne à l'autre
  var dragId = null;
  $('[data-board]').addEventListener('dragstart', function (e) {
    var li = e.target.closest('[data-task]'); if (!li) return;
    dragId = li.dataset.task; li.classList.add('is-drag');
    e.dataTransfer.effectAllowed = 'move';
  });
  $('[data-board]').addEventListener('dragend', function (e) {
    var li = e.target.closest('[data-task]'); if (li) li.classList.remove('is-drag');
  });
  $('[data-board]').addEventListener('dragover', function (e) {
    var col = e.target.closest('[data-col]'); if (!col || !dragId) return;
    e.preventDefault(); col.classList.add('is-over');
  });
  $('[data-board]').addEventListener('dragleave', function (e) {
    var col = e.target.closest('[data-col]'); if (col) col.classList.remove('is-over');
  });
  $('[data-board]').addEventListener('drop', function (e) {
    var col = e.target.closest('[data-col]'); if (!col || !dragId) return;
    e.preventDefault(); col.classList.remove('is-over');
    var id = dragId; dragId = null;
    api('cowork.php?r=tasks&id=' + encodeURIComponent(id), { method: 'PATCH', body: { status: col.dataset.col } })
      .then(load).catch(function (ex) { toast(ex.message); });
  });

  /* ---------- Fiches techniques des sites ---------- */
  var SITE = { building: 'En construction', preview: 'En aperçu', live: 'En ligne', suspended: 'Suspendu' };
  var SSL = { pending: 'En attente', active: 'Actif', expired: 'Expiré', none: 'Aucun' };
  function days(v) { return v ? Math.round((new Date(v) - new Date(new Date().toDateString())) / 864e5) : null; }
  function renderSites() {
    $('[data-sites]').innerHTML = state.projects.map(function (p) {
      var dh = days(p.hosting_renewal), dd = days(p.domain_expires);
      var doms = (p.domains || []).map(function (x) {
        var n = days(x.expires);
        return '<li><b dir="ltr">' + esc(x.name) + '</b><span' + (n !== null && n <= 30 ? ' class="cw_warn"' : '') + '>' + (x.expires ? fmtDate(x.expires) + (n !== null ? ' · ' + n + ' j' : '') : 'échéance inconnue') + '</span></li>';
      }).join('');
      return '<article class="admin_card cw_site" data-site="' + esc(p.id) + '">' +
        '<header class="cw_site-head">' +
          '<div><h2 class="heading-style-h5">' + esc(p.name) + '</h2>' +
          '<p class="text-size-small text-color-secondary">' + esc(p.client) + (p.company ? ' · ' + esc(p.company) : '') +
          (p.open_tasks ? ' · <b>' + p.open_tasks + ' tâche' + (p.open_tasks > 1 ? 's' : '') + '</b>' : '') + '</p></div>' +
          (p.domain ? '<a class="button is-small is-secondary" href="https://' + esc(p.domain.replace(/^https?:\/\//, '')) + '" target="_blank" rel="noopener">Ouvrir' + icon('i-arrow') + '</a>' : '') +
        '</header>' +
        '<div class="cw_site-grid">' +
          field(p, 'site_status', 'Statut du site', SITE) +
          field(p, 'ssl_status', 'SSL', SSL) +
          field(p, 'domain', 'Domaine principal') +
          field(p, 'preview_url', 'Lien d\'aperçu') +
          field(p, 'hosting_renewal', 'Hébergement jusqu\'au', null, dh) +
          field(p, 'domain_expires', 'Domaine jusqu\'au', null, dd) +
        '</div>' +
        (doms ? '<ul class="cw_domlist" role="list">' + doms + '</ul>' : '') +
        '<label class="form_field"><span class="form_label">Notes techniques</span>' +
          '<textarea class="form_input is-area" data-field="notes" rows="3" placeholder="Accès, particularités, choses à surveiller…">' + esc(p.notes || '') + '</textarea></label>' +
        '<p class="text-size-small text-color-muted">Contact client : ' + esc(p.client_email || '—') + (p.client_phone ? ' · ' + esc(p.client_phone) : '') + '</p>' +
      '</article>';
    }).join('') || '<p class="admin_empty">Aucun site pour le moment.</p>';
  }
  function field(p, name, label, map, left) {
    var v = p[name] == null ? '' : p[name];
    var input;
    if (map) {
      input = '<select class="form_input is-select" data-field="' + name + '">' + Object.keys(map).map(function (k) {
        return '<option value="' + k + '"' + (k === v ? ' selected' : '') + '>' + esc(map[k]) + '</option>';
      }).join('') + '</select>';
    } else if (name.indexOf('expires') > -1 || name.indexOf('renewal') > -1) {
      input = '<input class="form_input" type="date" data-field="' + name + '" value="' + esc(v) + '">';
    } else {
      input = '<input class="form_input" data-field="' + name + '" value="' + esc(v) + '" placeholder="—">';
    }
    return '<label class="form_field"><span class="form_label">' + esc(label) +
      (left !== null && left !== undefined ? ' <span class="' + (left <= 30 ? 'cw_warn' : 'text-color-muted') + '">(' + left + ' j)</span>' : '') +
      '</span>' + input + '</label>';
  }
  $('[data-sites]').addEventListener('change', function (e) {
    var el = e.target.closest('[data-field]'); if (!el) return;
    var card = el.closest('[data-site]'), body = {};
    body[el.dataset.field] = el.value;
    api('cowork.php?r=project&id=' + encodeURIComponent(card.dataset.site), { method: 'PATCH', body: body })
      .then(function () { toast('Enregistré'); return load(); })
      .catch(function (ex) { toast(ex.message); });
  });

  /* ---------- Bloc-notes commun ---------- */
  function renderNotes() {
    var ta = $('[data-notes]');
    if (document.activeElement !== ta) ta.value = state.notes.body || '';
    $('[data-notes-meta]').textContent = state.notes.updated_at
      ? 'Dernière modification : ' + fmtTime(state.notes.updated_at) + (state.notes.by ? ' par ' + state.notes.by : '')
      : 'Partagé entre vous deux, enregistré automatiquement.';
  }
  function saveNotes() {
    var ta = $('[data-notes]');
    if (!notesDirty) return;
    $('[data-notes-state]').textContent = 'Enregistrement…';
    api('cowork.php?r=notes', { method: 'PATCH', body: { body: ta.value, rev: state.notes.rev } })
      .then(function (x) {
        state.notes.rev = x.rev; notesDirty = false;
        $('[data-notes-state]').textContent = 'Enregistré';
        setTimeout(function () { if (!notesDirty) $('[data-notes-state]').textContent = ''; }, 2500);
      })
      .catch(function (ex) {
        if (ex.status === 409) {
          // L'autre a écrit pendant ce temps : on garde son texte et on ajoute le nôtre dessous
          var mine = ta.value;
          state.notes.rev = ex.data.rev;
          ta.value = (ex.data.body || '') + '\n\n--- ajouté pendant ce temps ---\n' + mine;
          $('[data-notes-state]').textContent = 'Fusionné avec la version de l\'autre';
          notesDirty = true;
          clearTimeout(notesTimer); notesTimer = setTimeout(saveNotes, 1200);
        } else {
          $('[data-notes-state]').textContent = ex.message;
        }
      });
  }
  $('[data-notes]').addEventListener('input', function () {
    notesDirty = true;
    $('[data-notes-state]').textContent = 'Modifié…';
    clearTimeout(notesTimer);
    notesTimer = setTimeout(saveNotes, 1200);
  });
  $('[data-notes]').addEventListener('blur', saveNotes);

  /* ---------- Messages ---------- */
  function renderMessages() {
    var list = $('[data-msgs]');
    var atBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
    list.innerHTML = state.messages.map(function (m) {
      var mine = state.me && m.user_id === state.me.id;
      var file = '';
      if (m.file_id) {
        var url = '/api/cowork.php?r=message&file=' + encodeURIComponent(m.file_id);
        file = (m.mime || '').indexOf('image/') === 0
          ? '<a class="cw_msg-img" href="' + url + '&view=1" target="_blank" rel="noopener"><img src="' + url + '&view=1" alt="' + esc(m.filename) + '" loading="lazy"></a>'
          : '<a class="cw_msg-file" href="' + url + '" download>' + icon('i-paperclip') + esc(m.filename) + ' <span>' + kb(m.size) + '</span></a>';
      }
      return '<li class="cw_msg' + (mine ? ' is-me' : '') + '">' +
        '<p class="cw_msg-meta">' + esc(m.author) + ' · ' + fmtTime(m.created_at) + '</p>' +
        (m.body ? '<p class="cw_msg-body">' + esc(m.body) + '</p>' : '') + file + '</li>';
    }).join('') || '<li class="admin_empty">Aucun message. Écrivez le premier.</li>';
    if (atBottom) list.scrollTop = list.scrollHeight;
  }
  var chatFile = $('[data-chat-file]');
  chatFile.addEventListener('change', function () {
    var n = $('[data-chat-file-name]');
    n.hidden = !chatFile.files[0];
    n.textContent = chatFile.files[0] ? 'Pièce jointe : ' + chatFile.files[0].name : '';
  });
  function sendMessage() {
    var f = $('[data-chat-form]'), text = f.body.value.trim(), file = chatFile.files[0];
    if (!text && !file) return;
    var fd = new FormData();
    fd.append('body', text); if (file) fd.append('file', file);
    var btn = f.querySelector('[type=submit]'); btn.disabled = true;
    api('cowork.php?r=message', { method: 'POST', form: fd })
      .then(function () {
        f.body.value = ''; chatFile.value = ''; $('[data-chat-file-name]').hidden = true;
        return poll(true);
      })
      .catch(function (ex) { toast(ex.message); })
      .then(function () { btn.disabled = false; });
  }
  $('[data-chat-form]').addEventListener('submit', function (e) { e.preventDefault(); sendMessage(); });
  $('[data-chat-form] [name="body"]').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });

  /* ---------- Mises en ligne ---------- */
  function renderDeploys() {
    $('[data-deploy-rows]').innerHTML = state.deploys.map(function (x) {
      return '<tr><td class="is-muted">' + fmtTime(x.created_at) + '</td>' +
        '<td>' + esc(x.project_name || '—') + '</td>' +
        '<td><b>' + esc(x.label) + '</b>' + (x.note ? '<span class="admin_sub">' + esc(x.note) + '</span>' : '') + '</td>' +
        '<td>' + (x.env === 'test' ? '<span class="admin_badge">Test</span>' : '<span class="admin_badge is-new">Production</span>') + '</td>' +
        '<td class="is-muted">' + esc(x.name || (x.email ? x.email.split('@')[0] : '—')) + '</td></tr>';
    }).join('') || '<tr><td colspan="5" class="admin_empty">Rien pour le moment.</td></tr>';
  }
  $('[data-deploy-form]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;
    api('cowork.php?r=deploys', { method: 'POST', body: { label: f.label.value, project_id: f.project_id.value, env: f.env.value, note: f.note.value } })
      .then(function () { f.label.value = ''; f.note.value = ''; toast('Mise en ligne enregistrée'); return load(); })
      .catch(function (ex) { toast(ex.message); });
  });

  /* ---------- Onglets ---------- */
  $$('.admin_tab').forEach(function (t) {
    t.addEventListener('click', function () {
      $$('.admin_tab').forEach(function (x) { x.setAttribute('aria-selected', String(x === t)); });
      $$('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== t.dataset.tab; });
      state.tab = t.dataset.tab;
      if (state.tab === 'chat') { $('[data-count="chat"]').textContent = ''; var l = $('[data-msgs]'); l.scrollTop = l.scrollHeight; }
    });
  });

  /* ---------- Rafraîchissement ---------- */
  function poll(force) {
    if (!force && document.hidden) return Promise.resolve();
    return api('cowork.php?r=poll&after=' + encodeURIComponent(lastMsgAt))
      .then(function (x) {
        state.team = x.team; renderPresence();
        if (x.messages && x.messages.length) {
          state.messages = state.messages.concat(x.messages).slice(-200);
          lastMsgAt = x.messages[x.messages.length - 1].created_at;
          renderMessages();
          var fromOther = x.messages.some(function (m) { return !state.me || m.user_id !== state.me.id; });
          if (fromOther && state.tab !== 'chat') {
            var b = $('[data-count="chat"]');
            b.textContent = String((Number(b.textContent) || 0) + x.messages.filter(function (m) { return m.user_id !== state.me.id; }).length);
          }
        }
        // Le bloc-notes ou les tâches ont bougé chez l'autre : on recharge sans écraser une saisie en cours
        if (x.notesRev !== state.notes.rev && !notesDirty && document.activeElement !== $('[data-notes]')) {
          return api('cowork.php?r=state').then(function (s) {
            state.notes = s.notes; state.tasks = s.tasks; state.projects = s.projects; state.deploys = s.deploys;
            renderNotes(); renderBoard(); renderSites(); renderDeploys();
          });
        }
        if (x.tasksRev && x.tasksRev !== state.tasksRev) {
          state.tasksRev = x.tasksRev;
          return api('cowork.php?r=state').then(function (s) {
            state.tasks = s.tasks; state.projects = s.projects; state.deploys = s.deploys;
            renderBoard(); renderDeploys();
          });
        }
      })
      .catch(function (ex) { if (ex.status === 401) { stopPoll(); showView('auth'); } });
  }
  function startPoll() { stopPoll(); pollTimer = setInterval(poll, POLL_MS); }
  function stopPoll() { if (pollTimer) clearInterval(pollTimer); pollTimer = null; }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(true); });
  window.addEventListener('beforeunload', function () { if (notesDirty) saveNotes(); });

  boot();
})();
