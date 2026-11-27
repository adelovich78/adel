<?php /** Liste de réservations : $list, $showLearner, $canCancel */ ?>
<?php if (!$list): ?><div class="empty_mini"><?= t($empty ?? 'Nothing here yet.') ?></div><?php endif; ?>
<?php foreach ($list as $b): ?>
<div class="list_item">
  <div style="display:flex;gap:1rem;align-items:center;min-width:0">
    <span class="list_date"><small><?= e(fmt_date($b['starts_at'], 'M')) ?></small><b><?= date('d', strtotime($b['starts_at'])) ?></b></span>
    <div style="min-width:0">
      <b><?= e(tf($b, 'program_name')) ?></b> <?= status_badge($b['status']) ?><?php if ($b['status'] === 'waitlist'): ?> <span class="badge"><?= t('#:n in line', ['n' => (int)$b['waitlist_position']]) ?></span><?php endif; ?><br>
      <span class="text-size-small text-color-muted"><?= e(fmt_date($b['starts_at'], 'D j M · H:i')) ?><?= $b['teacher_name'] ? ' · ' . e($b['teacher_name']) : '' ?><?= !empty($showLearner) ? ' · ' . e($b['learner_first'] . ' ' . $b['learner_last']) : '' ?><?= $b['room'] ? ' · ' . e($b['room']) : '' ?></span>
      <?php if (!empty($b['teacher_note'])): ?><br><span class="text-size-small">📝 <?= e($b['teacher_note']) ?></span><?php endif; ?>
    </div>
  </div>
  <div class="row_actions">
    <?php if ($b['status'] === 'pending' && $b['payment_id'] && ($b['pay_status'] ?? '') === 'pending'): ?>
      <form method="post" action="<?= url('payments/' . (int)$b['payment_id'] . '/pay') ?>"><?= csrf_field() ?><button class="button is-tiny" type="submit"><?= t('Pay :amount to confirm', ['amount' => money($b['pay_amount'])]) ?></button></form>
    <?php elseif (($b['pay_status'] ?? '') === 'pending' && ($b['pay_method'] ?? '') === 'onsite'): ?><span class="badge is-amber"><?= t('Pay at café') ?> · <?= money($b['pay_amount']) ?></span><?php endif; ?>
    <?php if (!empty($canCancel) && in_array($b['status'], ['pending', 'confirmed', 'waitlist'], true) && strtotime($b['starts_at']) > time()): ?>
      <form method="post" action="<?= url('bookings/' . (int)$b['id'] . '/cancel') ?>" data-confirm="<?= $b['status'] === 'waitlist' ? t('Leave the waitlist for this session?') : t('Cancel this booking?') ?>" data-confirm-text="<?= e(tf($b, 'program_name')) ?> · <?= e(fmt_date($b['starts_at'], 'D j M · H:i')) ?>" data-confirm-ok="<?= $b['status'] === 'waitlist' ? t('Leave waitlist') : t('Yes, cancel') ?>" data-confirm-danger><?= csrf_field() ?><button class="button is-tiny is-danger" type="submit"><?= $b['status'] === 'waitlist' ? t('Leave waitlist') : t('Cancel') ?></button></form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
