<div class="panel_head" style="margin-bottom:1.5rem">
  <div><div class="eyebrow margin-bottom margin-xsmall">Admin</div><h1 class="dash_title">Settings</h1></div>
</div>
<form class="panel" method="post" action="<?= url('admin/settings') ?>"><?= csrf_field() ?>
  <div class="admin_form">
    <?php foreach (\App\Controllers\AdminController::SETTINGS as $k => [$label, $def]): ?>
      <div class="form_field<?= in_array($k, ['hero_subtitle', 'address'], true) ? ' is-wide' : '' ?>"><label class="form_label" for="s-<?= e($k) ?>"><?= e($label) ?></label><input class="form_input" id="s-<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e($vals[$k] ?? $def) ?>"></div>
    <?php endforeach; ?>
  </div>
  <button class="button" type="submit">Save settings</button>
  <p class="text-size-small text-color-muted margin-top margin-small">Prices per program, plans and events are edited in their own sections. Stripe keys and database credentials live in <code>config/config.php</code> (cPanel → File Manager).</p>
</form>
