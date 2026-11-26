<?php /** @var array $pl */ $per = __((string)(['month' => 'month', 'quarter' => 'quarter', 'year' => 'year'][$pl['period']] ?? 'month')); ?>
<div class="pricing-card_component<?= $pl['is_featured'] ? ' is-featured' : '' ?>" data-reveal>
  <?php if ($pl['is_featured']): ?><span class="pricing-card_ribbon"><?= t('Most loved') ?></span><?php endif; ?>
  <div class="eyebrow" style="<?= $pl['is_featured'] ? 'color:rgba(247,243,234,.75)' : '' ?>"><?= e($pl['name']) ?></div>
  <div class="pricing-card_price"><?= money($pl['price']) ?><small> / <?= e($per) ?></small></div>
  <p style="opacity:.75;margin:0"><?= e(tf($pl, 'tagline')) ?></p>
  <ul class="pricing-card_list">
    <?php foreach (array_filter(array_map('trim', explode("\n", tf($pl, 'perks')))) as $perk): ?><li><?= e($perk) ?></li><?php endforeach; ?>
  </ul>
  <form method="post" action="<?= url('memberships/' . (int)$pl['id'] . '/join') ?>">
    <?= csrf_field() ?>
    <button type="submit" class="button is-full <?= $pl['is_featured'] ? 'is-cream' : '' ?>"><?= t('Become a member') ?> <?= icon('arrow', 'button_arrow') ?></button>
  </form>
</div>
