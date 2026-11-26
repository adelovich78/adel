<?php use App\Core\Auth; $me = Auth::user(); ?>
<header class="navbar_component" data-navbar>
  <div class="padding-global" style="width:100%">
    <div class="container-large">
      <nav class="navbar_container" aria-label="Main">
        <a href="<?= url('/') ?>" class="navbar_logo-link" aria-label="XY Academy Speaking Café"><?= logo('horizontal') ?></a>
        <div class="navbar_menu" data-menu>
          <a href="<?= url('programs') ?>" class="navbar_link<?= active_nav('programs') ?>"><?= t('Programs') ?></a>
          <a href="<?= url('one-to-one') ?>" class="navbar_link<?= active_nav('one-to-one') ?>"><?= t('1-to-1') ?></a>
          <a href="<?= url('business') ?>" class="navbar_link<?= active_nav('business') ?>"><?= t('Business') ?></a>
          <a href="<?= url('events') ?>" class="navbar_link<?= active_nav('events') ?>"><?= t('Events') ?></a>
          <a href="<?= url('teachers') ?>" class="navbar_link<?= active_nav('teachers') ?>"><?= t('Teachers') ?></a>
          <a href="<?= url('memberships') ?>" class="navbar_link<?= active_nav('memberships') ?>"><?= t('Memberships') ?></a>
          <a href="<?= url('library') ?>" class="navbar_link<?= active_nav('library') ?>"><?= t('Library') ?></a>
          <a href="<?= url('about') ?>" class="navbar_link<?= active_nav('about') ?>"><?= t('About') ?></a>
          <a href="<?= url('contact') ?>" class="navbar_link<?= active_nav('contact') ?>"><?= t('Contact') ?></a>
        </div>
        <div class="navbar_button-wrapper">
          <div class="lang-switch" role="group" aria-label="Language">
            <?php foreach (LANGS as $code => $name): ?><a href="<?= e(lang_url($code)) ?>" class="lang-switch_link<?= lang() === $code ? ' is-active' : '' ?>" hreflang="<?= $code ?>" lang="<?= $code ?>" title="<?= e($name) ?>" data-no-transition><?= strtoupper($code) ?></a><?php endforeach; ?>
          </div>
          <button class="navbar_icon-button" type="button" data-theme-toggle aria-label="<?= t('Toggle dark mode') ?>"><?= icon('moon', 'theme-icon-moon') . icon('sun', 'theme-icon-sun') ?></button>
          <?php if ($me): ?>
            <a href="<?= url('dashboard') ?>" class="button is-secondary is-small hide-tablet-nav"><?= e($me['first_name']) ?></a>
          <?php else: ?>
            <a href="<?= url('login') ?>" class="button is-secondary is-small hide-tablet-nav"><?= t('Sign in') ?></a>
          <?php endif; ?>
          <a href="<?= url('book') ?>" class="button is-small" data-magnetic><?= t('Book a seat') ?> <?= icon('arrow', 'button_arrow') ?></a>
          <button class="navbar_icon-button navbar_menu-button" type="button" data-menu-toggle aria-label="<?= t('Open menu') ?>" aria-expanded="false"><?= icon('menu') ?></button>
        </div>
      </nav>
    </div>
  </div>
</header>
