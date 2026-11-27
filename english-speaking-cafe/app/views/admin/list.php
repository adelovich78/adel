<?php
/** Liste générique d'entité admin */
$fmt = function (string $col, $v, array $row) use ($e) {
    if ($v === null || $v === '') return '<span class="text-color-muted">—</span>';
    if (in_array($col, ['is_active', 'is_published', 'is_read', 'includes_events', 'membership_eligible'], true)) return $v ? '<span class="badge is-green">Yes</span>' : '<span class="badge">No</span>';
    if (in_array($col, ['status'], true)) return status_badge((string)$v);
    if (in_array($col, ['price', 'amount', 'hourly_rate'], true)) return '<b>' . money($v) . '</b>';
    if (in_array($col, ['starts_at', 'created_at'], true)) return e(fmt_date((string)$v, 'D j M Y · H:i'));
    if (in_array($col, ['cefr_level', 'level', 'min_level', 'max_level'], true)) return '<span class="level-pill">' . e($v) . '</span>';
    if ($col === 'category') return e(category_label((string)$v));
    if ($col === 'role') return '<span class="badge" style="text-transform:capitalize">' . e($v) . '</span>';
    if ($col === 'credits') return (int)$v === 0 ? '∞' : (int)$v;
    return e(mb_strimwidth((string)$v, 0, 60, '…'));
};
$qs = function (array $extra) { return '?' . http_build_query(array_merge($_GET, $extra)); };
?>
<div class="panel_head" style="margin-bottom:1.5rem">
  <div><div class="eyebrow margin-bottom margin-xsmall">Admin · <?= (int)$total ?> records</div><h1 class="dash_title"><?= e($e['label']) ?></h1></div>
  <?php if (empty($e['no_create'])): ?><a class="button is-small" href="<?= url('admin/' . $e['key'] . '/new') ?>"><?= icon('plus') ?> New <?= e($e['singular']) ?></a><?php endif; ?>
</div>
<div class="panel">
  <form class="filter_bar" method="get">
    <input class="form_input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search…">
    <?php foreach ($e['filter'] ?? [] as $col => $vals): ?>
      <select class="form_input" name="<?= e($col) ?>" onchange="this.form.submit()"><option value="">All <?= e(str_replace('_', ' ', $col)) ?></option><?php foreach ($vals as $v): ?><option <?= input($col) === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <?php endforeach; ?>
    <button class="button is-small is-secondary" type="submit">Filter</button>
  </form>
  <div class="table_wrap"><table class="data_table">
    <thead><tr><?php foreach ($e['list'] as $col => $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <?php foreach ($e['list'] as $col => $label): ?><td><?= $fmt($col, $row[$col] ?? null, $row) ?></td><?php endforeach; ?>
        <td><div class="row_actions">
          <?php if ($e['key'] === 'payments' && $row['status'] === 'pending'): ?><form method="post" action="<?= url('admin/payments/' . (int)$row['id'] . '/paid') ?>"><?= csrf_field() ?><button class="button is-tiny" type="submit">Mark paid</button></form><?php endif; ?>
          <?php if ($e['key'] === 'bookings'): ?>
            <?php foreach (['attended' => 'Present', 'no_show' => 'Absent', 'confirmed' => 'Confirm'] as $st => $lbl): if ($row['status'] === $st) continue; ?>
              <form method="post" action="<?= url('admin/bookings/' . (int)$row['id'] . '/status') ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>"><button class="button is-tiny is-secondary" type="submit"><?= $lbl ?></button></form>
            <?php endforeach; ?>
          <?php endif; ?>
          <a class="button is-tiny is-secondary" href="<?= url('admin/' . $e['key'] . '/' . (int)$row['id'] . '/edit') ?>"><?= $e['key'] === 'messages' ? 'Read' : 'Edit' ?></a>
          <form method="post" action="<?= url('admin/' . $e['key'] . '/' . (int)$row['id'] . '/delete') ?>" data-confirm="<?= $e['key'] === 'bookings' ? 'Cancel this booking?' : 'Delete permanently?' ?>" data-confirm-text="<?= $e['key'] === 'bookings' ? 'Seat is released and the waitlist moves up.' : 'This cannot be undone.' ?>" data-confirm-ok="<?= $e['key'] === 'bookings' ? 'Yes, cancel' : 'Delete' ?>" data-confirm-cancel="Keep it" data-confirm-title="Are you sure?" data-confirm-danger><?= csrf_field() ?><button class="button is-tiny is-danger" type="submit"><?= $e['key'] === 'bookings' ? 'Cancel' : icon('x') ?></button></form>
        </div></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($e['list']) + 1 ?>"><div class="empty_mini">Nothing found.</div></td></tr><?php endif; ?>
    </tbody>
  </table></div>
  <?php if ($pages > 1): ?>
  <nav class="pagination" aria-label="Pages"><?php for ($i = 1; $i <= $pages; $i++): ?><?php if ($i === $page): ?><span class="is-active"><?= $i ?></span><?php else: ?><a href="<?= e($qs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?><?php endfor; ?></nav>
  <?php endif; ?>
</div>
