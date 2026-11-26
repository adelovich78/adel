<?php /** @var array $p @var int $i */ ?>
<a href="<?= url('programs/' . $p['slug']) ?>" class="program-card_component is-<?= e($p['color']) ?>" data-cursor-label="<?= t('Explore') ?>">
  <div>
    <div class="program-card_top">
      <span class="program-card_index">0<?= (int)$i + 1 ?> — <?= e(category_label($p['category'])) ?></span>
      <span class="program-card_icon"><?= program_icon($p['category']) ?></span>
    </div>
    <h3 class="program-card_title"><?= e(tf($p, 'name')) ?></h3>
    <p style="opacity:.8"><?= e(tf($p, 'tagline')) ?></p>
  </div>
  <div>
    <div class="program-card_meta">
      <span><?= e($p['min_level']) ?>–<?= e($p['max_level']) ?></span>
      <span><?= t(':n min', ['n' => (int)$p['duration_min']]) ?></span>
      <span><?= (int)$p['capacity'] === 1 ? t('Private') : t('Max :n + teacher', ['n' => (int)$p['capacity']]) ?></span>
    </div>
    <div class="program-card_footer">
      <span class="program-card_price"><?= money($p['price']) ?><small><?= t('/ session') ?></small></span>
      <span class="program-card_go"><?= icon('arrow-up-right') ?></span>
    </div>
  </div>
</a>
