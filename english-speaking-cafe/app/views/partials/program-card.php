<?php /** @var array $p @var int $i */ ?>
<a href="<?= url('programs/' . $p['slug']) ?>" class="program-card_component is-<?= e($p['color']) ?>" data-cursor-label="Explore">
  <div>
    <div class="program-card_top">
      <span class="program-card_index">0<?= (int)$i + 1 ?> — <?= e(category_label($p['category'])) ?></span>
      <span class="program-card_icon"><?= program_icon($p['category']) ?></span>
    </div>
    <h3 class="program-card_title"><?= e($p['name']) ?></h3>
    <p style="opacity:.8"><?= e($p['tagline']) ?></p>
  </div>
  <div>
    <div class="program-card_meta">
      <span><?= e($p['min_level']) ?>–<?= e($p['max_level']) ?></span>
      <span><?= (int)$p['duration_min'] ?> min</span>
      <span><?= (int)$p['capacity'] === 1 ? 'Private' : 'Max ' . (int)$p['capacity'] . ' + teacher' ?></span>
    </div>
    <div class="program-card_footer">
      <span class="program-card_price"><?= money($p['price']) ?><small>/ session</small></span>
      <span class="program-card_go"><?= icon('arrow-up-right') ?></span>
    </div>
  </div>
</a>
