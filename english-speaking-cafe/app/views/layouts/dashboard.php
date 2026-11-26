<?php
use App\Core\Auth;
$me = Auth::user();
$role = $me['role'] ?? 'student';
$unread = $role === 'admin' ? (int)\App\Core\DB::val('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0') : 0;
$links = match ($role) {
    'admin' => [
        'Overview' => [['admin', 'chart', 'Dashboard'], ['admin/settings', 'settings', 'Settings']],
        'Café' => [['admin/sessions', 'calendar', 'Sessions'], ['admin/bookings', 'ticket', 'Bookings'], ['admin/programs', 'layers', 'Programs'], ['admin/teachers', 'mic', 'Teachers'], ['admin/events', 'sparkles', 'Events'], ['admin/registrations', 'users', 'Event guests']],
        'Business' => [['admin/users', 'users', 'Users'], ['admin/companies', 'building', 'Companies'], ['admin/plans', 'star', 'Plans'], ['admin/memberships', 'card', 'Memberships'], ['admin/payments', 'euro', 'Payments']],
        'Content' => [['admin/books', 'book', 'Library'], ['admin/messages', 'mail', 'Messages' . ($unread ? '|' . $unread : '')]],
    ],
    'teacher' => ['Studio' => [['dashboard', 'calendar', 'My schedule'], ['dashboard#students', 'users', 'My students'], ['library', 'book', 'Library']]],
    'parent' => ['Family' => [['dashboard', 'home', 'Overview'], ['book', 'calendar', 'Book a session'], ['events', 'sparkles', 'Events'], ['library', 'book', 'Library'], ['memberships', 'star', 'Memberships']]],
    'corporate' => ['Company' => [['dashboard', 'building', 'Overview'], ['book', 'calendar', 'Book for a team member'], ['business', 'briefcase', 'Programs'], ['library', 'book', 'Library']]],
    default => ['My café' => [['dashboard', 'home', 'Overview'], ['book', 'calendar', 'Book a seat'], ['events', 'sparkles', 'Events'], ['library', 'book', 'Library'], ['memberships', 'star', 'Memberships']]],
};
$cur = '/' . trim(\App\Core\Router::currentPath(), '/');
?>
<!doctype html>
<html lang="<?= lang() ?>">
<head>
<?php require APP . '/views/partials/head.php'; ?>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
</head>
<body class="is-dashboard">
<div class="dash_layout">
  <aside class="dash_sidebar" data-dash-sidebar>
    <a class="dash_logo" href="<?= url('/') ?>" aria-label="Back to website"><?= logo('horizontal') ?></a>
    <nav class="dash_nav" aria-label="Dashboard">
      <?php foreach ($links as $group => $items): ?>
        <div class="dash_nav-title"><?= e($role === 'admin' ? $group : __($group)) ?></div>
        <?php foreach ($items as [$href, $ic, $label]):
          [$label, $count] = array_pad(explode('|', $label), 2, null);
          $path = '/' . trim(strtok($href, '#'), '/');
          $active = $cur === $path || ($path !== '/admin' && $path !== '/dashboard' && str_starts_with($cur, $path . '/')); ?>
          <a class="dash_link<?= $active && !str_contains($href, '#') ? ' w--current' : '' ?>" href="<?= url($href) ?>"><?= icon($ic) ?><?= e($role === 'admin' ? $label : __($label)) ?><?php if ($count): ?><span class="badge"><?= (int)$count ?></span><?php endif; ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <div class="dash_nav-title"><?= t('Account') ?></div>
      <a class="dash_link" href="<?= url('/') ?>"><?= icon('coffee') ?><?= t('Website') ?></a>
      <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="dash_link" style="width:100%;background:none;border:0;cursor:pointer" type="submit"><?= icon('logout') ?><?= t('Sign out') ?></button></form>
    </nav>
    <div class="dash_user"><span class="dash_avatar"><?= e(initials(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''))) ?></span><span class="text-size-small"><b><?= e($me['first_name'] ?? '') ?></b><br><span style="opacity:.6"><?= t(ucfirst($role)) ?></span></span></div>
  </aside>
  <div class="dash_main">
    <header class="dash_topbar">
      <div style="display:flex;align-items:center;gap:.75rem">
        <button class="navbar_icon-button dash_menu-btn" type="button" data-dash-toggle aria-label="Menu"><?= icon('menu') ?></button>
        <span class="eyebrow"><?= e(($role ?? '') === 'admin' ? ($title ?? '') : __($title ?? '')) ?></span>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center">
        <div class="lang-switch"><?php foreach (LANGS as $code => $name): ?><a href="<?= e(lang_url($code)) ?>" class="lang-switch_link<?= lang() === $code ? ' is-active' : '' ?>"><?= strtoupper($code) ?></a><?php endforeach; ?></div>
        <button class="navbar_icon-button" type="button" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon', 'theme-icon-moon') . icon('sun', 'theme-icon-sun') ?></button>
        <?php if ($role !== 'admin' && $role !== 'teacher'): ?><a class="button is-small" href="<?= url('book') ?>"><?= t('Book a seat') ?> <?= icon('arrow', 'button_arrow') ?></a><?php endif; ?>
      </div>
    </header>
    <main class="dash_content" id="main"><?= $content ?></main>
  </div>
</div>
<?php require APP . '/views/partials/flash.php'; ?>
<script src="<?= asset('js/main.js') ?>" defer></script>
<script>
  // Menu latéral mobile
  document.addEventListener('click', function (e) {
    var s = document.querySelector('[data-dash-sidebar]');
    if (e.target.closest('[data-dash-toggle]')) s.classList.toggle('is-open');
    else if (s.classList.contains('is-open') && !e.target.closest('[data-dash-sidebar]')) s.classList.remove('is-open');
  });
</script>
</body>
</html>
