<?php
$today = array_filter($sessions, fn($s) => date('Y-m-d', strtotime($s['starts_at'])) === date('Y-m-d'));
$week = array_filter($sessions, fn($s) => strtotime($s['starts_at']) > time() && strtotime($s['starts_at']) < strtotime('+7 days'));
$seated = array_sum(array_map(fn($s) => (int)$s['taken'], $week));
?>
<div class="panel_head" style="margin-bottom:2rem">
  <div><div class="eyebrow margin-bottom margin-xsmall">Teacher studio</div><h1 class="dash_title">Good to see you, <span class="serif-italic"><?= e(explode(' ', $t['display_name'])[0]) ?>.</span></h1></div>
</div>
<div class="dash_grid">
  <div class="panel kpi is-accent span-3"><span class="kpi_icon"><?= icon('clock') ?></span><div class="kpi_label">Today</div><div class="kpi_value"><?= count($today) ?></div><div class="text-size-small" style="opacity:.75">sessions</div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('calendar') ?></span><div class="kpi_label">Next 7 days</div><div class="kpi_value"><?= count($week) ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('users') ?></span><div class="kpi_label">Learners seated</div><div class="kpi_value"><?= $seated ?></div></div>
  <div class="panel kpi span-3"><span class="kpi_icon"><?= icon('star') ?></span><div class="kpi_label">My students</div><div class="kpi_value"><?= count($students) ?></div></div>

  <div class="panel span-12">
    <div class="panel_head"><h2 class="panel_title">Schedule & attendance</h2><span class="text-size-small text-color-muted">Mark attendance and leave a note after each session</span></div>
    <?php if (!$sessions): ?><div class="empty_mini">No sessions assigned yet.</div><?php endif; ?>
    <?php foreach ($sessions as $s): $past = strtotime($s['starts_at']) < time(); ?>
    <div class="list_item" style="align-items:flex-start">
      <div style="display:flex;gap:1rem;align-items:flex-start;flex:1 1 22rem;min-width:0">
        <span class="list_date"><small><?= date('D', strtotime($s['starts_at'])) ?></small><b><?= date('d', strtotime($s['starts_at'])) ?></b></span>
        <div style="flex:1;min-width:0">
          <b><?= e(fmt_time($s['starts_at'])) ?> · <?= e($s['title'] ?: $s['program_name']) ?></b> <span class="badge"><?= (int)$s['taken'] ?>/<?= (int)$s['capacity'] ?></span><?= $s['level'] ? ' <span class="level-pill">' . e($s['level']) . '</span>' : '' ?><?= $past ? ' <span class="badge">done</span>' : '' ?><br>
          <span class="text-size-small text-color-muted"><?= e($s['program_name']) ?><?= $s['room'] ? ' · ' . e($s['room']) : '' ?></span>
          <div class="roster">
            <?php foreach ($roster[$s['id']] ?? [] as $r): ?>
            <div class="roster_row">
              <span><b><?= e($r['first_name'] . ' ' . $r['last_name']) ?></b> <span class="level-pill"><?= e($r['cefr_level']) ?></span> <?= status_badge($r['status']) ?></span>
              <span class="row_actions">
                <?php if ($r['status'] !== 'waitlist'): ?>
                <form method="post" action="<?= url('dashboard/attendance/' . (int)$r['id']) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="attended"><button class="button is-tiny <?= $r['status'] === 'attended' ? '' : 'is-secondary' ?>" type="submit" title="Present"><?= icon('check') ?></button></form>
                <form method="post" action="<?= url('dashboard/attendance/' . (int)$r['id']) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="no_show"><button class="button is-tiny <?= $r['status'] === 'no_show' ? 'is-danger' : 'is-secondary' ?>" type="submit" title="Absent"><?= icon('x') ?></button></form>
                <details class="inline_form"><summary class="button is-tiny is-secondary">Note</summary>
                  <form method="post" action="<?= url('dashboard/note/' . (int)$r['id']) ?>" style="display:flex;gap:.5rem;margin-top:.5rem"><?= csrf_field() ?><input class="form_input" name="note" value="<?= e($r['teacher_note']) ?>" placeholder="What to work on next…"><button class="button is-tiny" type="submit">Save</button></form>
                </details>
                <?php else: ?><span class="text-size-tiny text-color-muted">#<?= (int)$r['waitlist_position'] ?> waiting</span><?php endif; ?>
              </span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($roster[$s['id']])): ?><span class="text-size-small text-color-muted">No learners yet.</span><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="panel span-12" id="students">
    <div class="panel_head"><h2 class="panel_title">My students & progress reports</h2></div>
    <div class="table_wrap"><table class="data_table">
      <thead><tr><th>Student</th><th>Level</th><th>Speak.</th><th>Listen.</th><th>Pron.</th><th>Vocab.</th><th>Conf.</th><th>Attendance</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($students as $st): $l = $st['progress']['latest']; ?>
        <tr>
          <td><b><?= e($st['first_name'] . ' ' . $st['last_name']) ?></b><br><span class="text-size-tiny text-color-muted">Last: <?= e(fmt_date($st['last_session'], 'j M')) ?></span></td>
          <td><span class="level-pill"><?= e($st['cefr_level']) ?></span></td>
          <?php foreach (\App\Controllers\DashboardController::SKILLS as $sk): ?><td><?= $l ? (int)$l[$sk] : '—' ?></td><?php endforeach; ?>
          <td><?= $st['progress']['attendance_rate'] !== null ? $st['progress']['attendance_rate'] . '%' : '—' ?></td>
          <td>
            <details class="inline_form"><summary class="button is-tiny">Report</summary>
              <form method="post" action="<?= url('dashboard/progress') ?>" class="admin_form" style="margin-top:.75rem;min-width:min(32rem,80vw)"><?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int)$st['id'] ?>">
                <div class="form_field"><label class="form_label">CEFR level</label><select class="form_input" name="cefr_level"><?php foreach (CEFR as $lv): ?><option <?= $lv === $st['cefr_level'] ? 'selected' : '' ?>><?= $lv ?></option><?php endforeach; ?></select></div>
                <?php foreach (\App\Controllers\DashboardController::SKILLS as $sk): ?>
                  <div class="form_field"><label class="form_label"><?= ucfirst($sk) ?> <output><?= $l ? (int)$l[$sk] : 50 ?></output></label><input type="range" min="0" max="100" name="<?= $sk ?>" value="<?= $l ? (int)$l[$sk] : 50 ?>" oninput="this.previousElementSibling.querySelector('output').value=this.value" style="width:100%;accent-color:var(--color-accent)"></div>
                <?php endforeach; ?>
                <div class="form_field is-wide"><label class="form_label">Notes for the learner (and parents)</label><textarea class="form_input" name="notes" style="min-height:5rem"></textarea></div>
                <div class="is-wide"><button class="button is-small" type="submit">Save report</button></div>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="9"><div class="empty_mini">Students appear here once they book your sessions.</div></td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>
