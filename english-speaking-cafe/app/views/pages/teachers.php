<?php
$heading = t('Native teachers.') . ' <span class="serif-italic">' . t('Real people.') . '</span>';
$eyebrow = __('The team');
$aside = t('Trained conversation coaches from the UK, Ireland, Scotland, the US, Canada and Australia — chosen for their warmth as much as their expertise.');
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-medium">
    <div class="teachers_grid"><?php $full = true; foreach ($teachers as $i => $t) include APP . '/views/partials/teacher-card.php'; ?></div>
  </div></div></div>
</section>
<?php require APP . '/views/partials/cta.php'; ?>
