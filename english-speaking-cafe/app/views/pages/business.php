<?php
$heading = 'English that <span class="serif-italic">closes deals.</span>';
$eyebrow = 'Business English & corporate programs';
$aside = 'Meetings, presentations, negotiations and networking — practised in small groups or tailored programs for your team, with a corporate dashboard to follow bookings and progress.';
$media = 'lamps';
$dark = true;
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="stats_grid">
      <div class="stats_item" data-reveal><div class="stats_number"><span data-count="90">0</span><sup>min</sup></div><p class="text-size-small text-color-muted">focused sessions by sector & level</p></div>
      <div class="stats_item" data-reveal><div class="stats_number"><span data-count="5">0</span></div><p class="text-size-small text-color-muted">professionals max per native coach</p></div>
      <div class="stats_item" data-reveal><div class="stats_number"><span data-count="24">0</span><sup>h</sup></div><p class="text-size-small text-color-muted">to receive a tailored proposal</p></div>
      <div class="stats_item" data-reveal><div class="stats_number"><span data-count="100">0</span><sup>%</sup></div><p class="text-size-small text-color-muted">progress visible to HR in real time</p></div>
    </div>
  </div></div></div>
</section>
<section style="background:var(--color-surface-2)">
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="split_component">
      <div>
        <div class="eyebrow margin-bottom margin-small" data-reveal>For companies</div>
        <h2 class="heading-style-h2" data-split>Training your team will <span class="serif-italic">actually</span> look forward to.</h2>
        <div class="feature_list">
          <?php foreach ([
            ['users', 'Employee onboarding', 'Add employees in seconds — they receive their access by email.'],
            ['calendar', 'Book for your team', 'Reserve seats for any employee; we prevent overbooking automatically.'],
            ['chart', 'Corporate dashboard', 'Attendance, bookings and CEFR progress per employee.'],
            ['shield', 'Invoicing & budget', 'Monthly invoicing, seats included, transparent pricing from ' . money(setting('business_from_price', 149)) . '/employee/month.'],
          ] as $f): ?>
          <div class="feature_item" data-reveal><span class="feature_icon"><?= icon($f[0]) ?></span><div><b><?= e($f[1]) ?></b><p class="text-size-small text-color-muted" style="margin:.25rem 0 0"><?= e($f[2]) ?></p></div></div>
          <?php endforeach; ?>
        </div>
        <div class="button-group margin-top margin-medium" data-reveal><a class="button" href="<?= url('register?role=corporate') ?>" data-magnetic>Open a company account <?= icon('arrow', 'button_arrow') ?></a><a class="button is-secondary" href="<?= url('contact?topic=Business') ?>">Request a proposal</a></div>
      </div>
      <div class="split_media" data-reveal="scale"><img src="<?= asset('img/photo-bar.jpg') ?>" alt="The café bar" loading="lazy" data-parallax="-10"><span class="split_media-tag">Private room available for teams</span></div>
    </div>
  </div></div></div>
</section>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <h2 class="heading-style-h2 margin-bottom margin-large" data-split>Modules</h2>
    <div class="grid-4">
      <?php foreach (['Meetings that move', 'Presenting with impact', 'Negotiation', 'Emails & writing', 'Networking & small talk', 'Phone & video calls', 'Cross-cultural communication', 'Leadership language'] as $k => $m): ?>
      <div class="card" data-reveal><span class="text-style-serif text-color-amber" style="font-size:2rem">0<?= $k + 1 ?></span><h3 class="heading-style-h5" style="margin-top:1.5rem"><?= e($m) ?></h3></div>
      <?php endforeach; ?>
    </div>
  </div></div></div>
</section>
<?php require APP . '/views/partials/cta.php'; ?>
