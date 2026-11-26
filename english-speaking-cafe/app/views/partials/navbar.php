<?php use App\Core\Auth; $me = Auth::user(); ?>
<header class="navbar_component" data-navbar>
  <div class="padding-global" style="width:100%">
    <div class="container-large">
      <nav class="navbar_container" aria-label="Main">
        <a href="<?= url('/') ?>" class="navbar_logo-link" aria-label="XY Academy Speaking Café — home"><?= logo('horizontal') ?></a>
        <div class="navbar_menu" data-menu>
          <a href="<?= url('programs') ?>" class="navbar_link<?= active_nav('programs') ?>">Programs</a>
          <a href="<?= url('one-to-one') ?>" class="navbar_link<?= active_nav('one-to-one') ?>">1-to-1</a>
          <a href="<?= url('business') ?>" class="navbar_link<?= active_nav('business') ?>">Business</a>
          <a href="<?= url('events') ?>" class="navbar_link<?= active_nav('events') ?>">Events</a>
          <a href="<?= url('teachers') ?>" class="navbar_link<?= active_nav('teachers') ?>">Teachers</a>
          <a href="<?= url('memberships') ?>" class="navbar_link<?= active_nav('memberships') ?>">Memberships</a>
          <a href="<?= url('library') ?>" class="navbar_link<?= active_nav('library') ?>">Library</a>
          <a href="<?= url('about') ?>" class="navbar_link<?= active_nav('about') ?>">About</a>
          <a href="<?= url('contact') ?>" class="navbar_link<?= active_nav('contact') ?>">Contact</a>
        </div>
        <div class="navbar_button-wrapper">
          <button class="navbar_icon-button" type="button" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon', 'theme-icon-moon') . icon('sun', 'theme-icon-sun') ?></button>
          <?php if ($me): ?>
            <a href="<?= url('dashboard') ?>" class="button is-secondary is-small hide-tablet-nav"><?= e($me['first_name']) ?></a>
          <?php else: ?>
            <a href="<?= url('login') ?>" class="button is-secondary is-small hide-tablet-nav">Sign in</a>
          <?php endif; ?>
          <a href="<?= url('book') ?>" class="button is-small" data-magnetic>Book a seat <?= icon('arrow', 'button_arrow') ?></a>
          <button class="navbar_icon-button navbar_menu-button" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false"><?= icon('menu') ?></button>
        </div>
      </nav>
    </div>
  </div>
</header>
