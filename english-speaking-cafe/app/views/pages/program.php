<?php
$heading = e(tf($p, 'name')) . ' <span class="serif-italic">' . e(category_label($p['category'])) . '</span>';
$eyebrow = tf($p, 'audience') ?: __('Program');
$aside = e(tf($p, 'tagline'));
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-medium">
    <div class="split_component" style="align-items:start">
      <div>
        <p class="text-size-xlarge" data-reveal><?= e(tf($p, 'description')) ?></p>
        <div class="feature_list">
          <?php foreach ([
            ['users', (int)$p['capacity'] === 1 ? __('Just you and your coach') : __('Maximum :n learners + 1 native teacher', ['n' => (int)$p['capacity']]), __('Everybody speaks, every session.')],
            ['clock', __(':n minutes of real conversation', ['n' => (int)$p['duration_min']]), __('Themed, structured, and genuinely fun.')],
            ['chart', __('Progress you can see'), __('Speaking, listening, pronunciation, vocabulary & confidence tracked in your dashboard.')],
            ['book', __('Library included'), __('Download the guides for your level before each table.')],
          ] as $f): ?>
          <div class="feature_item" data-reveal><span class="feature_icon"><?= icon($f[0]) ?></span><div><b><?= e($f[1]) ?></b><p class="text-size-small text-color-muted" style="margin:.25rem 0 0"><?= e($f[2]) ?></p></div></div>
          <?php endforeach; ?>
        </div>
      </div>
      <aside class="card program-hero_panel booking_summary" data-reveal>
        <dl class="info_list" style="margin:0">
          <div><dt><?= t('Levels') ?></dt><dd><?= e($p['min_level']) ?> → <?= e($p['max_level']) ?></dd></div>
          <div><dt><?= t('Duration') ?></dt><dd><?= t(':n min', ['n' => (int)$p['duration_min']]) ?></dd></div>
          <div><dt><?= t('Seats') ?></dt><dd><?= (int)$p['capacity'] === 1 ? t('Private') : t(':n + teacher', ['n' => (int)$p['capacity']]) ?></dd></div>
          <div><dt><?= t('Memberships') ?></dt><dd><?= $p['membership_eligible'] ? t('Included') : t('Pay per session') ?></dd></div>
        </dl>
        <div class="summary_total"><span><?= t('From') ?></span><span><?= money($p['price']) ?></span></div>
        <a class="button is-full margin-top margin-small" href="<?= url('book?program=' . e($p['slug'])) ?>" data-magnetic><?= t('Book this program') ?> <?= icon('arrow', 'button_arrow') ?></a>
      </aside>
    </div>
  </div></div></div>
</section>
<section style="background:var(--color-surface-2)">
  <div class="padding-global"><div class="container-large"><div class="padding-section-medium">
    <div class="section-header_component"><h2 class="heading-style-h3" data-split><?= t('Next tables') ?></h2><a class="text-link" href="<?= url('book?program=' . e($p['slug'])) ?>"><?= t('See full calendar') ?> <?= icon('arrow') ?></a></div>
    <?php if (!$sessions): ?><div class="empty_state"><?= t('No sessions scheduled yet — new dates are coming soon.') ?></div><?php endif; ?>
    <?php foreach ($sessions as $s): ?>
    <div class="session_mini" data-reveal>
      <div><b><?= e(fmt_date($s['starts_at'], 'l j M')) ?> · <?= e(fmt_time($s['starts_at'])) ?></b><br><span class="text-size-small text-color-muted"><?= e($s['title'] ?: $s['program_name']) ?><?= $s['teacher_name'] ? ' · ' . t('with :name', ['name' => $s['teacher_name']]) : '' ?><?= $s['level'] ? ' · ' . e($s['level']) : '' ?></span></div>
      <div style="display:flex;gap:1rem;align-items:center">
        <div class="seat_dots"><?php for ($k = 0; $k < (int)$s['capacity']; $k++): ?><span class="seat_dot<?= $k >= (int)$s['taken'] ? ' is-free' : '' ?>"></span><?php endfor; ?></div>
        <a class="button is-small <?= $s['seats_left'] ? '' : 'is-secondary' ?>" href="<?= url('book?program=' . e($p['slug']) . '&session=' . (int)$s['id']) ?>"><?= $s['seats_left'] ? t(':n left · Book', ['n' => $s['seats_left']]) : t('Join waitlist') ?></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div></div></div>
</section>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-large">
    <h2 class="heading-style-h3 margin-bottom margin-large" data-split><?= t('You might also love') ?></h2>
    <div class="programs_grid"><?php foreach ($others as $i => $p) include APP . '/views/partials/program-card.php'; ?></div>
  </div></div></div>
</section>
