<!doctype html>
<html lang="<?= lang() ?>">
<head><?php require APP . '/views/partials/head.php'; ?></head>
<body class="is-auth">
<div class="auth_layout">
  <aside class="auth_media">
    <video autoplay muted loop playsinline preload="metadata" poster="<?= asset('img/poster-people.jpg') ?>"><source src="<?= url('assets/video/people.mp4') ?>" type="video/mp4"></video>
    <div class="auth_media-content">
      <a href="<?= url('/') ?>" aria-label="Home" style="width:15rem;display:block"><?= logo('horizontal') ?></a>
      <div>
        <p class="heading-style-h2" style="max-width:30rem">Speak English. <span class="serif-italic">For real.</span></p>
        <p style="opacity:.75;max-width:26rem;margin-top:1rem"><?= t('Tables of five. Native teachers. Great coffee. Your English, finally out loud.') ?></p>
      </div>
    </div>
  </aside>
  <div class="auth_form-wrap">
    <div class="auth_form">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem">
        <a href="<?= url('/') ?>" class="text-link"><?= icon('arrow', '') ?> <?= t('Back to the café') ?></a>
        <div style="display:flex;gap:.5rem;align-items:center"><div class="lang-switch"><?php foreach (LANGS as $code => $name): ?><a href="<?= e(lang_url($code)) ?>" class="lang-switch_link<?= lang() === $code ? ' is-active' : '' ?>"><?= strtoupper($code) ?></a><?php endforeach; ?></div><button class="navbar_icon-button" type="button" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon', 'theme-icon-moon') . icon('sun', 'theme-icon-sun') ?></button></div>
      </div>
      <?= $content ?>
    </div>
  </div>
</div>
<?php require APP . '/views/partials/flash.php'; ?>
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
