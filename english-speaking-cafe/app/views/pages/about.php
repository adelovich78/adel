<?php
$heading = t('We built the place we') . ' <span class="serif-italic">' . t('wished existed.') . '</span>';
$eyebrow = __('Our story');
$aside = t('XY Academy started with one table, five strangers and a pot of coffee. Today it’s a boutique café-club where English finally becomes something you speak — not something you study.');
$media = 'hero';
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-medium"><div class="padding-section-large">
    <p class="manifesto_text" data-scrub-text><?= t('Classrooms teach you') ?> <span class="is-accent"><?= t('about') ?></span> <?= t('English. Cafés let you') ?> <span class="is-accent"><?= t('live') ?></span> <?= t('it. We swapped whiteboards for warm lights, textbooks for real topics, and rows of desks for tables of five — so every voice gets heard.') ?></p>
  </div></div></div>
</section>
<section style="background:var(--color-surface-2)">
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="grid-3">
      <?php foreach ([['Engage', 'coffee', 'Every session is a conversation, not a lecture. You speak more in one hour here than in a week of classes.'], ['Learn', 'book', 'Native teachers, CEFR-based progress tracking and a library that grows with your level.'], ['Connect', 'globe', 'Meet people from dozens of countries at tables, quiz nights and networking events.']] as $v): ?>
      <div class="card" data-reveal><span class="feature_icon" style="margin-bottom:1.5rem"><?= icon($v[1]) ?></span><h3 class="heading-style-h3"><?= t($v[0]) ?></h3><p class="text-color-muted" style="margin:.75rem 0 0"><?= t($v[2]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div></div></div>
</section>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="split_component is-reverse">
      <div class="split_media" data-reveal="scale"><img src="<?= asset('img/photo-lamps.jpg') ?>" alt="<?= t('Warm lights in the café') ?>" loading="lazy" data-parallax="-10"></div>
      <div>
        <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Tomorrow') ?></div>
        <h2 class="heading-style-h2" data-split><?= t('From café tables to') ?> <span class="serif-italic"><?= t('your pocket.') ?></span></h2>
        <p class="text-size-large text-color-muted margin-top margin-small" data-reveal><?= t('We’re building AI-powered pronunciation feedback, speaking practice between sessions, a mobile app and online lessons — all connected to the progress your teachers track at the café.') ?></p>
      </div>
    </div>
  </div></div></div>
</section>
<?php require APP . '/views/partials/cta.php'; ?>
