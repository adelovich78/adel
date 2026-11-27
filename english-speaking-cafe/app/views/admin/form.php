<?php
/** Formulaire générique d'entité admin */
$val = function (string $name) use ($row) {
    $o = old($name, null);
    if ($o !== null) return $o;
    return $row[$name] ?? null;
};
?>
<div class="panel_head" style="margin-bottom:1.5rem">
  <div><div class="eyebrow margin-bottom margin-xsmall"><a href="<?= url('admin/' . $e['key']) ?>">← <?= e($e['label']) ?></a></div><h1 class="dash_title"><?= e($title) ?></h1></div>
</div>
<div class="dash_grid">
<form class="panel <?= !empty($extra['roster']) ? 'span-7' : 'span-12' ?>" method="post" action="<?= url('admin/' . $e['key'] . '/save') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>">
  <div class="admin_form">
  <?php foreach ($fields as $f):
    [$name, $type, $label] = $f;
    if ($row && in_array($name, $e['create_only'] ?? [], true)) continue;
    $v = $val($name);
    $wide = in_array($type, ['textarea'], true) ? ' is-wide' : ''; ?>
    <div class="form_field<?= $wide ?>">
      <?php if ($type === 'bool'): ?>
        <label class="form_checkbox" style="margin-top:1.8rem"><input type="checkbox" name="<?= e($name) ?>" value="1" <?= ($v ?? ($row ? 0 : 1)) ? 'checked' : '' ?>> <b><?= e($label) ?></b></label>
      <?php else: ?>
        <label class="form_label" for="f-<?= e($name) ?>"><?= e($label) ?><?= !empty($f['required']) ? ' *' : '' ?></label>
        <?php switch ($type):
          case 'textarea': ?><textarea class="form_input" id="f-<?= e($name) ?>" name="<?= e($name) ?>"><?= e($v) ?></textarea><?php break;
          case 'select': ?><select class="form_input" id="f-<?= e($name) ?>" name="<?= e($name) ?>"><?php foreach ($f['options'] as $ok => $ol): ?><option value="<?= e($ok) ?>" <?= (string)$v === (string)$ok ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select><?php break;
          case 'datetime': ?><input class="form_input" type="datetime-local" id="f-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= $v ? e(date('Y-m-d\TH:i', strtotime((string)$v))) : '' ?>"><?php break;
          case 'date': ?><input class="form_input" type="date" id="f-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= $v ? e(date('Y-m-d', strtotime((string)$v))) : '' ?>"><?php break;
          case 'number': ?><input class="form_input" type="number" id="f-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($v) ?>"><?php break;
          case 'decimal': ?><input class="form_input" type="number" step="0.01" min="0" id="f-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($v) ?>"><?php break;
          case 'color': ?><input class="form_input" type="color" id="f-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($v ?: '#0f4a37') ?>" style="height:3.2rem;padding:.3rem"><?php break;
          case 'password': ?><input class="form_input" type="password" id="f-<?= e($name) ?>" name="<?= e($name) ?>" autocomplete="new-password" minlength="8"><?php break;
          case 'image': ?>
            <?php if ($v): ?><div style="display:flex;gap:.75rem;align-items:center;margin-bottom:.5rem"><img class="thumb" src="<?= e(media_url($v)) ?>" alt=""><label class="form_checkbox text-size-small"><input type="checkbox" name="<?= e($name) ?>_remove" value="1"> Remove</label></div><?php endif; ?>
            <input class="form_input" type="file" id="f-<?= e($name) ?>" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp"><?php break;
          case 'file': ?>
            <?php if ($v): ?><p class="text-size-small text-color-muted" style="margin:0 0 .4rem">Current: <?= e($v) ?></p><?php endif; ?>
            <input class="form_input" type="file" id="f-<?= e($name) ?>" name="<?= e($name) ?>" accept=".pdf,.epub,.zip,.mp3"><?php break;
          default: ?><input class="form_input" type="<?= $type === 'email' ? 'email' : 'text' ?>" id="f-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($v) ?>"><?php
        endswitch; ?>
      <?php endif; ?>
      <?php if (!empty($f['help'])): ?><div class="form_help"><?= e($f['help']) ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="button-group margin-top margin-small"><button class="button" type="submit">Save</button><a class="button is-secondary" href="<?= url('admin/' . $e['key']) ?>">Cancel</a></div>
</form>
<?php if (!empty($extra['roster'])): ?>
<div class="panel span-5">
  <div class="panel_head"><h2 class="panel_title">Roster</h2><a class="button is-tiny" href="<?= url('admin/bookings/new') ?>"><?= icon('plus') ?> Add learner</a></div>
  <div class="roster">
  <?php foreach ($extra['roster'] as $r): ?>
    <div class="roster_row"><span><b><?= e($r['first_name'] . ' ' . $r['last_name']) ?></b> <span class="level-pill"><?= e($r['cefr_level']) ?></span> <?= status_badge($r['status']) ?><?= $r['status'] === 'waitlist' ? ' #' . (int)$r['waitlist_position'] : '' ?></span>
      <span class="row_actions"><?php if (!in_array($r['status'], ['cancelled'], true)): ?><form method="post" action="<?= url('admin/bookings/' . (int)$r['id'] . '/status') ?>" data-confirm="Cancel this learner’s booking?" data-confirm-text="Seat is released and the waitlist moves up." data-confirm-ok="Yes, cancel" data-confirm-cancel="Keep it" data-confirm-title="Are you sure?" data-confirm-danger><?= csrf_field() ?><input type="hidden" name="status" value="cancelled"><button class="button is-tiny is-danger" type="submit"><?= icon('x') ?></button></form><?php endif; ?></span></div>
  <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
</div>
