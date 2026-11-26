<?php
$heading = 'After hours, <span class="serif-italic">in English.</span>';
$eyebrow = 'Events';
$aside = 'Quiz nights, debates, movie nights, networking, book club and karaoke. Free for Flat White & Club members.';
require APP . '/views/partials/page-hero.php';
?>
<section class="section_marquee is-amber" aria-hidden="true"><div class="marquee_track" data-marquee><?php for ($k = 0; $k < 2; $k++): ?><div class="marquee_group"><span>Quiz night</span><?= icon('sparkles', 'marquee_star') ?><span><em>Debate club</em></span><?= icon('sparkles', 'marquee_star') ?><span>Movie night</span><?= icon('sparkles', 'marquee_star') ?><span><em>Networking</em></span><?= icon('sparkles', 'marquee_star') ?><span>Book club</span><?= icon('sparkles', 'marquee_star') ?><span><em>Karaoke</em></span><?= icon('sparkles', 'marquee_star') ?></div><?php endfor; ?></div></section>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="events_list">
      <?php $withDesc = true; foreach ($events as $ev) include APP . '/views/partials/event-row.php'; ?>
      <?php if (!$events): ?><div class="empty_state">New events are brewing. Check back soon!</div><?php endif; ?>
    </div>
  </div></div></div>
</section>
<?php require APP . '/views/partials/cta.php'; ?>
