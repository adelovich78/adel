<?php /** <head> commun : thème appliqué avant rendu pour éviter le flash */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(($title ?? '') ? __($title) . ' — XY Academy Speaking Café' : 'XY Academy — English Speaking Café') ?></title>
<meta name="description" content="<?= e($description ?? 'XY Academy Speaking Café — a premium café-style English speaking club. Practise real English with native teachers in tables of five, 1-to-1 coaching, business English and events.') ?>">
<meta property="og:title" content="<?= e($title ?? 'Speak English. For real.') ?> — XY Academy Speaking Café">
<meta property="og:image" content="<?= e(full_url('assets/img/hero-cafe.jpg')) ?>">
<meta name="theme-color" content="#0f4a37">
<link rel="icon" type="image/svg+xml" href="<?= asset('logo/favicon.svg') ?>">
<link rel="apple-touch-icon" href="<?= asset('logo/apple-touch-icon.png') ?>">
<link rel="preload" href="<?= url('assets/fonts/playfair.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= url('assets/fonts/dmsans.woff2') ?>" as="font" type="font/woff2" crossorigin>
<script>
  // Thème mémorisé + détection JS (avant peinture)
  (function () {
    var d = document.documentElement; d.classList.add('js');
    try { var t = localStorage.getItem('xy-theme'); if (t === 'dark' || t === 'light') d.setAttribute('data-theme', t); } catch (e) {}
    try { if (sessionStorage.getItem('xy-loaded')) d.classList.add('is-loaded'); } catch (e) { d.classList.add('is-loaded'); }
  })();
</script>
<script>window.XY_I18N = <?= json_encode(['title' => __('Are you sure?'), 'ok' => __('Yes, continue'), 'cancel' => __('Keep it')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
