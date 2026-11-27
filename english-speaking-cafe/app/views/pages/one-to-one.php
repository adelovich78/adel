<?php
$heading = t('Your goals. Your pace.') . ' <span class="serif-italic">' . t('Your coach.') . '</span>';
$eyebrow = __('1-to-1 coaching');
$aside = t('A private session with a native coach, at the café or online. Job interview next week? Big presentation? Accent work? We build the session around you.');
$media = 'table';
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="grid-3">
      <?php foreach ([
        ['briefcase', 'Interview & career', 'Mock interviews, elevator pitch, salary negotiation — with honest feedback.'],
        ['mic', 'Presentations', 'Structure, storytelling, slides and delivery. Rehearse until it feels natural.'],
        ['waveform', 'Accent & clarity', 'Targeted pronunciation work on the sounds that matter for you.'],
        ['globe', 'Relocation', 'Life admin, small talk, culture codes — before you land.'],
        ['star', 'Exams', 'IELTS, TOEFL, Cambridge speaking tests: strategies and timed practice.'],
        ['smile', 'Confidence', 'For those who understand everything but freeze when it’s time to speak.'],
      ] as $f): ?>
      <div class="card" data-reveal><span class="feature_icon" style="margin-bottom:1.25rem"><?= icon($f[0]) ?></span><h3 class="heading-style-h4"><?= t($f[1]) ?></h3><p class="text-color-muted" style="margin:.5rem 0 0"><?= t($f[2]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div></div></div>
</section>
<section class="section_dark">
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="split_component">
      <div>
        <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Pricing') ?></div>
        <h2 class="heading-style-h2" data-split><?= money($p['price'] ?? 65) ?> <span class="serif-italic"><?= t('per hour.') ?></span></h2>
        <p class="text-size-large margin-top margin-small" style="opacity:.75" data-reveal><?= t('Book single sessions, or join Club Unlimited for a monthly 1-to-1 included. Packs and corporate rates available on request.') ?></p>
        <div class="button-group margin-top margin-medium" data-reveal><a class="button is-cream" href="<?= url('book?program=one-to-one') ?>" data-magnetic><?= t('Book a coaching') ?> <?= icon('arrow', 'button_arrow') ?></a><a class="button is-ghost-light" href="<?= url('contact') ?>"><?= t('Ask about packs') ?></a></div>
      </div>
      <div class="split_media" data-reveal="scale"><img src="<?= asset('img/photo-conversation.jpg') ?>" alt="<?= t('A conversation at the café') ?>" loading="lazy" data-parallax="-10"></div>
    </div>
  </div></div></div>
</section>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="section-header_component"><div class="max-width-large"><div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Choose your coach') ?></div><h2 class="heading-style-h2" data-split><?= t('Four accents.') ?> <span class="serif-italic"><?= t('One standard.') ?></span></h2></div></div>
    <div class="teachers_grid"><?php foreach ($teachers as $i => $t) include APP . '/views/partials/teacher-card.php'; ?></div>
  </div></div></div>
</section>
