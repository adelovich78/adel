<div class="eyebrow margin-bottom margin-small">Secure checkout · demo mode</div>
<h1 class="heading-style-h2 margin-bottom margin-small">Almost <span class="serif-italic">there.</span></h1>
<p class="text-color-muted">Stripe is not configured yet, so this is a payment simulator. Add your Stripe keys in <code>config/config.php</code> to accept real cards.</p>
<div class="card margin-top margin-medium margin-bottom margin-medium">
  <div class="summary_row"><span>Item</span><span><?= e($label) ?></span></div>
  <div class="summary_total"><span>Total</span><span><?= money($p['amount'], $p['currency']) ?></span></div>
</div>
<form method="post" action="<?= url('checkout/' . (int)$p['id']) ?>"><?= csrf_field() ?>
  <div class="form_field"><label class="form_label">Card number</label><input class="form_input" value="4242 4242 4242 4242" readonly></div>
  <div class="form_grid"><div class="form_field"><label class="form_label">Expiry</label><input class="form_input" value="12 / 34" readonly></div><div class="form_field"><label class="form_label">CVC</label><input class="form_input" value="123" readonly></div></div>
  <button class="button is-full" type="submit"><?= icon('lock') ?> Pay <?= money($p['amount'], $p['currency']) ?></button>
</form>
<p class="text-size-small margin-top margin-small"><a class="text-link" href="<?= url('dashboard') ?>">Pay later from my dashboard</a></p>
