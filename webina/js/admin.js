/* Webina — administration : demandes, codes de réduction, lien de devis, newsletter */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var root = document.documentElement;
  var TR = (window.WEBINA_I18N || {}).tr || {};
  var state = { submissions: [], subscribers: [], codes: [], status: 'all', promoFilter: 'free', fresh: [] };

  /* ---------- API (mêmes en-têtes que le site) ---------- */
  function api(path, opts) {
    opts = opts || {};
    return fetch('/api/' + path, {
      method: opts.method || 'GET',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'webina' },
      body: opts.body ? JSON.stringify(opts.body) : undefined
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) {
        if (!r.ok) { var e = new Error(d.error || ('HTTP ' + r.status)); e.status = r.status; throw e; }
        return d;
      });
    });
  }

  var toastTimer;
  function toast(msg) {
    var t = $('[data-toast]');
    t.textContent = msg;
    t.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('is-visible'); }, 2200);
  }
  function copy(text, msg) {
    var done = function () { toast(msg || 'Copié'); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, fallback); else fallback();
    function fallback() {
      var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta);
      ta.select(); try { document.execCommand('copy'); done(); } catch (e) {} ta.remove();
    }
  }
  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function date(s, withTime) {
    if (!s) return '—';
    var d = new Date(s.replace(' ', 'T'));
    return withTime ? d.toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : d.toLocaleDateString('fr-FR');
  }
  function money(n, cur) {
    if (n == null || n === '') return '';
    n = Number(n);
    if (cur === 'TRY' || !cur) return n.toLocaleString('tr-TR') + ' TL';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: cur, maximumFractionDigits: 0 }).format(n);
  }
  function confirmAction(msg) { return window.confirm(msg); }

  /* ---------- Thème ---------- */
  $('[data-theme-toggle]').addEventListener('click', function () {
    var dark = root.getAttribute('data-theme') === 'dark' ||
      (!root.getAttribute('data-theme') && matchMedia('(prefers-color-scheme: dark)').matches);
    var next = dark ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('webina-theme', next); } catch (e) {}
  });

  /* ---------- Connexion ---------- */
  var needsSetup = false;
  function showView(name) {
    $('[data-view="auth"]').hidden = name !== 'auth';
    $('[data-view="app"]').hidden = name !== 'app';
  }
  function setUser(u) {
    var el = $('[data-user]');
    el.textContent = u ? u.email : '';
    el.hidden = !u;
    $('[data-logout]').hidden = !u;
  }
  function boot() {
    api('auth.php?action=me').then(function (d) {
      needsSetup = !!d.needsSetup;
      if (d.user && d.user.isAdmin) { setUser(d.user); showView('app'); loadAll(); }
      else {
        $('[data-auth-title]').textContent = needsSetup ? 'Créer le compte administrateur' : 'Connexion';
        $('[data-auth-hint]').hidden = !needsSetup;
        $('[data-auth-submit]').textContent = needsSetup ? 'Créer le compte' : 'Se connecter';
        showView('auth');
        $('#a-email').focus();
      }
    }).catch(function (e) {
      showView('auth');
      $('[data-auth-error]').textContent = 'API indisponible : ' + e.message;
    });
  }
  $('[data-auth-form]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target, err = $('[data-auth-error]');
    err.textContent = '';
    var body = { email: f.email.value.trim(), password: f.password.value };
    api('auth.php?action=' + (needsSetup ? 'setup' : 'login'), { method: 'POST', body: body })
      .then(function (d) { f.reset(); setUser(d.user); showView('app'); loadAll(); })
      .catch(function (ex) { err.textContent = ex.message; });
  });
  $('[data-logout]').addEventListener('click', function () {
    api('auth.php?action=logout', { method: 'POST' }).then(function () { setUser(null); boot(); });
  });

  /* ---------- Onglets ---------- */
  $$('.admin_tab').forEach(function (t) {
    t.addEventListener('click', function () {
      $$('.admin_tab').forEach(function (x) { x.setAttribute('aria-selected', String(x === t)); });
      $$('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== t.dataset.tab; });
    });
  });

  function loadAll() {
    return Promise.all([
      api('submissions.php').then(function (d) { state.submissions = d.items || []; }).catch(function () {}),
      api('subscribers.php').then(function (d) { state.subscribers = d.items || []; }).catch(function () {}),
      api('promo.php').then(function (d) { state.codes = d.items || []; }).catch(function () {})
    ]).then(renderAll);
  }
  function renderAll() { renderStats(); renderSubmissions(); renderCodes(); renderSubscribers(); fillCodeSelect(); }

  /* ---------- Chiffres clés ---------- */
  function renderStats() {
    var s = state.submissions;
    var free = state.codes.filter(function (c) { return !c.used_at; }).length;
    var items = [
      ['Nouvelles demandes', s.filter(function (x) { return x.status === 'new'; }).length, true],
      ['En cours', s.filter(function (x) { return x.status === 'in_progress'; }).length],
      ['Traitées', s.filter(function (x) { return x.status === 'done'; }).length],
      ['Codes disponibles', free],
      ['Abonnés newsletter', state.subscribers.length]
    ];
    $('[data-stats]').innerHTML = items.map(function (i) {
      return '<div class="admin_stat' + (i[2] ? ' is-accent' : '') + '"><p class="admin_stat-value">' + i[1] + '</p><p class="admin_stat-label">' + esc(i[0]) + '</p></div>';
    }).join('');
  }

  /* ---------- Demandes ---------- */
  var STATUS = { new: 'Nouvelle', in_progress: 'En cours', done: 'Traitée' };
  var FR = {
    home: 'Accueil', about: 'À propos', services: 'Services', blog: 'Blog', gallery: 'Galerie', contact: 'Contact', shop: 'Boutique',
    tr: 'Turc', en: 'Anglais', ar: 'Arabe', fr: 'Français', ru: 'Russe', de: 'Allemand', pl: 'Polonais', it: 'Italien', es: 'Espagnol',
    form: 'Formulaire', whatsapp: 'WhatsApp / téléphone', calendar: 'Calendrier de RDV',
    ready: 'Prêts', partial: 'En partie', need: 'Rédaction complète',
    own: 'Fournies', stock: 'Banque d\'images', pro: 'Photographe pro', yes: 'Oui', no: 'Non'
  };
  function fr(v) { return Array.isArray(v) ? (v.length ? v.map(fr).join(', ') : '—') : (v ? (FR[v] || v) : '—'); }

  function detailRows(raw) {
    var d; try { d = JSON.parse(raw); } catch (e) { return []; }
    return [
      ['Prix', money(d.price, d.currency)],
      ['Code de réduction', d.promo || '—'],
      ['Ville', d.city || '—'],
      ['Langue du formulaire', (d.lang || '').toUpperCase() || '—'],
      ['Logo', fr(d.logo)],
      ['Nom de domaine', fr(d.domain) + (d.domainName ? ' · ' + d.domainName : '')],
      ['Pages en option', fr(d.pages)],
      ['Pages sur mesure', (d.customPages && d.customPages !== '0') ? d.customPages + (d.customDesc ? ' · ' + d.customDesc : '') : '—'],
      ['Langues du site', fr(d.langs)],
      ['Contact clients', fr(d.booking)],
      ['Textes', fr(d.content)],
      ['Photos', fr(d.photos)],
      ['Site exemple', d.example || '—']
    ];
  }
  function dl(rows) {
    return '<dl class="admin_dl">' + rows.map(function (r) { return '<dt>' + esc(r[0]) + '</dt><dd>' + esc(r[1] || '—') + '</dd>'; }).join('') + '</dl>';
  }

  function renderSubmissions() {
    var list = $('[data-submissions]');
    var items = state.submissions.filter(function (s) { return state.status === 'all' || s.status === state.status; });
    if (!items.length) { list.innerHTML = '<li class="admin_empty">Aucune demande</li>'; return; }
    list.innerHTML = items.map(function (s) {
      var d = {}; try { d = JSON.parse(s.details || '{}'); } catch (e) {}
      var isQuote = s.kind === 'quote';
      var contact = [['E-mail', s.email], ['Téléphone', s.phone], ['Entreprise', s.company], ['Secteur', s.sector]];
      if (!isQuote) contact.push(['Service', s.service], ['Budget', s.budget]);
      return '<li class="admin_item" data-id="' + esc(s.id) + '">' +
        '<button type="button" class="admin_item-head" aria-expanded="false">' +
          '<span><span class="admin_item-name">' + esc(s.name) + '</span>' +
          '<span class="admin_item-meta"><span>' + esc(date(s.created_at, true)) + '</span><span>' + esc(s.email) + '</span>' + (d.promo ? '<span>Code ' + esc(d.promo) + '</span>' : '') + '</span></span>' +
          '<span class="admin_item-price">' + (isQuote ? esc(money(d.price, d.currency)) : '') + '</span>' +
          '<span class="admin_badges">' + (isQuote ? '<span class="admin_badge is-quote">Devis</span> ' : '') + '<span class="admin_badge is-' + esc(s.status) + '">' + esc(STATUS[s.status] || s.status) + '</span></span>' +
          '<svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg>' +
        '</button>' +
        '<div class="admin_item-body" hidden>' +
          dl(contact) +
          (isQuote ? dl(detailRows(s.details)) : '<div></div>') +
          (s.message ? '<p class="admin_message">' + esc(s.message) + '</p>' : '') +
          '<div class="admin_item-actions">' +
            '<select class="form_input is-select" data-status aria-label="Statut">' +
              Object.keys(STATUS).map(function (k) { return '<option value="' + k + '"' + (k === s.status ? ' selected' : '') + '>' + STATUS[k] + '</option>'; }).join('') +
            '</select>' +
            '<a class="button is-small is-secondary" href="mailto:' + esc(s.email) + '"><svg class="icon" aria-hidden="true"><use href="#i-mail"/></svg>Répondre</a>' +
            '<button type="button" class="icon-button is-danger is-danger" data-delete aria-label="Supprimer"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg></button>' +
          '</div>' +
        '</div>' +
      '</li>';
    }).join('');
  }
  $('[data-submissions]').addEventListener('click', function (e) {
    var item = e.target.closest('.admin_item'); if (!item) return;
    var id = item.dataset.id;
    if (e.target.closest('.admin_item-head')) {
      var open = !item.classList.contains('is-open');
      item.classList.toggle('is-open', open);
      $('.admin_item-head', item).setAttribute('aria-expanded', String(open));
      $('.admin_item-body', item).hidden = !open;
    } else if (e.target.closest('[data-delete]')) {
      if (!confirmAction('Supprimer cette demande ?')) return;
      api('submissions.php?id=' + encodeURIComponent(id), { method: 'DELETE' }).then(function () {
        state.submissions = state.submissions.filter(function (s) { return s.id !== id; });
        renderStats(); renderSubmissions(); toast('Demande supprimée');
      }).catch(function (ex) { toast(ex.message); });
    }
  });
  $('[data-submissions]').addEventListener('change', function (e) {
    if (!e.target.matches('[data-status]')) return;
    var item = e.target.closest('.admin_item'), id = item.dataset.id, status = e.target.value;
    api('submissions.php?id=' + encodeURIComponent(id), { method: 'PATCH', body: { status: status } }).then(function () {
      state.submissions.forEach(function (s) { if (s.id === id) s.status = status; });
      renderStats();
      var b = $('.admin_badge:not(.is-quote)', item);
      b.className = 'admin_badge is-' + status; b.textContent = STATUS[status];
      toast('Statut mis à jour');
    }).catch(function (ex) { toast(ex.message); });
  });
  $('[data-status-filter]').addEventListener('change', function (e) { state.status = e.target.value; renderSubmissions(); });

  /* ---------- Codes de réduction ---------- */
  function quoteLink(code, extra) {
    var p = new URLSearchParams(extra || {});
    if (code) p.set('kod', code);
    var q = p.toString();
    return location.origin + '/teklif' + (q ? '?' + q : '');
  }
  function renderCodes() {
    var rows = state.codes.filter(function (c) {
      return state.promoFilter === 'all' || (state.promoFilter === 'free' ? !c.used_at : !!c.used_at);
    });
    var tb = $('[data-promo-rows]');
    if (!rows.length) { tb.innerHTML = '<tr><td colspan="5" class="is-muted">Aucun code</td></tr>'; return; }
    tb.innerHTML = rows.map(function (c) {
      var used = !!c.used_at;
      var etat = used
        ? '<span class="admin_badge">Utilisé</span> <span class="text-size-small text-color-secondary">' + esc(c.used_name || '') + (c.used_email ? ' · ' + esc(c.used_email) : '') + ' · ' + esc(date(c.used_at, true)) + '</span>'
        : '<span class="admin_badge is-new">Disponible</span>';
      return '<tr class="' + (used ? 'is-used' : '') + (state.fresh.indexOf(c.code) > -1 ? ' is-fresh' : '') + '" data-id="' + esc(c.id) + '" data-code="' + esc(c.code) + '">' +
        '<td><span class="admin_code">' + esc(c.code) + '</span></td>' +
        '<td>' + etat + '</td>' +
        '<td class="is-muted">' + esc(c.note || '—') + '</td>' +
        '<td class="is-muted">' + esc(date(c.created_at)) + '</td>' +
        '<td class="is-actions"><span class="admin_row-actions">' +
          (used ? '' :
            '<button type="button" class="icon-button" data-copy-code aria-label="Copier le code" title="Copier le code"><svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg></button>' +
            '<button type="button" class="icon-button" data-copy-code-link aria-label="Copier le lien de devis" title="Copier le lien de devis avec ce code"><svg class="icon" aria-hidden="true"><use href="#i-link"/></svg></button>' +
            '<button type="button" class="icon-button is-danger" data-delete-code aria-label="Supprimer" title="Supprimer"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg></button>') +
        '</span></td></tr>';
    }).join('');
  }
  $('[data-promo-rows]').addEventListener('click', function (e) {
    var tr = e.target.closest('tr'); if (!tr) return;
    var code = tr.dataset.code, id = tr.dataset.id;
    if (e.target.closest('[data-copy-code]')) copy(code, 'Code copié');
    else if (e.target.closest('[data-copy-code-link]')) copy(quoteLink(code), 'Lien copié');
    else if (e.target.closest('[data-delete-code]')) {
      if (!confirmAction('Supprimer le code ' + code + ' ?')) return;
      api('promo.php?id=' + encodeURIComponent(id), { method: 'DELETE' }).then(function () {
        state.codes = state.codes.filter(function (c) { return c.id !== id; });
        renderStats(); renderCodes(); fillCodeSelect(); toast('Code supprimé');
      }).catch(function (ex) { toast(ex.message); });
    }
  });
  $('[data-promo-filter]').addEventListener('change', function (e) { state.promoFilter = e.target.value; renderCodes(); });
  $('[data-generate]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;
    api('promo.php', { method: 'POST', body: { count: Number(f.count.value) || 1, note: f.note.value.trim() } }).then(function (d) {
      state.fresh = d.codes || [];
      var box = $('[data-new-codes]');
      box.innerHTML = state.fresh.map(function (c) { return '<span class="admin_code">' + esc(c) + '</span>'; }).join('') +
        '<button type="button" class="button is-small" data-copy-new><svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg>Tout copier</button>';
      box.hidden = false;
      f.note.value = '';
      toast(state.fresh.length + ' code(s) créé(s)');
      return api('promo.php').then(function (r) { state.codes = r.items || []; renderStats(); renderCodes(); fillCodeSelect(); });
    }).catch(function (ex) { toast(ex.message); });
  });
  $('[data-new-codes]').addEventListener('click', function (e) {
    if (e.target.closest('[data-copy-new]')) copy(state.fresh.join('\n'), 'Codes copiés');
  });

  /* ---------- Lien de devis personnalisé ---------- */
  var sectorSel = $('[data-sector-select]');
  (TR.contact && TR.contact.sectorOpts || []).forEach(function (o) {
    var opt = document.createElement('option'); opt.value = o; opt.textContent = o; sectorSel.appendChild(opt);
  });
  function fillCodeSelect() {
    var sel = $('[data-code-select]'), cur = sel.value;
    sel.innerHTML = '<option value="">Aucun</option>' + state.codes.filter(function (c) { return !c.used_at; })
      .map(function (c) { return '<option value="' + esc(c.code) + '">' + esc(c.code) + (c.note ? ' — ' + esc(c.note) : '') + '</option>'; }).join('');
    sel.value = cur;
    updateLink();
  }
  function updateLink() {
    var f = $('[data-link-form]'), extra = {};
    ['ad', 'firma', 'sektor', 'sehir'].forEach(function (k) { var v = f[k].value.trim(); if (v) extra[k] = v; });
    if (f.lang.value !== 'tr') extra.lang = f.lang.value;
    $('[data-link-out]').value = quoteLink(f.kod.value, extra);
  }
  $('[data-link-form]').addEventListener('input', updateLink);
  $('[data-link-form]').addEventListener('change', updateLink);
  $('[data-copy-link]').addEventListener('click', function () { copy($('[data-link-out]').value, 'Lien copié'); });

  /* ---------- Newsletter ---------- */
  function renderSubscribers() {
    var s = state.subscribers;
    $('[data-sub-count]').textContent = s.length + ' abonné' + (s.length > 1 ? 's' : '');
    var tb = $('[data-sub-rows]');
    if (!s.length) { tb.innerHTML = '<tr><td colspan="4" class="is-muted">Aucun abonné</td></tr>'; return; }
    tb.innerHTML = s.map(function (r) {
      return '<tr data-id="' + esc(r.id) + '"><td><a href="mailto:' + esc(r.email) + '">' + esc(r.email) + '</a></td><td>' + esc((r.lang || '').toUpperCase()) + '</td><td class="is-muted">' + esc(date(r.created_at)) + '</td>' +
        '<td class="is-actions"><button type="button" class="icon-button is-danger" data-delete-sub aria-label="Supprimer"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg></button></td></tr>';
    }).join('');
  }
  $('[data-sub-rows]').addEventListener('click', function (e) {
    if (!e.target.closest('[data-delete-sub]')) return;
    var id = e.target.closest('tr').dataset.id;
    if (!confirmAction('Supprimer cet abonné ?')) return;
    api('subscribers.php?id=' + encodeURIComponent(id), { method: 'DELETE' }).then(function () {
      state.subscribers = state.subscribers.filter(function (s) { return s.id !== id; });
      renderStats(); renderSubscribers(); toast('Abonné supprimé');
    }).catch(function (ex) { toast(ex.message); });
  });
  $('[data-copy-emails]').addEventListener('click', function () {
    copy(state.subscribers.map(function (s) { return s.email; }).join(', '), 'E-mails copiés');
  });
  $('[data-export-csv]').addEventListener('click', function () {
    var csv = '﻿email;langue;date\n' + state.subscribers.map(function (s) { return [s.email, s.lang, s.created_at].join(';'); }).join('\n');
    var a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    a.download = 'webina-newsletter.csv';
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  });

  boot();
})();
