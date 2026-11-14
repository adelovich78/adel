/* Webina — espace client (my.webina.com.tr)
   Connexion par code e-mail ou lien à usage unique, puis un tableau de bord qui répond d'abord
   à une question : « dois-je faire quelque chose ? ». */
(function () {
  'use strict';

  var T = {
    tr: {
      panel: 'Müşteri Paneli', logout: 'Çıkış', loginT: 'Müşteri paneline giriş', loginP: 'E-posta adresinizi yazın, size 6 haneli bir giriş kodu gönderelim.',
      email: 'E-posta', sendCode: 'Kod gönder', codeSent: 'E-posta gönderildi:', code: 'Giriş kodu', login: 'Giriş yap', changeEmail: 'E-postayı değiştir',
      noAccess: 'Erişiminiz yok mu?', contactUs: 'Bize yazın', hello: 'Merhaba', invoices: 'Faturalar', notices: 'Bildirimler', account: 'Hesap bilgileri',
      name: 'Ad soyad', company: 'Firma', phone: 'Telefon', emailHint: 'E-posta adresinizi değiştirmek için bize yazın.', save: 'Kaydet',
      support: 'Destek', supportP: 'Bir değişiklik mi istiyorsunuz, bir sorunuz mu var? Yazın, aynı gün dönelim.', message: 'Mesajınız', send: 'Gönder',
      changePlan: 'Paketimi değiştir', changePlanP: 'Yeni paketinizi seçin. Farkı ve tarihi sizinle birlikte netleştiririz.', note: 'Not (isteğe bağlı)', sendRequest: 'Talebi gönder',
      errEmail: 'Lütfen geçerli bir e-posta yazın.', errCode: 'Kod hatalı veya süresi dolmuş.', errLink: 'Bu giriş bağlantısı geçersiz veya süresi dolmuş. E-postanızla giriş yapabilirsiniz.', errGeneric: 'Bir sorun oluştu, lütfen tekrar deneyin.',
      allGood: 'Her şey yolunda', allGoodP: 'Şu anda yapmanız gereken bir şey yok.', todo: '{n} işlem bekliyor', todo1: '1 işlem bekliyor',
      act: { pay: 'Faturanızı ödeyin: {number} · {amount} · son gün {date}', renew: 'Aboneliğiniz {date} tarihinde yenilenmeli ({days})', renewLate: 'Aboneliğinizin süresi {date} tarihinde doldu', domain: 'Alan adınız {date} tarihinde sona eriyor ({days})', domainLate: 'Alan adınızın süresi {date} tarihinde doldu', preview: 'Siteniz önizlemede: inceleyin ve onaylayın', ssl: 'SSL sertifikanızın süresi doldu, ilgileniyoruz' },
      btn: { pay: 'Nasıl ödenir?', renew: 'Yenilemeyi onayla', preview: 'Önizlemeyi aç', approve: 'Siteyi onayla', open: 'Siteyi aç', upgrade: 'Paketimi değiştir' },
      tile: { domain: 'Alan adı', hosting: 'Hosting', ssl: 'SSL', sub: 'Abonelik', left: 'Kalan süre', payment: 'Ödeme durumu' },
      st: { ok: 'Aktif', soon: 'Yakında yenilenmeli', expired: 'Süresi doldu', none: 'Bilgi yok', active: 'Aktif', pending: 'Hazırlanıyor', paid: 'Ödendi', due: 'Ödeme bekleniyor', overdue: 'Gecikmiş', unpaid: 'Ödenmedi' },
      site: { building: 'Hazırlanıyor', preview: 'Önizlemede', live: 'Yayında', suspended: 'Durduruldu' },
      plan: { vitrin: 'Vitrin', randevu: 'Randevu', eticaret: 'E-ticaret', ozel: 'Özel' },
      until: '{date} tarihine kadar', next: 'Sonraki ödeme: {date}', perYear: '/ yıl', days: '{n} gün', daysLeft: '{n} gün kaldı', daysAgo: '{n} gün geçti', today: 'bugün',
      noService: 'Henüz aktif bir hizmetiniz yok. Siteniz hazırlandığında burada görünecek.', noInvoice: 'Henüz fatura yok.', noNotice: 'Yeni bildirim yok.',
      th: { number: 'No', label: 'Açıklama', date: 'Tarih', due: 'Son gün', amount: 'Tutar', status: 'Durum' },
      bankT: 'Havale / EFT ile ödeme', bankRef: 'Açıklamaya fatura numarasını yazın.', payWa: 'WhatsApp ile ödeme bilgisi isteyin',
      sent: 'Talebiniz bize ulaştı. En kısa sürede dönüş yapacağız.', saved: 'Bilgileriniz kaydedildi.', approved: 'Teşekkürler! Sitenizi yayına almak için hazırlanıyoruz.',
      confirmRenew: 'Aboneliğinizi bir yıl daha yenilemek istediğinizi onaylıyor musunuz?', confirmApprove: 'Önizlemedeki siteyi onaylıyor musunuz? Ardından alan adınızda yayına alacağız.',
      current: 'Mevcut paket', reqs: 'Son talepleriniz', req: { renew: 'Yenileme', upgrade: 'Paket değişikliği', support: 'Destek', approve: 'Site onayı' }, reqOpen: 'İşleniyor', reqDone: 'Tamamlandı'
    },
    en: {
      panel: 'Client area', logout: 'Log out', loginT: 'Sign in to your client area', loginP: 'Enter your email address and we will send you a 6-digit login code.',
      email: 'Email', sendCode: 'Send code', codeSent: 'Email sent to:', code: 'Login code', login: 'Sign in', changeEmail: 'Change email',
      noAccess: 'No access?', contactUs: 'Message us', hello: 'Hello', invoices: 'Invoices', notices: 'Notifications', account: 'Account details',
      name: 'Full name', company: 'Company', phone: 'Phone', emailHint: 'To change your email address, message us.', save: 'Save',
      support: 'Support', supportP: 'Need a change or have a question? Write to us and we will reply the same day.', message: 'Your message', send: 'Send',
      changePlan: 'Change my plan', changePlanP: 'Choose your new plan. We will confirm the difference and the date with you.', note: 'Note (optional)', sendRequest: 'Send request',
      errEmail: 'Please enter a valid email.', errCode: 'Wrong or expired code.', errLink: 'This login link is invalid or has expired. You can sign in with your email.', errGeneric: 'Something went wrong, please try again.',
      allGood: 'All good', allGoodP: 'There is nothing you need to do right now.', todo: '{n} actions needed', todo1: '1 action needed',
      act: { pay: 'Pay your invoice: {number} · {amount} · due {date}', renew: 'Your subscription renews on {date} ({days})', renewLate: 'Your subscription expired on {date}', domain: 'Your domain expires on {date} ({days})', domainLate: 'Your domain expired on {date}', preview: 'Your site is ready to preview: check it and approve it', ssl: 'Your SSL certificate expired, we are on it' },
      btn: { pay: 'How to pay', renew: 'Confirm renewal', preview: 'Open preview', approve: 'Approve the site', open: 'Open site', upgrade: 'Change my plan' },
      tile: { domain: 'Domain', hosting: 'Hosting', ssl: 'SSL', sub: 'Subscription', left: 'Time left', payment: 'Payment status' },
      st: { ok: 'Active', soon: 'Renewal due soon', expired: 'Expired', none: 'No data', active: 'Active', pending: 'In progress', paid: 'Paid', due: 'Payment due', overdue: 'Overdue', unpaid: 'Unpaid' },
      site: { building: 'In progress', preview: 'In preview', live: 'Live', suspended: 'Suspended' },
      plan: { vitrin: 'Showcase', randevu: 'Booking', eticaret: 'E-commerce', ozel: 'Custom' },
      until: 'until {date}', next: 'Next payment: {date}', perYear: '/ year', days: '{n} days', daysLeft: '{n} days left', daysAgo: '{n} days ago', today: 'today',
      noService: 'You have no active service yet. Your site will appear here once it is ready.', noInvoice: 'No invoices yet.', noNotice: 'No new notifications.',
      th: { number: 'No.', label: 'Description', date: 'Date', due: 'Due', amount: 'Amount', status: 'Status' },
      bankT: 'Pay by bank transfer', bankRef: 'Put the invoice number in the reference.', payWa: 'Ask for payment details on WhatsApp',
      sent: 'We received your request and will get back to you shortly.', saved: 'Your details were saved.', approved: 'Thank you! We are getting your site ready to go live.',
      confirmRenew: 'Do you confirm you want to renew your subscription for another year?', confirmApprove: 'Do you approve the site in preview? We will then publish it on your domain.',
      current: 'Current plan', reqs: 'Your latest requests', req: { renew: 'Renewal', upgrade: 'Plan change', support: 'Support', approve: 'Site approval' }, reqOpen: 'In progress', reqDone: 'Done'
    },
    fr: {
      panel: 'Espace client', logout: 'Déconnexion', loginT: 'Connexion à votre espace client', loginP: 'Saisissez votre adresse e-mail, nous vous envoyons un code de connexion à 6 chiffres.',
      email: 'E-mail', sendCode: 'Recevoir le code', codeSent: 'E-mail envoyé à :', code: 'Code de connexion', login: 'Se connecter', changeEmail: 'Changer d’adresse',
      noAccess: 'Pas d’accès ?', contactUs: 'Écrivez-nous', hello: 'Bonjour', invoices: 'Factures', notices: 'Notifications', account: 'Mon compte',
      name: 'Nom et prénom', company: 'Société', phone: 'Téléphone', emailHint: 'Pour changer d’adresse e-mail, écrivez-nous.', save: 'Enregistrer',
      support: 'Assistance', supportP: 'Une modification, une question ? Écrivez-nous, nous répondons dans la journée.', message: 'Votre message', send: 'Envoyer',
      changePlan: 'Changer de formule', changePlanP: 'Choisissez votre nouvelle formule. Nous confirmons avec vous la différence et la date.', note: 'Note (facultatif)', sendRequest: 'Envoyer la demande',
      errEmail: 'Saisissez une adresse e-mail valide.', errCode: 'Code incorrect ou expiré.', errLink: 'Ce lien de connexion n’est plus valable. Connectez-vous avec votre e-mail.', errGeneric: 'Une erreur est survenue, réessayez.',
      allGood: 'Tout est en ordre', allGoodP: 'Vous n’avez rien à faire pour le moment.', todo: '{n} actions à faire', todo1: '1 action à faire',
      act: { pay: 'Réglez votre facture : {number} · {amount} · avant le {date}', renew: 'Votre abonnement se renouvelle le {date} ({days})', renewLate: 'Votre abonnement a expiré le {date}', domain: 'Votre nom de domaine expire le {date} ({days})', domainLate: 'Votre nom de domaine a expiré le {date}', preview: 'Votre site est prêt : vérifiez-le et validez-le', ssl: 'Votre certificat SSL a expiré, nous nous en occupons' },
      btn: { pay: 'Comment payer', renew: 'Confirmer le renouvellement', preview: 'Voir la prévisualisation', approve: 'Valider le site', open: 'Ouvrir le site', upgrade: 'Changer de formule' },
      tile: { domain: 'Nom de domaine', hosting: 'Hébergement', ssl: 'SSL', sub: 'Abonnement', left: 'Temps restant', payment: 'Paiement' },
      st: { ok: 'Actif', soon: 'À renouveler bientôt', expired: 'Expiré', none: 'Non renseigné', active: 'Actif', pending: 'En cours', paid: 'Payé', due: 'Paiement attendu', overdue: 'En retard', unpaid: 'Non payée' },
      site: { building: 'En création', preview: 'En prévisualisation', live: 'En ligne', suspended: 'Suspendu' },
      plan: { vitrin: 'Vitrine', randevu: 'Rendez-vous', eticaret: 'E-commerce', ozel: 'Sur mesure' },
      until: 'jusqu’au {date}', next: 'Prochain paiement : {date}', perYear: '/ an', days: '{n} jours', daysLeft: 'encore {n} jours', daysAgo: 'il y a {n} jours', today: 'aujourd’hui',
      noService: 'Aucun service actif pour l’instant. Votre site apparaîtra ici dès qu’il sera prêt.', noInvoice: 'Aucune facture.', noNotice: 'Aucune nouvelle notification.',
      th: { number: 'N°', label: 'Libellé', date: 'Date', due: 'Échéance', amount: 'Montant', status: 'Statut' },
      bankT: 'Paiement par virement', bankRef: 'Indiquez le numéro de facture en référence.', payWa: 'Demander les coordonnées de paiement sur WhatsApp',
      sent: 'Votre demande nous est parvenue. Nous revenons vers vous rapidement.', saved: 'Vos informations sont enregistrées.', approved: 'Merci ! Nous préparons la mise en ligne de votre site.',
      confirmRenew: 'Confirmez-vous le renouvellement de votre abonnement pour un an ?', confirmApprove: 'Validez-vous le site en prévisualisation ? Nous le publierons ensuite sur votre domaine.',
      current: 'Formule actuelle', reqs: 'Vos dernières demandes', req: { renew: 'Renouvellement', upgrade: 'Changement de formule', support: 'Assistance', approve: 'Validation du site' }, reqOpen: 'En cours', reqDone: 'Traitée'
    },
    ar: {
      panel: 'منطقة العملاء', logout: 'خروج', loginT: 'الدخول إلى منطقة العملاء', loginP: 'اكتب بريدك الإلكتروني وسنرسل إليك رمز دخول من 6 أرقام.',
      email: 'البريد الإلكتروني', sendCode: 'أرسل الرمز', codeSent: 'تم إرسال رسالة إلى:', code: 'رمز الدخول', login: 'دخول', changeEmail: 'تغيير البريد',
      noAccess: 'ليس لديك وصول؟', contactUs: 'راسلنا', hello: 'مرحباً', invoices: 'الفواتير', notices: 'الإشعارات', account: 'بيانات الحساب',
      name: 'الاسم الكامل', company: 'الشركة', phone: 'الهاتف', emailHint: 'لتغيير بريدك الإلكتروني راسلنا.', save: 'حفظ',
      support: 'الدعم', supportP: 'تريد تعديلاً أو لديك سؤال؟ اكتب لنا ونرد في اليوم نفسه.', message: 'رسالتك', send: 'إرسال',
      changePlan: 'تغيير الباقة', changePlanP: 'اختر باقتك الجديدة، وسنؤكد معك الفرق والتاريخ.', note: 'ملاحظة (اختياري)', sendRequest: 'إرسال الطلب',
      errEmail: 'اكتب بريداً إلكترونياً صالحاً.', errCode: 'الرمز خاطئ أو منتهي الصلاحية.', errLink: 'رابط الدخول غير صالح أو منتهي. يمكنك الدخول بالبريد الإلكتروني.', errGeneric: 'حدث خطأ، حاول مرة أخرى.',
      allGood: 'كل شيء على ما يرام', allGoodP: 'لا يوجد ما تحتاج إلى فعله الآن.', todo: '{n} إجراءات مطلوبة', todo1: 'إجراء واحد مطلوب',
      act: { pay: 'ادفع فاتورتك: {number} · {amount} · قبل {date}', renew: 'يتجدد اشتراكك في {date} ({days})', renewLate: 'انتهى اشتراكك في {date}', domain: 'ينتهي نطاقك في {date} ({days})', domainLate: 'انتهى نطاقك في {date}', preview: 'موقعك جاهز للمعاينة: راجعه ووافق عليه', ssl: 'انتهت شهادة SSL ونحن نعمل على تجديدها' },
      btn: { pay: 'طريقة الدفع', renew: 'تأكيد التجديد', preview: 'افتح المعاينة', approve: 'الموافقة على الموقع', open: 'افتح الموقع', upgrade: 'تغيير الباقة' },
      tile: { domain: 'النطاق', hosting: 'الاستضافة', ssl: 'SSL', sub: 'الاشتراك', left: 'المدة المتبقية', payment: 'حالة الدفع' },
      st: { ok: 'نشط', soon: 'يجب التجديد قريباً', expired: 'منتهٍ', none: 'لا توجد بيانات', active: 'نشط', pending: 'قيد الإعداد', paid: 'مدفوع', due: 'بانتظار الدفع', overdue: 'متأخر', unpaid: 'غير مدفوعة' },
      site: { building: 'قيد الإعداد', preview: 'قيد المعاينة', live: 'منشور', suspended: 'موقوف' },
      plan: { vitrin: 'واجهة', randevu: 'مواعيد', eticaret: 'متجر إلكتروني', ozel: 'مخصص' },
      until: 'حتى {date}', next: 'الدفعة التالية: {date}', perYear: '/ سنة', days: '{n} يوماً', daysLeft: 'متبقٍ {n} يوماً', daysAgo: 'منذ {n} يوماً', today: 'اليوم',
      noService: 'لا توجد خدمة نشطة بعد. سيظهر موقعك هنا عندما يصبح جاهزاً.', noInvoice: 'لا توجد فواتير بعد.', noNotice: 'لا توجد إشعارات جديدة.',
      th: { number: 'رقم', label: 'الوصف', date: 'التاريخ', due: 'الاستحقاق', amount: 'المبلغ', status: 'الحالة' },
      bankT: 'الدفع بالتحويل البنكي', bankRef: 'اكتب رقم الفاتورة في وصف التحويل.', payWa: 'اطلب بيانات الدفع عبر واتساب',
      sent: 'وصلنا طلبك وسنعود إليك قريباً.', saved: 'تم حفظ بياناتك.', approved: 'شكراً! نستعد لنشر موقعك.',
      confirmRenew: 'هل تؤكد تجديد اشتراكك لسنة أخرى؟', confirmApprove: 'هل توافق على الموقع في المعاينة؟ سننشره بعد ذلك على نطاقك.',
      current: 'الباقة الحالية', reqs: 'آخر طلباتك', req: { renew: 'تجديد', upgrade: 'تغيير الباقة', support: 'دعم', approve: 'الموافقة على الموقع' }, reqOpen: 'قيد المعالجة', reqDone: 'مكتمل'
    }
  };
  var LOC = { tr: 'tr-TR', en: 'en-GB', fr: 'fr-FR', ar: 'ar' };

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var root = document.documentElement;
  var lang = 'tr', data = null;
  var savedLang = null;
  try { savedLang = localStorage.getItem('wb-portal-lang'); } catch (e) {}
  // Sans choix mémorisé : la langue du navigateur (client en France → français, etc.)
  var navLang = null;
  try {
    var prefs = (navigator.languages && navigator.languages.length) ? navigator.languages : [navigator.language || ''];
    for (var pi = 0; pi < prefs.length; pi++) { var pc = String(prefs[pi]).slice(0, 2).toLowerCase(); if (T[pc]) { navLang = pc; break; } }
  } catch (e) {}
  lang = savedLang || navLang || 'tr';
  if (!T[lang]) lang = 'tr';
  function L() { return T[lang]; }
  function fill(s, o) { return String(s).replace(/\{(\w+)\}/g, function (m, k) { return o[k] != null ? o[k] : m; }); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function icon(id) { return '<svg class="icon" aria-hidden="true"><use href="#' + id + '"/></svg>'; }
  function date(s) {
    if (!s) return '—';
    var d = new Date(String(s).slice(0, 10) + 'T12:00:00');
    return d.toLocaleDateString(LOC[lang], { day: 'numeric', month: 'long', year: 'numeric' });
  }
  function money(n, cur) {
    cur = cur || 'TRY';
    if (cur === 'TRY') return new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 0 }).format(n) + ' TL';
    return new Intl.NumberFormat(LOC[lang], { style: 'currency', currency: cur, maximumFractionDigits: 0 }).format(n);
  }
  function daysText(d) {
    if (d == null) return '';
    if (d === 0) return L().today;
    return d > 0 ? fill(L().daysLeft, { n: d }) : fill(L().daysAgo, { n: -d });
  }

  /* ---------- API ---------- */
  function api(r, opts) {
    opts = opts || {};
    return fetch('/api/portal.php?r=' + r, {
      method: opts.method || 'GET', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'webina' },
      body: opts.body ? JSON.stringify(opts.body) : undefined
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (j) {
        if (!res.ok) { var e = new Error(j.error || L().errGeneric); e.status = res.status; throw e; }
        return j;
      });
    });
  }

  /* ---------- Vues ---------- */
  function show(view) {
    document.querySelectorAll('[data-view]').forEach(function (v) { v.hidden = v.dataset.view !== view; });
    $('[data-logout]').hidden = view !== 'dash';
  }
  var toastTimer;
  function toast(msg) {
    var t = $('[data-toast]');
    t.textContent = msg; t.classList.add('is-on');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.classList.remove('is-on'); }, 3200);
  }

  function setLang(l, save) {
    lang = T[l] ? l : 'tr';
    root.lang = lang; root.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-t]').forEach(function (el) { var v = L()[el.dataset.t]; if (typeof v === 'string') el.textContent = v; });
    document.querySelectorAll('[data-lang]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.lang === lang)); });
    document.title = L().panel + ' — Webina';
    if (save) { try { localStorage.setItem('wb-portal-lang', lang); } catch (e) {} }
    if (data) render();
  }
  document.querySelectorAll('[data-lang]').forEach(function (b) {
    b.addEventListener('click', function () {
      setLang(b.dataset.lang, true);
      if (data) api('account', { method: 'PATCH', body: { name: data.client.name, phone: data.client.phone, company: data.client.company, lang: lang } }).catch(function () {});
    });
  });

  /* ---------- Connexion ---------- */
  var formEmail = $('[data-step="email"]'), formCode = $('[data-step="code"]'), loginErr = $('[data-login-error]'), sentTo = '';
  formEmail.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = formEmail.email.value.trim();
    loginErr.textContent = '';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { loginErr.textContent = L().errEmail; return; }
    var btn = formEmail.querySelector('button'); btn.disabled = true;
    api('code', { method: 'POST', body: { email: email, lang: lang } }).then(function () {
      sentTo = email; $('[data-sent-to]').textContent = email;
      formEmail.hidden = true; formCode.hidden = false; formCode.code.value = ''; formCode.code.focus();
    }).catch(function (ex) { loginErr.textContent = ex.message; }).then(function () { btn.disabled = false; });
  });
  formCode.addEventListener('submit', function (e) {
    e.preventDefault();
    loginErr.textContent = '';
    var btn = formCode.querySelector('button[type="submit"]'); btn.disabled = true;
    api('verify', { method: 'POST', body: { email: sentTo, code: formCode.code.value } }).then(load)
      .catch(function () { loginErr.textContent = L().errCode; }).then(function () { btn.disabled = false; });
  });
  formCode.code.addEventListener('input', function () {
    formCode.code.value = formCode.code.value.replace(/\D/g, '').slice(0, 6);
    if (formCode.code.value.length === 6) formCode.requestSubmit();
  });
  $('[data-back]').addEventListener('click', function () { formCode.hidden = true; formEmail.hidden = false; loginErr.textContent = ''; formEmail.email.focus(); });
  $('[data-logout]').addEventListener('click', function () { api('logout', { method: 'POST' }).then(function () { data = null; show('login'); }); });

  /* ---------- Tableau de bord ---------- */
  function load() {
    return api('me').then(function (d) {
      data = d;
      if (d.client.lang && T[d.client.lang] && !savedLang && d.client.lang !== lang) setLang(d.client.lang, false);
      render(); show('dash');
    }).catch(function (ex) { if (ex.status === 401) show('login'); else { show('login'); loginErr.textContent = ex.message; } });
  }

  function actionText(a) {
    var s = L().act;
    if (a.code === 'pay') return fill(s.pay, { number: a.number, amount: money(a.amount, a.currency), date: date(a.date) });
    if (a.code === 'renew') return fill(a.days < 0 ? s.renewLate : s.renew, { date: date(a.date), days: daysText(a.days) });
    if (a.code === 'domain') return fill(a.days < 0 ? s.domainLate : s.domain, { date: date(a.date), days: daysText(a.days) });
    return s[a.code] || '';
  }
  function actionButtons(a) {
    var b = L().btn;
    if (a.code === 'pay') return '<button type="button" class="button is-small" data-go-pay>' + esc(b.pay) + '</button>';
    if (a.code === 'renew' || a.code === 'domain') return '<button type="button" class="button is-small" data-renew="' + esc(a.service) + '">' + icon('i-refresh') + esc(b.renew) + '</button>';
    if (a.code === 'preview') return '<a class="button is-small is-secondary" href="' + esc(a.url) + '" target="_blank" rel="noopener">' + esc(b.preview) + icon('i-arrow') + '</a><button type="button" class="button is-small" data-approve="' + esc(a.service) + '">' + icon('i-check') + esc(b.approve) + '</button>';
    return '';
  }

  function render() {
    var d = data, l = L();
    $('[data-name]').textContent = d.client.name;
    $('[data-today]').textContent = new Date().toLocaleDateString(LOC[lang], { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    // 1. La réponse d'abord : faut-il agir ?
    var banner = $('[data-banner]');
    var worst = d.actions.length ? d.actions[0].level : 'ok';
    banner.className = 'status_banner is-' + worst;
    if (!d.actions.length) {
      banner.innerHTML = '<span class="status_icon">' + icon('i-check') + '</span><div><p class="status_title">' + esc(l.allGood) + '</p><p class="status_text">' + esc(l.allGoodP) + '</p></div>';
    } else {
      banner.innerHTML = '<span class="status_icon">' + icon(worst === 'info' ? 'i-info' : 'i-alert') + '</span><div class="status_body"><p class="status_title">' +
        esc(d.actions.length === 1 ? l.todo1 : fill(l.todo, { n: d.actions.length })) + '</p><ul class="status_list" role="list">' +
        d.actions.map(function (a) {
          return '<li class="status_item is-' + a.level + '"><span>' + esc(actionText(a)) + '</span><span class="status_btns">' + actionButtons(a) + '</span></li>';
        }).join('') + '</ul></div>';
    }

    // 2. Services : un bloc par site / abonnement
    var sv = $('[data-services]');
    if (!d.services.length) sv.innerHTML = '<p class="empty_card">' + esc(l.noService) + '</p>';
    else sv.innerHTML = d.services.map(function (s) { return serviceCard(s); }).join('');

    // 3. Factures
    var inv = $('[data-invoices]');
    if (!d.invoices.length) inv.innerHTML = '<p class="empty_text">' + esc(l.noInvoice) + '</p>';
    else inv.innerHTML = '<div class="table_wrap"><table class="invoice_table"><thead><tr><th>' + esc(l.th.number) + '</th><th>' + esc(l.th.label) + '</th><th>' + esc(l.th.date) + '</th><th>' + esc(l.th.due) + '</th><th class="is-num">' + esc(l.th.amount) + '</th><th>' + esc(l.th.status) + '</th></tr></thead><tbody>' +
      d.invoices.map(function (i) {
        return '<tr><td dir="ltr">' + esc(i.number) + '</td><td>' + esc(i.label) + '</td><td>' + date(i.issued_on) + '</td><td>' + date(i.due_on) + '</td><td class="is-num" dir="ltr">' + money(i.amount, i.currency) + '</td><td><span class="pill is-' + (i.status === 'paid' ? 'ok' : i.status === 'overdue' ? 'critical' : 'warning') + '">' + esc(l.st[i.status]) + '</span></td></tr>';
      }).join('') + '</tbody></table></div>';
    var unpaid = d.invoices.some(function (i) { return i.status !== 'paid'; });
    var bank = $('[data-bank]');
    bank.hidden = !unpaid;
    if (unpaid) {
      var b = d.bank || {};
      var waText = encodeURIComponent((lang === 'tr' ? 'Merhaba, fatura ödemesi hakkında bilgi almak istiyorum: ' : 'Hello, payment details for invoice: ') + d.invoices.filter(function (i) { return i.status !== 'paid'; }).map(function (i) { return i.number; }).join(', '));
      bank.innerHTML = '<p class="bank_title">' + esc(l.bankT) + '</p>' +
        (b.iban ? '<p class="bank_line">' + (b.holder ? esc(b.holder) + ' · ' : '') + (b.name ? esc(b.name) + ' · ' : '') + '<b dir="ltr">' + esc(b.iban) + '</b></p><p class="bank_hint">' + esc(l.bankRef) + '</p>' : '') +
        '<a class="text_link" href="https://wa.me/' + esc(d.whatsapp) + '?text=' + waText + '" target="_blank" rel="noopener">' + icon('i-chat') + esc(l.payWa) + '</a>';
    }

    // 4. Notifications + demandes récentes
    var no = $('[data-notices]');
    var notes = d.notices.map(function (n) {
      return '<li class="notice_item is-' + esc(n.level) + '"><p class="notice_title">' + esc(n.title) + '</p>' + (n.body ? '<p class="notice_body">' + esc(n.body) + '</p>' : '') + '<p class="notice_date">' + date(n.created_at) + '</p></li>';
    }).join('');
    var reqs = d.requests.length ? '<p class="mini_title">' + esc(l.reqs) + '</p><ul class="req_list" role="list">' + d.requests.map(function (q) {
      return '<li><span>' + esc(l.req[q.kind] || q.kind) + ' · ' + date(q.created_at) + '</span><span class="pill is-' + (q.status === 'open' ? 'warning' : 'ok') + '">' + esc(q.status === 'open' ? l.reqOpen : l.reqDone) + '</span></li>';
    }).join('') + '</ul>' : '';
    no.innerHTML = (notes ? '<ul class="notice_list" role="list">' + notes + '</ul>' : '<p class="empty_text">' + esc(l.noNotice) + '</p>') + reqs;

    // 5. Compte
    var f = $('[data-account]');
    if (document.activeElement && f.contains(document.activeElement)) return;
    f.name.value = d.client.name || ''; f.company.value = d.client.company || ''; f.phone.value = d.client.phone || ''; f.email.value = d.client.email || '';
    $('[data-wa]').href = 'https://wa.me/' + d.whatsapp;
  }

  function tile(ico, title, value, sub, status) {
    return '<div class="tile is-' + status + '"><p class="tile_title">' + icon(ico) + esc(title) + '</p><p class="tile_value">' + value + '</p>' + (sub ? '<p class="tile_sub">' + sub + '</p>' : '') + '</div>';
  }
  function tone(st) { return { ok: 'ok', active: 'ok', paid: 'ok', soon: 'warning', due: 'warning', pending: 'neutral', none: 'neutral', expired: 'critical', overdue: 'critical' }[st] || 'neutral'; }

  function serviceCard(s) {
    var l = L();
    var siteTone = { live: 'ok', preview: 'warning', building: 'neutral', suspended: 'critical' }[s.site.status] || 'neutral';
    var url = s.domain ? 'https://' + s.domain.replace(/^https?:\/\//, '') : null;
    var head = '<div class="service_head"><div><p class="service_domain" dir="ltr">' + esc(s.domain || s.name) + '</p><p class="service_meta">' +
      (s.plan ? esc(l.plan[s.plan] || s.plan) + ' · ' : '') + '<span class="pill is-' + siteTone + '">' + esc(l.site[s.site.status] || s.site.status) + '</span></p></div><div class="service_links">' +
      (url && s.site.status === 'live' ? '<a class="button is-small is-secondary" href="' + esc(url) + '" target="_blank" rel="noopener">' + esc(l.btn.open) + icon('i-arrow') + '</a>' : '') +
      (s.site.preview && s.site.status !== 'live' ? '<a class="button is-small is-secondary" href="' + esc(s.site.preview) + '" target="_blank" rel="noopener">' + esc(l.btn.preview) + icon('i-arrow') + '</a>' : '') +
      '</div></div>';
    var d = s.domainInfo, h = s.hosting, sub = s.subscription, pay = s.payment;
    var left = sub.days;
    var pct = left == null ? 0 : Math.max(0, Math.min(100, Math.round(left / 365 * 100)));
    var tiles =
      tile('i-globe', l.tile.domain, esc(l.st[d.status]), d.expires ? fill(l.until, { date: date(d.expires) }) : '', tone(d.status)) +
      tile('i-server', l.tile.hosting, esc(l.st[h.status]), h.expires ? fill(l.until, { date: date(h.expires) }) : '', tone(h.status)) +
      tile('i-lock', l.tile.ssl, esc(l.st[s.ssl.status] || s.ssl.status), s.ssl.expires ? fill(l.until, { date: date(s.ssl.expires) }) : '', tone(s.ssl.status)) +
      tile('i-layout', l.tile.sub, (sub.plan ? esc(l.plan[sub.plan] || sub.plan) : '—') + (sub.price ? ' <span class="tile_price" dir="ltr">' + money(sub.price, sub.currency) + ' ' + esc(l.perYear) + '</span>' : ''), sub.next ? fill(l.next, { date: date(sub.next) }) : '', 'neutral') +
      '<div class="tile is-' + tone(h.status) + '"><p class="tile_title">' + icon('i-calendar') + esc(l.tile.left) + '</p><p class="tile_value is-big">' + (left == null ? '—' : esc(left < 0 ? 0 : left)) + '<span class="tile_unit">' + (left == null ? '' : esc(fill(l.days, { n: '' }).trim())) + '</span></p><span class="meter"><span style="--w:' + pct + '%"></span></span></div>' +
      tile('i-card', l.tile.payment, esc(l.st[pay.status]), pay.status !== 'paid' && pay.amount ? '<span dir="ltr">' + money(pay.amount, sub.currency) + '</span>' + (pay.due ? ' · ' + date(pay.due) : '') : '', tone(pay.status));
    var acts = '<div class="service_actions">' +
      '<button type="button" class="button is-small is-secondary" data-renew="' + esc(s.id) + '">' + icon('i-refresh') + esc(l.btn.renew) + '</button>' +
      '<button type="button" class="button is-small is-secondary" data-upgrade="' + esc(s.id) + '">' + icon('i-swap') + esc(l.btn.upgrade) + '</button></div>';
    return '<article class="service_card">' + head + '<div class="tile_grid">' + tiles + '</div>' + acts + '</article>';
  }

  /* ---------- Actions ---------- */
  function request(kind, service, detail, okMsg) {
    return api('request', { method: 'POST', body: { kind: kind, service: service, detail: detail || '' } })
      .then(function () { toast(okMsg || L().sent); return load(); })
      .catch(function (ex) { toast(ex.message); });
  }
  document.addEventListener('click', function (e) {
    var t = e.target, el;
    if ((el = t.closest('[data-renew]'))) { if (window.confirm(L().confirmRenew)) request('renew', el.dataset.renew); }
    else if ((el = t.closest('[data-approve]'))) { if (window.confirm(L().confirmApprove)) request('approve', el.dataset.approve, '', L().approved); }
    else if ((el = t.closest('[data-upgrade]'))) openPlans(el.dataset.upgrade);
    else if (t.closest('[data-go-pay]')) { var box = $('[data-bank]'); if (box) box.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  });

  var dialog = $('[data-plan-dialog]'), planFor = null;
  function openPlans(id) {
    planFor = id;
    var s = data.services.filter(function (x) { return x.id === id; })[0] || {};
    var P = data.pricing.prices, l = L();
    var plans = [['vitrin', P.base], ['randevu', P.base + P.bookingCalendar], ['eticaret', P.base + P.shop], ['ozel', null]];
    $('[data-plan-options]').innerHTML = plans.map(function (p, i) {
      var cur = s.plan === p[0];
      return '<label class="plan_option' + (cur ? ' is-current' : '') + '"><input type="radio" name="plan" value="' + p[0] + '"' + (cur ? ' disabled' : '') + (!cur && i === (s.plan === 'vitrin' ? 1 : 0) ? ' checked' : '') + '><span class="plan_name">' + esc(l.plan[p[0]]) + '</span>' +
        (cur ? '<span class="pill is-neutral">' + esc(l.current) + '</span>' : (p[1] ? '<span class="plan_price" dir="ltr">' + money(p[1], 'TRY') + '</span>' : '')) + '</label>';
    }).join('');
    dialog.querySelector('[name="note"]').value = '';
    if (dialog.showModal) dialog.showModal(); else dialog.setAttribute('open', '');
  }
  dialog.addEventListener('close', function () {
    if (dialog.returnValue !== 'ok' || !planFor) return;
    var picked = dialog.querySelector('[name="plan"]:checked');
    if (!picked) return;
    var note = dialog.querySelector('[name="note"]').value.trim();
    request('upgrade', planFor, (L().plan[picked.value] || picked.value) + ' (' + picked.value + ')' + (note ? ' — ' + note : ''));
  });

  $('[data-account]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;
    api('account', { method: 'PATCH', body: { name: f.name.value, company: f.company.value, phone: f.phone.value, lang: lang } })
      .then(function () { toast(L().saved); f.querySelector('button').blur(); return load(); }).catch(function (ex) { toast(ex.message); });
  });
  $('[data-support]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target, msg = f.detail.value.trim();
    if (!msg) { f.detail.focus(); return; }
    request('support', data.services[0] ? data.services[0].id : '', msg).then(function () { f.detail.value = ''; });
  });

  /* ---------- Démarrage : lien de connexion (?t=…) ou session existante ---------- */
  setLang(lang, false);
  var params = new URLSearchParams(location.search);
  var token = params.get('t');
  if (token) {
    history.replaceState(null, '', location.pathname);
    api('link', { method: 'POST', body: { token: token } }).then(load).catch(function () { show('login'); loginErr.textContent = L().errLink; });
  } else {
    load();
  }
})();
