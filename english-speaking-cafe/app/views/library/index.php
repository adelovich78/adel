<?php
use App\Core\Auth;
$me = Auth::user();
$heading = t('Your library,') . ' <span class="serif-italic">' . t('level by level.') . '</span>';
$eyebrow = __('Library');
$aside = t('Café dialogues, phrase books, graded readers, business and exam guides — download the books that match your CEFR level. New shelves unlock as you progress.');
require APP . '/views/partials/page-hero.php';
$grouped = [];
foreach ($books as $b) $grouped[$b['level']][] = $b;
$maxIdx = $maxLevel ? cefr_index($maxLevel) : -1;
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-small">
    <div class="library_banner" data-reveal>
      <div class="cefr_ladder" aria-hidden="true"><?php foreach (CEFR as $k => $l): ?><span class="<?= $k <= $maxIdx ? 'is-on' : '' ?>" style="height:<?= 1.4 + $k * .55 ?>rem"><?= $l ?></span><?php endforeach; ?></div>
      <div>
        <?php if ($me): ?>
          <b><?= t('Your shelves: A1 → :level', ['level' => $maxLevel]) ?></b><br><span class="text-size-small text-color-muted"><?= t('Books above your level are visible but locked. Your teacher updates your level after each progress report.') ?></span>
        <?php else: ?>
          <b><?= t('Sign in to download') ?></b><br><span class="text-size-small text-color-muted"><?= t('Every learner gets free access to the books for their level and below.') ?></span>
        <?php endif; ?>
      </div>
      <?php if (!$me): ?><a class="button is-small" href="<?= url('login') ?>"><?= t('Sign in') ?></a><?php else: ?><a class="button is-small is-secondary" href="<?= url('dashboard') ?>"><?= t('My progress') ?></a><?php endif; ?>
    </div>
    <div class="test_cta margin-bottom margin-large" data-reveal><span class="feature_icon"><?= icon('quiz') ?></span><div><b><?= t('Not sure of your level?') ?></b><br><span class="text-size-small text-color-muted"><?= t('Take our free 24-question test and find the right shelf in 8 minutes.') ?></span></div><a class="button is-small" href="<?= url('level-test') ?>"><?= t('Take the level test') ?></a></div>
    <div class="library_filters" role="tablist">
      <a class="library_filter<?= !$filter ? ' is-active' : '' ?>" href="<?= url('library') ?>"><?= t('All levels') ?></a>
      <?php foreach (CEFR as $l): ?><a class="library_filter<?= $filter === $l ? ' is-active' : '' ?>" href="<?= url('library?level=' . $l) ?>"><?= $l ?><small><?= (int)($counts[$l] ?? 0) ?></small></a><?php endforeach; ?>
    </div>
    <?php if (!$books): ?><div class="empty_state"><?= t('No books on this shelf yet.') ?></div><?php endif; ?>
    <?php foreach ($grouped as $level => $list): $locked = cefr_index($level) > $maxIdx; ?>
    <div class="library_level">
      <div class="library_level-head"><span class="level-pill"><?= e($level) ?></span><h2 class="heading-style-h4"><?= e(cefr_label($level)) ?></h2><?php if ($me && $locked): ?><span class="badge"><?= icon('lock') ?> <?= t('Unlocks at :level', ['level' => $level]) ?></span><?php endif; ?></div>
      <div class="library_grid" data-books>
        <?php foreach ($list as $b): ?>
        <article class="book_component<?= ($me && $locked) ? ' is-locked' : '' ?>">
          <div class="book_cover" style="background:<?= e($b['cover_color']) ?>">
            <?php if ($b['cover_image']): ?><img src="<?= e(media_url($b['cover_image'])) ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover"><?php endif; ?>
            <span class="book_level"><?= e($b['level']) ?></span>
            <span class="book_title"><?= e($b['title']) ?></span>
            <span class="book_brand">XY Academy · <?= e(__($b['category'])) ?></span>
            <?php if ($me && $locked): ?><span class="book_lock"><span><?= icon('lock') ?> <?= t('Level :level', ['level' => $b['level']]) ?></span></span><?php endif; ?>
          </div>
          <div class="book_info">
            <p class="text-size-small text-color-muted" style="margin:0"><?= e(__($b['category'])) ?> · <?= t(':n pages', ['n' => (int)$b['pages']]) ?> · <?= t(':n downloads', ['n' => (int)$b['downloads']]) ?></p>
            <div class="book_actions">
              <?php if (!$me): ?><a class="button is-tiny is-secondary" href="<?= url('login') ?>"><?= icon('lock') ?> <?= t('Sign in') ?></a>
              <?php elseif ($locked): ?><span class="text-size-tiny text-color-muted"><?= t('Keep speaking to unlock') ?></span>
              <?php else: ?><a class="button is-tiny" href="<?= url('library/download/' . (int)$b['id']) ?>" download><?= icon('download') ?> <?= t('Download') ?></a><?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div></div></div>
</section>
