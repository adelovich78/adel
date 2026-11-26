<!doctype html>
<html lang="<?= lang() ?>">
<head><?php require APP . '/views/partials/head.php'; ?></head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php if (($bodyClass ?? '') === 'is-home'): ?>
<div class="preloader_component" data-preloader aria-hidden="true">
  <?= logo('mark', 'preloader_logo') ?>
  <div class="preloader_caption">Brewing your café</div>
  <div class="preloader_count" data-preloader-count>0</div>
</div>
<?php endif; ?>
<div class="transition_panel" data-transition aria-hidden="true"><?= logo('mark') ?></div>
<div class="cursor_component" data-cursor aria-hidden="true"><div class="cursor_ring" data-cursor-ring></div><div class="cursor_dot" data-cursor-dot></div></div>
<div class="page-wrapper">
  <?php require APP . '/views/partials/navbar.php'; ?>
  <main class="main-wrapper" id="main">
    <?= $content ?>
  </main>
  <?php require APP . '/views/partials/footer.php'; ?>
</div>
<?php require APP . '/views/partials/flash.php'; ?>
<?php require APP . '/views/partials/scripts.php'; ?>
<?php foreach ($scripts ?? [] as $s): ?><script src="<?= asset($s) ?>" defer></script><?php endforeach; ?>
</body>
</html>
