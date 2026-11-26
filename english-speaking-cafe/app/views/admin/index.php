<div class="panel_head" style="margin-bottom:2rem">
  <div><div class="eyebrow margin-bottom margin-xsmall">Admin</div><h1 class="dash_title">The café <span class="serif-italic">at a glance.</span></h1></div>
  <div class="button-group"><a class="button is-small" href="<?= url('admin/sessions/new') ?>"><?= icon('plus') ?> Session</a><a class="button is-small is-secondary" href="<?= url('admin/events/new') ?>"><?= icon('plus') ?> Event</a></div>
</div>
<div class="dash_grid">
  <div class="panel kpi is-accent span-3"><span class="kpi_icon"><?= icon('euro') ?></span><div class="kpi_label">Revenue this month</div><div class="kpi_value"><?= money($kpi['revenue_month']) ?></div><div class="text-size-small" style="opacity:.75"><?= money($kpi['pending_onsite']) ?> to collect at café</div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('ticket') ?></span><div class="kpi_label">Bookings · 7 days</div><div class="kpi_value"><?= (int)$kpi['bookings_week'] ?></div><div class="text-size-small text-color-muted"><?= (int)$kpi['waitlist'] ?> on waitlists</div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('chart') ?></span><div class="kpi_label">Fill rate · 7 days</div><div class="kpi_value"><?= (int)$kpi['fill'] ?>%</div><div class="skill_track" style="margin-top:.5rem"><div class="skill_fill" style="width:<?= (int)$kpi['fill'] ?>%"></div></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('star') ?></span><div class="kpi_label">Active members</div><div class="kpi_value"><?= (int)$kpi['members'] ?></div><div class="text-size-small text-color-muted"><?= (int)$kpi['learners'] ?> learners · <?= (int)$kpi['unread'] ?> unread msg</div></div>

  <div class="panel span-8">
    <div class="panel_head"><h2 class="panel_title">Next sessions</h2><a class="text-link text-size-small" href="<?= url('admin/sessions') ?>">Manage schedule</a></div>
    <div class="table_wrap"><table class="data_table">
      <thead><tr><th>When</th><th>Program</th><th>Teacher</th><th>Seats</th><th>Wait</th><th class="is-num">Price</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $s): ?>
        <tr><td><b><?= e(fmt_date($s['starts_at'], 'D j M')) ?></b> <?= e(fmt_time($s['starts_at'])) ?></td><td><?= e($s['program_name']) ?><?= $s['level'] ? ' <span class="level-pill">' . e($s['level']) . '</span>' : '' ?></td><td><?= e($s['teacher_name'] ?? '—') ?></td>
          <td><div class="seat_dots" style="margin:0"><?php for ($k = 0; $k < (int)$s['capacity']; $k++): ?><span class="seat_dot<?= $k >= (int)$s['taken'] ? ' is-free' : '' ?>"></span><?php endfor; ?></div></td>
          <td><?= (int)$s['waiting'] ? '<span class="badge is-amber">' . (int)$s['waiting'] . '</span>' : '—' ?></td><td class="is-num"><?= money($s['effective_price']) ?></td>
          <td><a class="button is-tiny is-secondary" href="<?= url('admin/sessions/' . (int)$s['id'] . '/edit') ?>">Open</a></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="panel span-4">
    <div class="panel_head"><h2 class="panel_title">Revenue · 10 weeks</h2></div>
    <?php $max = max(1, ...array_map(fn($w) => (float)$w['total'], $weeks ?: [['total' => 1]])); ?>
    <?php if (!$weeks): ?><div class="empty_mini">No paid transactions yet.</div><?php else: ?>
    <svg class="chart_svg" viewBox="0 0 300 170" role="img" aria-label="Weekly revenue">
      <?php $bw = 300 / max(1, count($weeks)); foreach ($weeks as $k => $w): $h = (float)$w['total'] / $max * 130; ?>
        <rect class="bar" x="<?= round($k * $bw + 4, 1) ?>" y="<?= round(140 - $h, 1) ?>" width="<?= round($bw - 8, 1) ?>" height="<?= round($h, 1) ?>" rx="4" style="animation-delay:<?= $k * 60 ?>ms"><title><?= money($w['total']) ?></title></rect>
        <text class="axis-label" x="<?= round($k * $bw + $bw / 2, 1) ?>" y="158" text-anchor="middle"><?= e(date('j/m', strtotime($w['d']))) ?></text>
      <?php endforeach; ?>
    </svg>
    <?php endif; ?>
    <div class="panel_head margin-top margin-small"><h3 class="heading-style-h6">Popular programs</h3></div>
    <?php $pm = max(1, ...array_map(fn($p) => (int)$p['c'], $popular ?: [['c' => 1]])); foreach ($popular as $p): ?>
      <div class="skill_bar" style="grid-template-columns:9rem 1fr 2rem"><span class="text-size-small"><?= e($p['name']) ?></span><span class="skill_track"><span class="skill_fill" style="display:block;width:<?= round((int)$p['c'] / $pm * 100) ?>%"></span></span><b class="text-size-small"><?= (int)$p['c'] ?></b></div>
    <?php endforeach; ?>
  </div>

  <div class="panel span-7">
    <div class="panel_head"><h2 class="panel_title">Recent payments</h2><a class="text-link text-size-small" href="<?= url('admin/payments') ?>">All payments</a></div>
    <div class="table_wrap"><table class="data_table">
      <?php foreach ($recentPayments as $pay): ?>
      <tr><td><?= e(fmt_date($pay['created_at'], 'j M H:i')) ?></td><td><?= e($pay['payer']) ?></td><td style="text-transform:capitalize"><?= e($pay['purpose']) ?> · <?= e($pay['method']) ?></td><td class="is-num"><b><?= money($pay['amount'], $pay['currency']) ?></b></td><td><?= status_badge($pay['status']) ?></td>
        <td><?php if ($pay['status'] === 'pending'): ?><form method="post" action="<?= url('admin/payments/' . (int)$pay['id'] . '/paid') ?>"><?= csrf_field() ?><button class="button is-tiny" type="submit">Mark paid</button></form><?php endif; ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <div class="panel span-5">
    <div class="panel_head"><h2 class="panel_title">Generate a recurring schedule</h2></div>
    <form method="post" action="<?= url('admin/sessions/generate') ?>"><?= csrf_field() ?>
      <div class="form_field"><label class="form_label">Program</label><select class="form_input" name="program_id" required><?php foreach ($programs as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> (<?= (int)$p['duration_min'] ?> min)</option><?php endforeach; ?></select></div>
      <div class="form_grid">
        <div class="form_field"><label class="form_label">Teacher</label><select class="form_input" name="teacher_id"><option value="">—</option><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['display_name']) ?></option><?php endforeach; ?></select></div>
        <div class="form_field"><label class="form_label">Level</label><select class="form_input" name="level"><option value="">Any</option><?php foreach (CEFR as $l): ?><option><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="form_field"><label class="form_label">Time</label><input class="form_input" type="time" name="time" value="18:00" required></div>
        <div class="form_field"><label class="form_label">Weeks</label><input class="form_input" type="number" name="weeks" value="4" min="1" max="26"></div>
        <div class="form_field"><label class="form_label">Capacity</label><input class="form_input" type="number" name="capacity" value="5" min="1" max="50"></div>
        <div class="form_field"><label class="form_label">Room</label><input class="form_input" name="room" placeholder="Green booth"></div>
      </div>
      <div class="form_field"><span class="form_label">Days</span><div style="display:flex;gap:.4rem;flex-wrap:wrap"><?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $k => $d): ?><label class="badge" style="cursor:pointer;padding:.45rem .7rem"><input type="checkbox" name="days[]" value="<?= $k + 1 ?>" style="accent-color:var(--color-accent)"> <?= $d ?></label><?php endforeach; ?></div></div>
      <button class="button is-small" type="submit">Create sessions</button>
    </form>
  </div>
</div>
