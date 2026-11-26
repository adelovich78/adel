<?php /** Hero des pages intérieures : $eyebrow, $heading (HTML autorisé), $aside, $media (vidéo) */ ?>
<section class="section_page-hero<?= !empty($dark) ? ' is-dark' : '' ?>">
  <div class="page-hero_deco" aria-hidden="true"></div>
  <div class="padding-global">
    <div class="container-large">
      <div class="page-hero_component<?= empty($aside) ? ' is-single' : '' ?>">
        <div>
          <div class="eyebrow margin-bottom margin-small" data-reveal><?= e($eyebrow) ?></div>
          <h1 class="heading-style-h1" data-split><?= $heading ?></h1>
        </div>
        <?php if (!empty($aside)): ?><div class="page-hero_aside" data-reveal><?= $aside ?></div><?php endif; ?>
      </div>
    </div>
    <?php if (!empty($media)): ?>
    <div class="container-large">
      <div class="page-hero_media" data-cursor-label="Watch"><video autoplay muted loop playsinline preload="metadata" poster="<?= asset('img/poster-' . $media . '.jpg') ?>" data-parallax="-12"><source src="<?= url('assets/video/' . $media . '.mp4') ?>" type="video/mp4"></video></div>
    </div>
    <?php endif; ?>
  </div>
</section>
