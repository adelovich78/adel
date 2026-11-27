<?php
$heading = t('Programs made for') . ' <span class="serif-italic">' . t('talking.') . '</span>';
$eyebrow = __('The menu');
$aside = t('Every format puts speaking first: max five learners per native teacher, themes that matter, and a warm café setting. Prices, levels and schedules are always up to date.');
$media = 'programs';
require APP . '/views/partials/page-hero.php';
$canDo = ['I can introduce myself and order a coffee.', 'I can talk about my day and my plans.', 'I can share opinions and tell stories.', 'I can debate and work in English.', 'I can express nuance and lead discussions.', 'I speak with precision and ease.'];
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="programs_grid" data-books>
      <?php foreach ($programs as $i => $p) include APP . '/views/partials/program-card.php'; ?>
    </div>
  </div></div></div>
</section>
<section style="background:var(--color-surface-2)">
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <div class="section-header_component"><div class="max-width-large"><div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Find your level') ?></div><h2 class="heading-style-h2" data-split><?= t('From A1 to C2,') ?> <span class="serif-italic"><?= t('there’s a table for you.') ?></span></h2></div><a class="button is-secondary" href="<?= url('level-test') ?>" data-reveal><?= t('Take the level test') ?> <?= icon('arrow', 'button_arrow') ?></a></div>
    <div class="grid-3">
      <?php foreach (CEFR as $k => $l): ?>
      <a class="card" href="<?= url('book?level=' . $l) ?>" data-reveal style="display:flex;gap:1.25rem;align-items:center">
        <span class="choice_level text-color-amber"><?= $l ?></span>
        <span><b><?= e(cefr_label($l)) ?></b><br><span class="text-size-small text-color-muted"><?= t($canDo[$k]) ?></span></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div></div></div>
</section>
<?php require APP . '/views/partials/cta.php'; ?>
