<?php /** @var array $t @var int $i */ ?>
<article class="teacher-card_component" data-reveal>
  <div class="teacher-card_visual mono-bg-<?= (int)$i % 6 ?>">
    <span class="teacher-card_flag"><?= e(__($t['accent'])) ?> · <?= e(explode(',', $t['origin'])[0]) ?></span>
    <?php if ($t['photo']): ?><img src="<?= e(media_url($t['photo'])) ?>" alt="<?= e($t['display_name']) ?>" loading="lazy">
    <?php else: ?><span class="teacher-card_monogram"><?= e(initials($t['display_name'])) ?></span><?php endif; ?>
  </div>
  <div class="teacher-card_content">
    <h3 class="heading-style-h4"><?= e($t['display_name']) ?></h3>
    <p class="text-size-small text-color-muted" style="margin:.35rem 0 0"><?= e(tf($t, 'headline')) ?></p>
    <div class="teacher-card_tags"><?php foreach (array_filter(array_map('trim', explode(',', (string)$t['specialties']))) as $s): ?><span><?= e(__($s)) ?></span><?php endforeach; ?></div>
    <?php if (!empty($full)): ?><p class="text-size-small" style="margin-top:1rem"><?= e(tf($t, 'bio')) ?></p><?php endif; ?>
    <div style="margin-top:1.25rem"><a class="text-link" href="<?= url('book?teacher=' . (int)$t['id']) ?>"><?= t('Book with :name', ['name' => explode(' ', $t['display_name'])[0]]) ?> <?= icon('arrow') ?></a></div>
  </div>
</article>
