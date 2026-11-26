<?php /** Abonnements, paiements, profil — commun aux tableaux de bord */ ?>
<div class="panel span-6">
  <div class="panel_head"><h2 class="panel_title"><?= t('Membership') ?></h2><a class="text-link text-size-small" href="<?= url('memberships') ?>"><?= t('Plans') ?></a></div>
  <?php $active = array_values(array_filter($memberships, fn($m) => $m['status'] === 'active')); ?>
  <?php if ($active): $m = $active[0]; ?>
    <div style="display:flex;gap:1.25rem;align-items:center">
      <div class="ring" style="--p:<?= (int)$m['credits'] ? min(100, round($m['credits_remaining'] / max(1, $m['credits']) * 100)) : 100 ?>"><span><?= (int)$m['credits'] ? (int)$m['credits_remaining'] : '∞' ?></span></div>
      <div><b class="heading-style-h4"><?= e($m['name']) ?></b><br><span class="text-size-small text-color-muted"><?= (int)$m['credits'] ? t('credits left this period') : t('Unlimited sessions') ?> · <?= t('until') ?> <?= e(fmt_date($m['ends_at'], 'j M Y')) ?><?= $m['includes_events'] ? ' · ' . t('events included') : '' ?></span></div>
    </div>
  <?php else: ?>
    <div class="empty_mini"><?= t('No active membership.') ?> <a class="text-link" href="<?= url('memberships') ?>"><?= t('Discover plans') ?></a></div>
  <?php endif; ?>
  <?php $pendingM = array_filter($memberships, fn($m) => $m['status'] === 'pending'); if ($pendingM): ?><p class="text-size-small text-color-muted margin-top margin-xsmall"><?= t(':n membership awaiting payment — see payments.', ['n' => count($pendingM)]) ?></p><?php endif; ?>
</div>
<div class="panel span-6">
  <div class="panel_head"><h2 class="panel_title"><?= t('Payments') ?></h2></div>
  <?php if (!$payments): ?><div class="empty_mini"><?= t('No payments yet.') ?></div><?php endif; ?>
  <div class="table_wrap"><table class="data_table">
    <?php foreach (array_slice($payments, 0, 6) as $pay): ?>
    <tr><td><?= e(fmt_date($pay['created_at'], 'j M')) ?></td><td><?= t(ucfirst($pay['purpose'])) ?> <span class="text-color-muted text-size-tiny">· <?= t($pay['method']) ?></span></td><td class="is-num"><b><?= money($pay['amount'], $pay['currency']) ?></b></td><td><?= status_badge($pay['status']) ?></td>
      <td><?php if ($pay['status'] === 'pending'): ?><form method="post" action="<?= url('payments/' . (int)$pay['id'] . '/pay') ?>"><?= csrf_field() ?><button class="button is-tiny" type="submit"><?= t('Pay') ?></button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php if ($events): ?>
<div class="panel span-12">
  <div class="panel_head"><h2 class="panel_title"><?= t('My events') ?></h2><a class="text-link text-size-small" href="<?= url('events') ?>"><?= t('All events') ?></a></div>
  <?php foreach ($events as $ev): ?><div class="list_item"><span><b><?= e(tf($ev, 'title')) ?></b><br><span class="text-size-small text-color-muted"><?= e(fmt_date($ev['starts_at'], 'D j M · H:i')) ?></span></span><?= status_badge($ev['status']) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>
<div class="panel span-12">
  <details class="inline_form">
    <summary class="panel_head" style="margin:0"><h2 class="panel_title"><?= t('Profile & security') ?></h2><span class="button is-tiny is-secondary"><?= t('Edit') ?></span></summary>
    <form method="post" action="<?= url('dashboard/profile') ?>" class="admin_form margin-top margin-small"><?= csrf_field() ?>
      <div class="form_field"><label class="form_label"><?= t('First name') ?></label><input class="form_input" name="first_name" value="<?= e($u['first_name']) ?>"></div>
      <div class="form_field"><label class="form_label"><?= t('Last name') ?></label><input class="form_input" name="last_name" value="<?= e($u['last_name']) ?>"></div>
      <div class="form_field"><label class="form_label"><?= t('Phone') ?></label><input class="form_input" name="phone" value="<?= e($u['phone']) ?>"></div>
      <div class="form_field"><label class="form_label"><?= t('Goal') ?></label><select class="form_input" name="goal"><?php foreach (GOALS as $k => $g): ?><option value="<?= $k ?>" <?= $u['goal'] === $k ? 'selected' : '' ?>><?= e(goal_label($k)) ?></option><?php endforeach; ?></select></div>
      <div class="form_field"><label class="form_label"><?= t('New password') ?></label><input class="form_input" type="password" name="password" minlength="8" autocomplete="new-password"><div class="form_help"><?= t('Leave empty to keep it.') ?></div></div>
      <div class="form_field" style="align-self:end"><button class="button" type="submit"><?= t('Save') ?></button></div>
    </form>
    <form method="post" action="<?= url('dashboard/api-token') ?>" class="margin-top margin-small"><?= csrf_field() ?><p class="text-size-small text-color-muted"><?= t('Mobile app & integrations (beta):') ?> <button class="button is-tiny is-secondary" type="submit"><?= t('Generate API token') ?></button></p></form>
  </details>
</div>
