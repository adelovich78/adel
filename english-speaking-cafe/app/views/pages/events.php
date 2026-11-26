<?php
$heading = t('After hours,') . ' <span class="serif-italic">' . t('in English.') . '</span>';
$eyebrow = __('Events');
$aside = t('Quiz nights, debates, movie nights, networking, book club and karaoke. Free for Flat White & Club members.');
require APP . '/views/partials/page-hero.php';
?>
<section class="section_marquee is-amber" aria-hidden="true"><div class="marquee_track" data-marquee><?php for ($k = 0; $k < 2; $k++): ?><div class="marquee_group"><span><?= t('Quiz night') ?></span><?= icon('sparkles', 'marquee_star') ?><span><em><?= t('Debate club') ?></em></span><?= icon('sparkles', 'marquee_star') ?><span><?= t('Movie night') ?></span><?= icon('sparkles', 'marquee_star') ?><span><em><?= t('Networking') ?></em></span><?= icon('sparkles', 'marquee_star') ?><span><?= t('Book club') ?></span><?= icon('sparkles', 'marquee_star') ?><span><em><?= t('Karaoke') ?></em></span><?= icon('sparkles', 'marquee_star') ?></div><?php endfor; ?></div></section>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="events_list">
      <?php $withDesc = true; foreach ($events as $ev) include APP . '/views/partials/event-row.php'; ?>
      <?php if (!$events): ?><div class="empty_state"><?= t('New events are brewing. Check back soon!') ?></div><?php endif; ?>
    </div>
  </div></div></div>
</section>
<?php require APP . '/views/partials/cta.php'; ?>
