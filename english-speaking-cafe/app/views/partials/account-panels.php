<?php /** Abonnements, paiements, profil — commun aux tableaux de bord */ ?>
<div class="panel span-6">
  <div class="panel_head"><h2 class="panel_title">Membership</h2><a class="text-link text-size-small" href="<?= url('memberships') ?>">Plans</a></div>
  <?php $active = array_values(array_filter($memberships, fn($m) => $m['status'] === 'active')); ?>
  <?php if ($active): $m = $active[0]; ?>
    <div style="display:flex;gap:1.25rem;align-items:center">
      <div class="ring" style="--p:<?= (int)$m['credits'] ? min(100, round($m['credits_remaining'] / max(1, $m['credits']) * 100)) : 100 ?>"><span><?= (int)$m['credits'] ? (int)$m['credits_remaining'] : '∞' ?></span></div>
      <div><b class="heading-style-h4"><?= e($m['name']) ?></b><br><span class="text-size-small text-color-muted"><?= (int)$m['credits'] ? 'credits left this period' : 'Unlimited sessions' ?> · until <?= e(fmt_date($m['ends_at'], 'j M Y')) ?><?= $m['includes_events'] ? ' · events included' : '' ?></span></div>
    </div>
  <?php else: ?>
    <div class="empty_mini">No active membership. <a class="text-link" href="<?= url('memberships') ?>">Discover plans</a></div>
  <?php endif; ?>
  <?php $pendingM = array_filter($memberships, fn($m) => $m['status'] === 'pending'); if ($pendingM): ?><p class="text-size-small text-color-muted margin-top margin-xsmall"><?= count($pendingM) ?> membership awaiting payment — see payments.</p><?php endif; ?>
</div>
<div class="panel span-6">
  <div class="panel_head"><h2 class="panel_title">Payments</h2></div>
  <?php if (!$payments): ?><div class="empty_mini">No payments yet.</div><?php endif; ?>
  <div class="table_wrap"><table class="data_table">
    <?php foreach (array_slice($payments, 0, 6) as $pay): ?>
    <tr><td><?= e(fmt_date($pay['created_at'], 'j M')) ?></td><td style="text-transform:capitalize"><?= e($pay['purpose']) ?> <span class="text-color-muted text-size-tiny">· <?= e($pay['method']) ?></span></td><td class="is-num"><b><?= money($pay['amount'], $pay['currency']) ?></b></td><td><?= status_badge($pay['status']) ?></td>
      <td><?php if ($pay['status'] === 'pending'): ?><form method="post" action="<?= url('payments/' . (int)$pay['id'] . '/pay') ?>"><?= csrf_field() ?><button class="button is-tiny" type="submit">Pay</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php if ($events): ?>
<div class="panel span-12">
  <div class="panel_head"><h2 class="panel_title">My events</h2><a class="text-link text-size-small" href="<?= url('events') ?>">All events</a></div>
  <?php foreach ($events as $ev): ?><div class="list_item"><span><b><?= e($ev['title']) ?></b><br><span class="text-size-small text-color-muted"><?= e(fmt_date($ev['starts_at'], 'D j M · H:i')) ?></span></span><?= status_badge($ev['status']) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>
<div class="panel span-12">
  <details class="inline_form">
    <summary class="panel_head" style="margin:0"><h2 class="panel_title">Profile & security</h2><span class="button is-tiny is-secondary">Edit</span></summary>
    <form method="post" action="<?= url('dashboard/profile') ?>" class="admin_form margin-top margin-small"><?= csrf_field() ?>
      <div class="form_field"><label class="form_label">First name</label><input class="form_input" name="first_name" value="<?= e($u['first_name']) ?>"></div>
      <div class="form_field"><label class="form_label">Last name</label><input class="form_input" name="last_name" value="<?= e($u['last_name']) ?>"></div>
      <div class="form_field"><label class="form_label">Phone</label><input class="form_input" name="phone" value="<?= e($u['phone']) ?>"></div>
      <div class="form_field"><label class="form_label">Goal</label><select class="form_input" name="goal"><?php foreach (GOALS as $k => $g): ?><option value="<?= $k ?>" <?= $u['goal'] === $k ? 'selected' : '' ?>><?= e($g[0]) ?></option><?php endforeach; ?></select></div>
      <div class="form_field"><label class="form_label">New password</label><input class="form_input" type="password" name="password" minlength="8" autocomplete="new-password"><div class="form_help">Leave empty to keep it.</div></div>
      <div class="form_field" style="align-self:end"><button class="button" type="submit">Save</button></div>
    </form>
    <form method="post" action="<?= url('dashboard/api-token') ?>" class="margin-top margin-small"><?= csrf_field() ?><p class="text-size-small text-color-muted">Mobile app & integrations (beta): <button class="button is-tiny is-secondary" type="submit">Generate API token</button></p></form>
  </details>
</div>
