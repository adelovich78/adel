/* Webina — CRM : prospection (liste, recherche automatique, modèles d'e-mails, import, envoi progressif) */
(function () {
  'use strict';

  var W = window.WebinaAdmin;
  if (!W) return;
  var api = W.api, toast = W.toast, esc = W.esc;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var panel = $('[data-panel="prospect"]');
  if (!panel) return;

  var DISTRICTS = ['Adalar', 'Arnavutköy', 'Ataşehir', 'Avcılar', 'Bağcılar', 'Bahçelievler', 'Bakırköy', 'Başakşehir', 'Bayrampaşa', 'Beşiktaş',
    'Beykoz', 'Beylikdüzü', 'Beyoğlu', 'Büyükçekmece', 'Çatalca', 'Çekmeköy', 'Esenler', 'Esenyurt', 'Eyüpsultan', 'Fatih', 'Gaziosmanpaşa',
    'Güngören', 'Kadıköy', 'Kağıthane', 'Kartal', 'Küçükçekmece', 'Maltepe', 'Pendik', 'Sancaktepe', 'Sarıyer', 'Silivri', 'Sultanbeyli',
    'Sultangazi', 'Şile', 'Şişli', 'Tuzla', 'Ümraniye', 'Üsküdar', 'Zeytinburnu'];

  var state = { loaded: false, items: [], trades: [], tpls: [], sel: {}, filter: 'all', q: '', trade: '', tplId: null, results: [], rsel: {}, queue: null, running: false, timer: null };
  var tradeLabel = function (k) { var t = state.trades.filter(function (x) { return x.key === k; })[0]; return t ? t.label : k; };

  function fmt(s) {
    if (!s) return '';
    var d = new Date(String(s).replace(' ', 'T'));
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  }
  // Premier numéro de la cellule, au format international turc (90…) pour WhatsApp
  function waNumber(phone) {
    var first = String(phone || '').split(/[\/,;]| - /)[0];
    var d = first.replace(/\D/g, '');
    if (!d) return '';
    if (d.indexOf('90') === 0 && d.length === 12) return d;
    if (d.charAt(0) === '0') d = d.slice(1);
    return d.length === 10 ? '90' + d : d;
  }
  var DEMO = { dentist: 'https://dental.webina.com.tr/', doctor: 'https://doktor.webina.com.tr/', vet: 'https://veteriner.webina.com.tr/',
    lawyer: 'https://avukat.webina.com.tr/', accountant: 'https://avukat.webina.com.tr/', beauty: 'https://kuafor.webina.com.tr/',
    auto: 'https://otoelektrik.webina.com.tr/', cleaning: 'https://nakliyet.webina.com.tr/', realestate: 'https://emlak.webina.com.tr/',
    events: 'https://restoran.webina.com.tr/', photo: 'https://adelbenelli.webina.com.tr/', shop: 'https://eticaret.webina.com.tr/' };
  // Message WhatsApp court : pour les établissements qui n'ont qu'un téléphone (la majorité de ceux sans site)
  function waText(c) {
    var name = c.company || c.name;
    return 'Merhaba, ben Webina\'dan yazıyorum. ' + name + ' için mesleğinize özel, telefonda hızlı açılan bir web sitesi hazırlıyoruz ' +
      '(alan adı, hosting ve logo ilk yıl ücretsiz, 17.000 TL\'den başlayan fiyatlarla). Örnek: ' + (DEMO[c.trade] || 'https://webina.com.tr/sektorler') +
      ' — Uygun olursanız 10 dakikalık kısa bir görüşme yapabilir miyiz?';
  }

  /* ---------- Chargement ---------- */
  function load() {
    return Promise.all([api('prospect.php?r=list'), api('prospect.php?r=templates')]).then(function (r) {
      state.items = r[0].items || []; state.trades = r[0].trades || []; state.queue = r[0].queue; state.tpls = r[1].items || [];
      state.loaded = true;
      fillSelects(); renderList(); renderTpls(); renderQueue();
      if (state.queue && state.queue.queued && !state.running) startSending();
    }).catch(function (e) { toast(e.message); });
  }
  document.addEventListener('admin:tab', function (e) { if (e.detail === 'prospect' && !state.loaded) load(); });

  function fillSelects() {
    var opts = state.trades.map(function (t) { return '<option value="' + t.key + '">' + esc(t.label) + '</option>'; }).join('');
    $('[data-pr-trade]').innerHTML = '<option value="">Tous les métiers</option>' + opts;
    $('[data-pr-trade]').value = state.trade;
    $('#pr-ftrade').innerHTML = state.trades.filter(function (t) { return t.search; }).map(function (t) { return '<option value="' + t.key + '">' + esc(t.label) + '</option>'; }).join('');
    $('#pr-ttrade').innerHTML = opts;
    $('#pr-districts').innerHTML = DISTRICTS.map(function (d) { return '<option value="' + d + '">'; }).join('');
    var cur = $('[data-pr-template]').value;
    $('[data-pr-template]').innerHTML = '<option value="auto">Modèle selon le métier (automatique)</option>' +
      state.tpls.map(function (t) { return '<option value="' + t.id + '">' + esc(t.name) + '</option>'; }).join('');
    $('[data-pr-template]').value = cur && (cur === 'auto' || state.tpls.some(function (t) { return t.id === cur; })) ? cur : 'auto';
  }

  /* ---------- Vues ---------- */
  $('[data-pr-view]').addEventListener('change', function (e) {
    $$('[data-pr-section]', panel).forEach(function (s) { s.hidden = s.dataset.prSection !== e.target.value; });
  });

  /* ---------- Liste ---------- */
  function visible() {
    var q = state.q.toLowerCase();
    return state.items.filter(function (c) {
      if (state.trade && c.trade !== state.trade) return false;
      if (state.filter === 'nosite' && c.website) return false;
      if (state.filter === 'email' && !c.email) return false;
      if (state.filter === 'todo' && (c.last_mailed_at || c.mail_status === 'queued')) return false;
      if (q && [c.name, c.company, c.district, c.email, c.sector].join(' ').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
  }
  function canMail(c) { return !!c.email && c.email_optout != 1; }
  function renderList() {
    var rows = visible();
    var tb = $('[data-pr-rows]');
    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="8" class="is-muted">' + (state.items.length ? 'Aucun prospect pour ce filtre' : 'Aucun prospect : importez votre liste ou lancez une recherche automatique') + '</td></tr>';
    } else {
      tb.innerHTML = rows.map(function (c) {
        var mail = canMail(c);
        var last = c.email_optout == 1 ? '<span class="admin_badge is-lost">Désinscrit</span>'
          : c.mail_status === 'queued' ? '<span class="admin_badge is-new">En file</span>'
          : c.mail_status === 'failed' ? '<span class="admin_badge is-lost">Échec</span>'
          : c.last_mailed_at ? '<span class="text-color-secondary">' + esc(fmt(c.last_mailed_at)) + '</span>' : '<span class="is-muted">—</span>';
        var wa = waNumber(c.phone);
        return '<tr data-id="' + esc(c.id) + '">' +
          '<td class="pr_cell-check"><input type="checkbox" data-pr-pick ' + (mail ? '' : 'disabled title="Pas d\'adresse e-mail"') + (state.sel[c.id] ? ' checked' : '') + ' aria-label="Sélectionner ' + esc(c.name) + '"></td>' +
          '<td><span class="pr_name">' + esc(c.company || c.name) + '</span>' + (c.source ? '<span class="pr_meta">' + esc(c.source) + '</span>' : '') + '</td>' +
          '<td>' + esc(tradeLabel(c.trade)) + '</td>' +
          '<td>' + esc(c.district || c.city || '—') + '</td>' +
          '<td class="pr_contact">' + (c.email ? '<span>' + esc(c.email) + '</span>' : '') + (c.phone ? '<span class="pr_meta">' + esc(c.phone) + '</span>' : '') + (!c.email && !c.phone ? '—' : '') + '</td>' +
          '<td>' + (c.website ? '<a class="text-link" href="' + esc(/^https?:/i.test(c.website) ? c.website : 'https://' + c.website) + '" target="_blank" rel="noopener">Oui</a>' : '<span class="admin_badge is-new">Sans site</span>') + '</td>' +
          '<td>' + last + '</td>' +
          '<td class="is-actions"><span class="admin_row-actions">' +
            (wa ? '<a class="icon-button" href="https://wa.me/' + wa + '?text=' + encodeURIComponent(waText(c)) + '" target="_blank" rel="noopener" title="Message WhatsApp prêt à envoyer" aria-label="WhatsApp"><svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg></a>' : '') +
            (c.phone ? '<a class="icon-button" href="tel:' + esc(String(c.phone).split(/[\/,;]/)[0].replace(/[^\d+]/g, '')) + '" title="Appeler" aria-label="Appeler"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg></a>' : '') +
            '<button type="button" class="icon-button" data-pr-open title="Ouvrir la fiche" aria-label="Ouvrir la fiche"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></button>' +
          '</span></td></tr>';
      }).join('');
    }
    updateBar();
  }
  function selectedIds() { return Object.keys(state.sel).filter(function (k) { return state.sel[k]; }); }
  function updateBar() {
    var n = selectedIds().length;
    $('[data-pr-count]').textContent = n + (n > 1 ? ' sélectionnés' : ' sélectionné');
    $('[data-pr-send]').disabled = !n;
    var mailable = visible().filter(canMail);
    var all = $('[data-pr-all]');
    all.checked = mailable.length > 0 && mailable.every(function (c) { return state.sel[c.id]; });
    all.indeterminate = !all.checked && mailable.some(function (c) { return state.sel[c.id]; });
  }
  $('[data-pr-q]').addEventListener('input', function (e) { state.q = e.target.value; renderList(); });
  $('[data-pr-trade]').addEventListener('change', function (e) { state.trade = e.target.value; renderList(); });
  $('[data-pr-filter]').addEventListener('change', function (e) { state.filter = e.target.value; renderList(); });
  $('[data-pr-all]').addEventListener('change', function (e) {
    visible().filter(canMail).forEach(function (c) { state.sel[c.id] = e.target.checked; });
    renderList();
  });
  $('[data-pr-rows]').addEventListener('change', function (e) {
    if (!e.target.matches('[data-pr-pick]')) return;
    state.sel[e.target.closest('tr').dataset.id] = e.target.checked;
    updateBar();
  });
  $('[data-pr-rows]').addEventListener('click', function (e) {
    if (e.target.closest('[data-pr-open]') && window.CRM) window.CRM.open(e.target.closest('tr').dataset.id);
  });

  /* ---------- Aperçu obligatoire avant l'envoi ----------
     « Envoyer » ouvre l'aperçu : mode d'envoi, expéditeur, destinataires, e-mail tel qu'il sera reçu.
     Rien ne part tant que « Confirmer l'envoi » n'est pas cliqué. */
  var dlg = document.createElement('dialog');
  dlg.className = 'pr_dialog';
  dlg.innerHTML = '<div class="pr_dialog-inner">' +
    '<div class="pr_dialog-head"><div><p class="pr_meta" data-pv-from></p><h2 class="heading-style-h5" data-pv-subject></h2></div>' +
    '<button type="button" class="icon-button" data-pv-close aria-label="Fermer"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button></div>' +
    '<div class="choice_group pr_modes" data-pv-mode>' +
      '<label class="choice_chip"><input type="radio" name="pvm" value="bcc" checked><span>Envoi groupé en Cci</span></label>' +
      '<label class="choice_chip"><input type="radio" name="pvm" value="single"><span>Un e-mail personnalisé par prospect</span></label>' +
    '</div>' +
    '<p class="pr_meta" data-pv-explain></p>' +
    '<div class="pr_pv-nav" data-pv-nav><button type="button" class="icon-button" data-pv-prev aria-label="Précédent"><svg class="icon" aria-hidden="true"><use href="#i-chevron-left"/></svg></button>' +
      '<span data-pv-who></span><button type="button" class="icon-button" data-pv-next aria-label="Suivant"><svg class="icon" aria-hidden="true"><use href="#i-chevron-right"/></svg></button></div>' +
    '<iframe class="pr_dialog-frame" title="Aperçu de l\'e-mail" data-pv-frame></iframe>' +
    '<div class="button-group pr_pv-actions"><button type="button" class="button" data-pv-confirm><svg class="icon" aria-hidden="true"><use href="#i-send"/></svg><span data-pv-confirm-label>Confirmer l\'envoi</span></button>' +
    '<button type="button" class="button is-secondary" data-pv-close>Annuler</button></div></div>';
  document.body.appendChild(dlg);
  var pv = { ids: [], i: 0, mode: 'bcc' };

  function tplFor(c) {
    var tid = $('[data-pr-template]').value;
    if (tid !== 'auto') return state.tpls.filter(function (t) { return t.id === tid; })[0];
    return state.tpls.filter(function (t) { return t.trade === c.trade; })[0] || state.tpls.filter(function (t) { return t.trade === 'general'; })[0];
  }
  function client(id) { return state.items.filter(function (x) { return x.id === id; })[0]; }
  // Envoi groupé : un e-mail par modèle ; on prévisualise chaque modèle une fois
  function groups() {
    var g = {};
    pv.ids.forEach(function (id) { var t = tplFor(client(id)); if (!t) return; (g[t.id] = g[t.id] || { tpl: t, ids: [] }).ids.push(id); });
    return Object.keys(g).map(function (k) { return g[k]; });
  }
  function renderPreview() {
    var bcc = pv.mode === 'bcc', list = bcc ? groups() : pv.ids, n = list.length;
    pv.i = Math.max(0, Math.min(pv.i, n - 1));
    var id = bcc ? list[pv.i].ids[0] : list[pv.i];
    var c = client(id), tpl = bcc ? list[pv.i].tpl : tplFor(c);
    var lim = state.queue || {};
    $('[data-pv-explain]', dlg).textContent = bcc
      ? pv.ids.length + ' destinataire(s) en copie cachée : aucun ne voit les adresses des autres. ' + n + ' e-mail(s) (un par modèle), par lots de 25 au plus. Texte identique pour tous, sans le nom de l\'établissement.'
      : pv.ids.length + ' e-mail(s) personnalisé(s) avec le nom et le quartier de chaque établissement, envoyés un par un, espacés.';
    $('[data-pv-who]', dlg).textContent = bcc
      ? 'Modèle « ' + tpl.name + ' » · ' + list[pv.i].ids.length + ' destinataire(s) · ' + (pv.i + 1) + ' / ' + n
      : (c.company || c.name) + ' · ' + c.email + ' · ' + (pv.i + 1) + ' / ' + n;
    $('[data-pv-prev]', dlg).disabled = pv.i === 0;
    $('[data-pv-next]', dlg).disabled = pv.i >= n - 1;
    $('[data-pv-confirm-label]', dlg).textContent = 'Confirmer l\'envoi à ' + pv.ids.length + ' prospect(s)';
    api('prospect.php?r=preview', { method: 'POST', body: { client_id: id, template_id: tpl.id, mode: pv.mode } }).then(function (p) {
      $('[data-pv-from]', dlg).textContent = 'De : ' + (p.from || '—') + (lim.daily ? ' · limite ' + lim.daily + ' destinataires par jour' : '');
      $('[data-pv-subject]', dlg).textContent = p.subject;
      $('[data-pv-frame]', dlg).srcdoc = p.html;
    }).catch(function (e) { toast(e.message); });
  }
  function openPreview() {
    pv.ids = selectedIds().filter(function (id) { var c = client(id); return c && canMail(c); });
    if (!pv.ids.length) return toast('Aucun prospect avec e-mail sélectionné');
    if (!state.tpls.length) return toast('Aucun modèle');
    pv.i = 0;
    renderPreview();
    dlg.showModal();
  }
  $('[data-pr-send]').addEventListener('click', openPreview);
  dlg.addEventListener('click', function (e) {
    if (e.target.closest('[data-pv-close]')) dlg.close();
    else if (e.target.closest('[data-pv-prev]')) { pv.i--; renderPreview(); }
    else if (e.target.closest('[data-pv-next]')) { pv.i++; renderPreview(); }
    else if (e.target.closest('[data-pv-confirm]')) confirmSend();
  });
  $('[data-pv-mode]', dlg).addEventListener('change', function (e) { pv.mode = e.target.value; pv.i = 0; renderPreview(); });

  function confirmSend() {
    var tid = $('[data-pr-template]').value, btn = $('[data-pv-confirm]', dlg);
    btn.disabled = true;
    api('prospect.php?r=queue', { method: 'POST', body: { client_ids: pv.ids, auto: tid === 'auto', template_id: tid === 'auto' ? null : tid, mode: pv.mode } }).then(function (r) {
      var sk = r.skipped || {}, skipped = (sk.email || 0) + (sk.optout || 0) + (sk.already || 0);
      toast(r.queued + ' destinataire(s) en file' + (skipped ? ' · ' + skipped + ' ignoré(s) (déjà contacté, désinscrit ou sans e-mail)' : ''));
      dlg.close();
      state.sel = {};
      state.queue = r;
      return api('prospect.php?r=list').then(function (d) { state.items = d.items || []; renderList(); renderQueue(); startSending(); });
    }).catch(function (e) { toast(e.message); }).then(function () { btn.disabled = false; });
  }

  function renderQueue() {
    var q = state.queue, box = $('[data-pr-queue]');
    if (!q || (!q.queued && !q.sentToday)) { box.hidden = true; return; }
    box.hidden = false;
    var status = !q.queued ? 'File vide'
      : q.sentToday >= q.daily ? 'Limite du jour atteinte : la suite partira demain'
      : state.running ? (q.wait > 0 ? 'Prochain envoi dans ' + q.wait + ' s' : 'Envoi en cours…') : 'En pause';
    box.innerHTML = '<span class="pr_queue-stat"><strong>' + q.queued + '</strong> en file</span>' +
      '<span class="pr_queue-stat"><strong>' + q.sentToday + '</strong> / ' + q.daily + ' envoyés aujourd\'hui</span>' +
      '<span class="pr_queue-status">' + (state.running && q.queued ? '<span class="live-dot" aria-hidden="true"></span>' : '') + esc(status) + '</span>' +
      (q.queued ? '<span class="pr_queue-actions">' + (state.running
        ? '<button type="button" class="button is-small is-secondary" data-pr-pause>Pause</button>'
        : '<button type="button" class="button is-small" data-pr-resume>Reprendre</button>') +
        '<button type="button" class="button is-small is-secondary" data-pr-clear>Vider la file</button></span>' : '') +
      '<span class="pr_queue-note">Gardez cette page ouverte pendant l\'envoi (ou laissez la tâche cron s\'en charger).</span>';
  }
  $('[data-pr-queue]').addEventListener('click', function (e) {
    if (e.target.closest('[data-pr-pause]')) { stopSending(); renderQueue(); }
    else if (e.target.closest('[data-pr-resume]')) startSending();
    else if (e.target.closest('[data-pr-clear]')) {
      if (!W.confirm('Retirer de la file les e-mails pas encore envoyés ?')) return;
      stopSending();
      api('prospect.php?r=clear', { method: 'POST', body: {} }).then(function (q) { state.queue = q; toast(q.removed + ' e-mail(s) retiré(s)'); return api('prospect.php?r=list'); })
        .then(function (d) { state.items = d.items || []; renderList(); renderQueue(); }).catch(function (e) { toast(e.message); });
    }
  });
  function stopSending() { state.running = false; clearTimeout(state.timer); }
  function startSending() { state.running = true; renderQueue(); step(); }
  function step() {
    clearTimeout(state.timer);
    if (!state.running) return;
    api('prospect.php?r=send', { method: 'POST', body: {} }).then(function (r) {
      state.queue = r;
      if (r.sent) {
        var sentTo = r.sent.emails || [r.sent.to];
        state.items.forEach(function (c) {
          if (sentTo.indexOf(c.email) > -1) { c.mail_status = r.sent.ok ? 'sent' : 'failed'; if (r.sent.ok) c.last_mailed_at = new Date().toISOString(); }
        });
        renderList();
        if (!r.sent.ok) toast('Échec de l\'envoi à ' + r.sent.to);
      }
      if (r.reason === 'empty' || r.reason === 'daily' || !r.queued) { state.running = r.queued > 0 && r.reason !== 'daily' ? state.running : false; renderQueue(); if (!state.running) return; }
      renderQueue();
      // Compte à rebours affiché, puis nouvel essai quand l'espacement est écoulé
      var wait = Math.max(1, r.wait || r.gap || 5);
      var left = wait;
      (function tick() {
        if (!state.running) return;
        if (left <= 0) { step(); return; }
        state.queue.wait = left; renderQueue(); left--;
        state.timer = setTimeout(tick, 1000);
      })();
    }).catch(function (e) { stopSending(); renderQueue(); toast(e.message); });
  }

  /* ---------- Recherche automatique ---------- */
  $('[data-pr-finder]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target, btn = $('button[type="submit"]', f), box = $('[data-pr-results]');
    btn.disabled = true;
    box.innerHTML = '<p class="text-color-secondary pr_wait">Recherche en cours, cela peut prendre jusqu\'à 30 secondes…</p>';
    api('prospect.php?r=search&trade=' + encodeURIComponent(f.trade.value) + '&district=' + encodeURIComponent(f.district.value.trim())).then(function (r) {
      state.results = r.items || []; state.rsel = {};
      state.results.forEach(function (x, i) { if (!x.known && !x.website) state.rsel[i] = true; });
      state.rlabel = r.label; state.rdist = f.district.value.trim();
      renderResults();
    }).catch(function (e) { box.innerHTML = '<p class="form_error">' + esc(e.message) + '</p>'; })
      .then(function () { btn.disabled = false; });
  });
  function renderResults() {
    var box = $('[data-pr-results]'), list = state.results;
    if (!list.length) { box.innerHTML = '<p class="text-color-secondary">Aucun établissement trouvé sur la carte pour cette recherche. Essayez un quartier voisin ou tout İstanbul.</p>'; return; }
    var nosite = list.filter(function (x) { return !x.website; }).length, mails = list.filter(function (x) { return x.email; }).length;
    var n = Object.keys(state.rsel).filter(function (k) { return state.rsel[k]; }).length;
    box.innerHTML = '<div class="pr_bar"><span class="text-color-secondary">' + list.length + ' résultats · <strong>' + nosite + '</strong> sans site · ' + mails + ' avec e-mail</span>' +
      '<button type="button" class="button is-small" data-pr-add ' + (n ? '' : 'disabled') + '><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>Ajouter ' + n + ' aux prospects</button></div>' +
      '<div class="admin_table-wrap"><table class="admin_table pr_table"><thead><tr><th class="pr_cell-check"></th><th>Établissement</th><th>Quartier</th><th>Contact</th><th>Site</th></tr></thead><tbody>' +
      list.map(function (x, i) {
        return '<tr data-i="' + i + '"><td class="pr_cell-check"><input type="checkbox" data-pr-rpick ' + (x.known ? 'disabled' : '') + (state.rsel[i] ? ' checked' : '') + ' aria-label="Sélectionner"></td>' +
          '<td><span class="pr_name">' + esc(x.name) + '</span>' + (x.known ? '<span class="pr_meta">Déjà dans le CRM</span>' : x.address ? '<span class="pr_meta">' + esc(x.address) + '</span>' : '') + '</td>' +
          '<td>' + esc(x.district || '—') + '</td>' +
          '<td class="pr_contact">' + (x.email ? '<span>' + esc(x.email) + '</span>' : '') + (x.phone ? '<span class="pr_meta">' + esc(x.phone) + '</span>' : '') + (!x.email && !x.phone ? '<span class="is-muted">—</span>' : '') + '</td>' +
          '<td>' + (x.website ? '<a class="text-link" href="' + esc(/^https?:/i.test(x.website) ? x.website : 'https://' + x.website) + '" target="_blank" rel="noopener">Oui</a>' : '<span class="admin_badge is-new">Sans site</span>') + '</td></tr>';
      }).join('') + '</tbody></table></div><p class="pr_meta pr_osm">Données © les contributeurs d\'OpenStreetMap (licence ODbL)</p>';
  }
  $('[data-pr-results]').addEventListener('change', function (e) {
    if (!e.target.matches('[data-pr-rpick]')) return;
    state.rsel[e.target.closest('tr').dataset.i] = e.target.checked;
    renderResults();
  });
  $('[data-pr-results]').addEventListener('click', function (e) {
    if (!e.target.closest('[data-pr-add]')) return;
    var rows = state.results.filter(function (x, i) { return state.rsel[i]; }).map(function (x) {
      return { name: x.name, sector: state.rlabel, district: x.district || state.rdist, email: x.email, phone: x.phone, website: x.website,
        source: 'OpenStreetMap', notes: [x.address, x.social].filter(Boolean).join(' · ') };
    });
    importRows(rows);
  });

  /* ---------- Import (copier-coller Excel ou CSV) ---------- */
  var COLS = {
    name: ['işletme', 'isletme', 'büro', 'buro', 'adı', 'name', 'nom', 'firma', 'établissement'],
    sector: ['kategori', 'meslek', 'sektör', 'sektor', 'category', 'métier', 'secteur'],
    district: ['ilçe', 'ilce', 'semt', 'district', 'quartier'],
    email: ['e-posta', 'eposta', 'email', 'e-mail', 'mail'],
    phone: ['telefon', 'phone', 'tel', 'gsm'],
    website: ['web', 'site'],
    source: ['kaynak', 'source'],
    notes: ['not', 'notes', 'öncelik', 'oncelik']
  };
  function parseTable(text) {
    text = text.replace(/\r/g, '').trim();
    if (!text) return [];
    var first = text.split('\n')[0];
    var sep = first.indexOf('\t') > -1 ? '\t' : (first.split(';').length > first.split(',').length ? ';' : ',');
    // Analyse CSV avec guillemets (cellules contenant des retours à la ligne ou des séparateurs)
    var rows = [], row = [], cell = '', inQ = false;
    for (var i = 0; i < text.length; i++) {
      var ch = text[i];
      if (inQ) {
        if (ch === '"' && text[i + 1] === '"') { cell += '"'; i++; }
        else if (ch === '"') inQ = false;
        else cell += ch;
      } else if (ch === '"' && cell === '') inQ = true;
      else if (ch === sep) { row.push(cell); cell = ''; }
      else if (ch === '\n') { row.push(cell); rows.push(row); row = []; cell = ''; }
      else cell += ch;
    }
    row.push(cell); rows.push(row);
    // Minuscules à la turque : « İlçe » → « ilçe » (en minuscules simples, le İ donnerait « i̇ »)
    var head = rows.shift().map(function (h) { return h.toLocaleLowerCase('tr-TR').trim(); });
    var map = {};
    Object.keys(COLS).forEach(function (k) {
      var idx = head.findIndex(function (h, j) {
        if (Object.keys(map).some(function (m) { return map[m] === j; })) return false;
        return COLS[k].some(function (w) { return h === w || h.indexOf(w) > -1; });
      });
      if (idx > -1) map[k] = idx;
    });
    if (map.name == null) return null;
    return rows.filter(function (r) { return r.join('').trim(); }).map(function (r) {
      var o = {};
      Object.keys(map).forEach(function (k) { o[k] = (r[map[k]] || '').trim(); });
      return o;
    });
  }
  var parsed = [];
  function onPaste() {
    var rows = parseTable($('[data-pr-paste]').value);
    var info = $('[data-pr-import-info]');
    if (rows === null) { parsed = []; info.textContent = 'Colonne du nom introuvable : gardez la ligne de titres.'; }
    else { parsed = rows; info.textContent = rows.length ? rows.length + ' ligne(s) prête(s) à importer' : ''; }
    $('[data-pr-import]').disabled = !parsed.length;
  }
  $('[data-pr-paste]').addEventListener('input', onPaste);
  $('[data-pr-file]').addEventListener('change', function (e) {
    var f = e.target.files[0]; if (!f) return;
    var rd = new FileReader();
    rd.onload = function () { $('[data-pr-paste]').value = String(rd.result); onPaste(); };
    rd.readAsText(f, 'utf-8');
  });
  $('[data-pr-import]').addEventListener('click', function () { importRows(parsed); });
  function importRows(rows) {
    if (!rows.length) return;
    api('prospect.php?r=import', { method: 'POST', body: { rows: rows } }).then(function (r) {
      toast(r.added + ' prospect(s) ajouté(s)' + (r.skipped ? ' · ' + r.skipped + ' doublon(s) ignoré(s)' : ''));
      $('[data-pr-paste]').value = ''; parsed = []; $('[data-pr-import]').disabled = true; $('[data-pr-import-info]').textContent = '';
      return load().then(function () {
        $('[data-pr-view] input[value="list"]').click();
        if (window.CRM && window.CRM.load) window.CRM.load();
      });
    }).catch(function (e) { toast(e.message); });
  }

  /* ---------- Modèles ---------- */
  var tf = $('[data-pr-tpl-form]');
  function renderTpls() {
    if (!state.tplId && state.tpls.length) state.tplId = state.tpls[0].id;
    $('[data-pr-tpls]').innerHTML = state.tpls.map(function (t) {
      return '<li><button type="button" class="pr_tpl-item' + (t.id === state.tplId ? ' is-active' : '') + '" data-tpl="' + t.id + '"><span>' + esc(t.name) + '</span><span class="pr_meta">' + esc(tradeLabel(t.trade)) + '</span></button></li>';
    }).join('');
    var t = state.tpls.filter(function (x) { return x.id === state.tplId; })[0];
    tf.name.value = t ? t.name : ''; tf.trade.value = t ? t.trade : 'general'; tf.subject.value = t ? t.subject : ''; tf.body.value = t ? t.body : '';
  }
  $('[data-pr-tpls]').addEventListener('click', function (e) {
    var b = e.target.closest('[data-tpl]'); if (!b) return;
    state.tplId = b.dataset.tpl; renderTpls();
  });
  $('[data-pr-tpl-new]').addEventListener('click', function () {
    state.tplId = null;
    $$('.pr_tpl-item', panel).forEach(function (x) { x.classList.remove('is-active'); });
    tf.name.value = ''; tf.trade.value = 'general'; tf.subject.value = '{isletme} için profesyonel bir web sitesi'; tf.body.value = 'Merhaba,\n\n';
    tf.name.focus();
  });
  tf.addEventListener('submit', function (e) {
    e.preventDefault();
    var body = { name: tf.name.value, trade: tf.trade.value, subject: tf.subject.value, body: tf.body.value };
    var req = state.tplId ? api('prospect.php?r=templates&id=' + state.tplId, { method: 'PUT', body: body }) : api('prospect.php?r=templates', { method: 'POST', body: body });
    req.then(function (r) {
      if (r.id) state.tplId = r.id;
      toast('Modèle enregistré');
      return api('prospect.php?r=templates').then(function (d) { state.tpls = d.items || []; renderTpls(); fillSelects(); });
    }).catch(function (e) { toast(e.message); });
  });
  $('[data-pr-tpl-del]').addEventListener('click', function () {
    if (!state.tplId || !W.confirm('Supprimer ce modèle ?')) return;
    api('prospect.php?r=templates&id=' + state.tplId, { method: 'DELETE' }).then(function () {
      state.tplId = null; toast('Modèle supprimé');
      return api('prospect.php?r=templates').then(function (d) { state.tpls = d.items || []; renderTpls(); fillSelects(); });
    }).catch(function (e) { toast(e.message); });
  });
})();
