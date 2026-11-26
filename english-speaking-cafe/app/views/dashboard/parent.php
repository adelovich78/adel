<div class="panel_head" style="margin-bottom:2rem">
  <div><div class="eyebrow margin-bottom margin-xsmall"><?= t('Family dashboard') ?></div><h1 class="dash_title"><?= t('Hello :name,', ['name' => $u['first_name']]) ?> <span class="serif-italic"><?= t('here’s how they’re doing.') ?></span></h1></div>
</div>
<div class="dash_grid">
  <?php foreach ($children as $i => $c): $progress = $c['progress']; $compact = true; ?>
  <div class="panel span-6">
    <div class="panel_head">
      <div style="display:flex;gap:.75rem;align-items:center"><span class="quote_avatar mono-bg-<?= $i % 6 ?>"><?= e(initials($c['first_name'] . ' ' . $c['last_name'])) ?></span><div><h2 class="panel_title"><?= e($c['first_name']) ?></h2><span class="text-size-small text-color-muted"><?= $c['birth_year'] ? t(':n years', ['n' => date('Y') - (int)$c['birth_year']]) . ' · ' : '' ?><?= t('level :level', ['level' => $c['cefr_level']]) ?> · <?= t(':n sessions', ['n' => (int)$progress['attended']]) ?><?= $progress['attendance_rate'] !== null ? ' · ' . t(':n% attendance', ['n' => $progress['attendance_rate']]) : '' ?></span></div></div>
      <a class="button is-tiny" href="<?= url('book?level=' . e($c['cefr_level']) . '&goal=school') ?>"><?= t('Book for :name', ['name' => $c['first_name']]) ?></a>
    </div>
    <?php include APP . '/views/partials/progress.php'; ?>
    <?php if ($progress['latest'] && $progress['latest']['notes']): ?><p class="text-size-small card is-flat margin-top margin-small" style="margin-bottom:0">💬 <?= t('Teacher') ?>: “<?= e($progress['latest']['notes']) ?>”</p><?php endif; ?>
    <div class="margin-top margin-small"><?php $list = $c['upcoming']; $canCancel = true; $empty = 'No upcoming sessions.'; include APP . '/views/partials/booking-list.php'; ?></div>
  </div>
  <?php endforeach; ?>
  <div class="panel span-6">
    <div class="panel_head"><h2 class="panel_title"><?= t('Add a child') ?></h2></div>
    <form method="post" action="<?= url('dashboard/children') ?>" class="admin_form"><?= csrf_field() ?>
      <div class="form_field"><label class="form_label"><?= t('First name') ?></label><input class="form_input" name="first_name" required></div>
      <div class="form_field"><label class="form_label"><?= t('Last name') ?></label><input class="form_input" name="last_name" value="<?= e($u['last_name']) ?>"></div>
      <div class="form_field"><label class="form_label"><?= t('Birth year') ?></label><input class="form_input" type="number" name="birth_year" min="2005" max="<?= date('Y') ?>"></div>
      <div class="form_field"><label class="form_label"><?= t('Level') ?></label><select class="form_input" name="cefr_level"><?php foreach (CEFR as $l): ?><option><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="is-wide"><button class="button" type="submit"><?= icon('plus') ?> <?= t('Add to family') ?></button></div>
    </form>
  </div>
  <div class="panel span-12">
    <div class="panel_head"><h2 class="panel_title"><?= t('All family bookings') ?></h2></div>
    <?php $list = $bookings; $showLearner = true; $canCancel = true; $empty = 'No upcoming bookings.'; include APP . '/views/partials/booking-list.php'; ?>
  </div>
  <?php include APP . '/views/partials/account-panels.php'; ?>
</div>
