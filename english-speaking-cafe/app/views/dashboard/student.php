<?php $att = $progress['attendance_rate']; ?>
<div class="panel_head" style="margin-bottom:2rem">
  <div><div class="eyebrow margin-bottom margin-xsmall"><?= t('Welcome back') ?></div><h1 class="dash_title"><?= t('Hi :name,', ['name' => $u['first_name']]) ?> <span class="serif-italic"><?= t('ready to talk?') ?></span></h1></div>
</div>
<div class="dash_grid">
  <div class="panel kpi is-accent span-3"><span class="kpi_icon"><?= icon('chart') ?></span><div class="kpi_label"><?= t('CEFR level') ?></div><div class="kpi_value"><?= e($u['cefr_level']) ?></div><div class="text-size-small" style="opacity:.75"><?= e(cefr_label($u['cefr_level'])) ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('calendar') ?></span><div class="kpi_label"><?= t('Upcoming') ?></div><div class="kpi_value"><?= count(array_filter($upcoming, fn($b) => $b['status'] !== 'waitlist')) ?></div><div class="text-size-small text-color-muted"><?= t(':n on waitlist', ['n' => count(array_filter($upcoming, fn($b) => $b['status'] === 'waitlist'))]) ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('check') ?></span><div class="kpi_label"><?= t('Sessions attended') ?></div><div class="kpi_value"><?= (int)$progress['attended'] ?></div><div class="text-size-small text-color-muted"><?= t('since you joined') ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('smile') ?></span><div class="kpi_label"><?= t('Attendance') ?></div><div class="kpi_value"><?= $att === null ? '—' : $att . '%' ?></div><div class="text-size-small text-color-muted"><?= t('show-up rate') ?></div></div>

  <div class="panel span-7">
    <div class="panel_head"><h2 class="panel_title"><?= t('My bookings') ?></h2><a class="button is-tiny" href="<?= url('book') ?>">+ <?= t('Book') ?></a></div>
    <?php $list = $upcoming; $canCancel = true; $empty = 'No upcoming sessions. Your seat is waiting!'; include APP . '/views/partials/booking-list.php'; ?>
    <?php if ($past): ?>
      <details class="margin-top margin-small"><summary class="text-size-small text-link" style="cursor:pointer"><?= t('Past sessions (:n)', ['n' => count($past)]) ?></summary>
        <?php $list = $past; $canCancel = false; include APP . '/views/partials/booking-list.php'; ?>
      </details>
    <?php endif; ?>
  </div>
  <div class="panel span-5">
    <div class="panel_head"><h2 class="panel_title"><?= t('For your level') ?></h2><a class="text-link text-size-small" href="<?= url('library') ?>"><?= t('Library') ?></a></div>
    <?php foreach ($books as $bk): ?>
      <div class="list_item"><span style="display:flex;gap:.75rem;align-items:center"><span style="width:2.2rem;height:3rem;border-radius:.2rem .5rem .5rem .2rem;background:<?= e($bk['cover_color']) ?>;flex-shrink:0"></span><span><b class="text-size-small"><?= e($bk['title']) ?></b><br><span class="text-size-tiny text-color-muted"><?= e(__($bk['category'])) ?> · <?= (int)$bk['pages'] ?> p.</span></span></span><a class="button is-tiny is-secondary" href="<?= url('library/download/' . (int)$bk['id']) ?>"><?= icon('download') ?></a></div>
    <?php endforeach; ?>
    <?php if (!$books): ?><div class="empty_mini"><?= t('New books for :level are coming soon.', ['level' => $u['cefr_level']]) ?></div><?php endif; ?>
  </div>

  <div class="panel span-12">
    <div class="panel_head"><h2 class="panel_title"><?= t('My progress') ?></h2><span class="text-size-small text-color-muted"><?= t('Speaking · Listening · Pronunciation · Vocabulary · Confidence') ?></span></div>
    <?php include APP . '/views/partials/progress.php'; ?>
    <?php if ($progress['latest'] && $progress['latest']['notes']): ?><p class="card is-flat margin-top margin-small" style="margin-bottom:0">💬 “<?= e($progress['latest']['notes']) ?>”</p><?php endif; ?>
  </div>
  <?php include APP . '/views/partials/account-panels.php'; ?>
</div>
