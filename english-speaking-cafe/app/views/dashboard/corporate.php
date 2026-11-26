<?php
$total = count($employees);
$att = array_sum(array_column($employees, 'attended_count'));
$ns = array_sum(array_column($employees, 'noshow_count'));
$upcomingCount = count(array_filter($bookings, fn($b) => strtotime($b['starts_at']) > time() && in_array($b['status'], ['confirmed', 'pending'], true)));
?>
<div class="panel_head" style="margin-bottom:2rem">
  <div><div class="eyebrow margin-bottom margin-xsmall">Company dashboard</div><h1 class="dash_title"><?= e($company['name'] ?? 'Your company') ?> <span class="serif-italic">speaks English.</span></h1></div>
</div>
<div class="dash_grid">
  <div class="panel kpi is-accent span-3"><span class="kpi_icon"><?= icon('users') ?></span><div class="kpi_label">Employees</div><div class="kpi_value"><?= $total ?></div><div class="text-size-small" style="opacity:.75"><?= (int)($company['seats_included'] ?? 0) ?> seats included</div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('calendar') ?></span><div class="kpi_label">Upcoming sessions</div><div class="kpi_value"><?= $upcomingCount ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('check') ?></span><div class="kpi_label">Attended</div><div class="kpi_value"><?= $att ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('chart') ?></span><div class="kpi_label">Attendance rate</div><div class="kpi_value"><?= ($att + $ns) ? round($att / ($att + $ns) * 100) . '%' : '—' ?></div></div>

  <div class="panel span-12">
    <div class="panel_head"><h2 class="panel_title">Employees</h2><a class="button is-tiny" href="<?= url('book?program=business-english') ?>">Book for an employee</a></div>
    <div class="table_wrap"><table class="data_table">
      <thead><tr><th>Name</th><th>Email</th><th>Level</th><th>Speaking</th><th>Confidence</th><th class="is-num">Sessions</th><th class="is-num">Attended</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($employees as $em): $l = $em['progress']['latest']; ?>
        <tr><td><b><?= e($em['first_name'] . ' ' . $em['last_name']) ?></b></td><td class="text-color-muted"><?= e($em['email']) ?></td><td><span class="level-pill"><?= e($em['cefr_level']) ?></span></td>
          <td><?= $l ? '<span class="skill_track" style="display:inline-block;width:5rem;vertical-align:middle"><span class="skill_fill" style="display:block;width:' . (int)$l['speaking'] . '%"></span></span> ' . (int)$l['speaking'] : '—' ?></td>
          <td><?= $l ? (int)$l['confidence'] : '—' ?></td><td class="is-num"><?= (int)$em['sessions_count'] ?></td><td class="is-num"><?= (int)$em['attended_count'] ?></td>
          <td><a class="button is-tiny is-secondary" href="<?= url('book?level=' . e($em['cefr_level']) . '&goal=career') ?>">Book</a></td></tr>
      <?php endforeach; ?>
      <?php if (!$employees): ?><tr><td colspan="8"><div class="empty_mini">Add your first employee below.</div></td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="panel span-5">
    <div class="panel_head"><h2 class="panel_title">Add an employee</h2></div>
    <form method="post" action="<?= url('dashboard/employees') ?>"><?= csrf_field() ?>
      <div class="form_grid"><div class="form_field"><label class="form_label">First name</label><input class="form_input" name="first_name" required></div><div class="form_field"><label class="form_label">Last name</label><input class="form_input" name="last_name"></div></div>
      <div class="form_field"><label class="form_label">Work email</label><input class="form_input" type="email" name="email" required></div>
      <div class="form_field"><label class="form_label">Estimated level</label><select class="form_input" name="cefr_level"><?php foreach (CEFR as $l): ?><option <?= $l === 'B1' ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <button class="button" type="submit"><?= icon('plus') ?> Add & send invitation</button>
    </form>
  </div>
  <div class="panel span-7">
    <div class="panel_head"><h2 class="panel_title">Team bookings</h2></div>
    <?php $list = array_slice($bookings, 0, 12); $showLearner = true; $canCancel = true; $empty = 'No bookings yet.'; include APP . '/views/partials/booking-list.php'; ?>
  </div>
  <?php include APP . '/views/partials/account-panels.php'; ?>
</div>
