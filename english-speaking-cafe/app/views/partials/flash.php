<?php $msgs = array_filter(['success' => flash('success'), 'error' => flash('error'), 'info' => flash('info')]); ?>
<?php if ($msgs): ?>
<div class="toast-stack" data-toasts role="status" aria-live="polite">
  <?php foreach ($msgs as $type => $m): ?><div class="alert is-<?= e($type) ?>"><?= icon($type === 'error' ? 'x' : 'check') ?><span><?= e($m) ?></span></div><?php endforeach; ?>
</div>
<?php endif; ?>
