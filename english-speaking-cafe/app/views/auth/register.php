<?php $role = old('role', input('role', 'student')); ?>
<div class="eyebrow margin-bottom margin-small"><?= t('Join the café') ?></div>
<h1 class="heading-style-h2 margin-bottom margin-medium"><?= t('Create your') ?> <span class="serif-italic"><?= t('account.') ?></span></h1>
<form method="post" action="<?= url('register') ?>" data-register>
  <?= csrf_field() ?>
  <div class="form_field"><span class="form_label"><?= t('I am') ?></span>
    <div class="choice_grid" style="grid-template-columns:repeat(3,1fr);gap:.5rem">
      <?php foreach (['student' => ['user', 'A learner'], 'parent' => ['smile', 'A parent'], 'corporate' => ['building', 'A company']] as $k => [$ic, $lbl]): ?>
      <label class="choice_card<?= $role === $k ? ' is-selected' : '' ?>" style="padding:.9rem;align-items:center;text-align:center"><input type="radio" name="role" value="<?= $k ?>" class="sr-only" <?= $role === $k ? 'checked' : '' ?>><?= icon($ic) ?><span class="text-size-small text-weight-bold"><?= t($lbl) ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="form_grid">
    <div class="form_field"><label class="form_label" for="fn"><?= t('First name') ?></label><input class="form_input" id="fn" name="first_name" value="<?= e(old('first_name')) ?>" required></div>
    <div class="form_field"><label class="form_label" for="ln"><?= t('Last name') ?></label><input class="form_input" id="ln" name="last_name" value="<?= e(old('last_name')) ?>"></div>
  </div>
  <div class="form_field" data-company <?= $role === 'corporate' ? '' : 'hidden' ?>><label class="form_label" for="co"><?= t('Company') ?></label><input class="form_input" id="co" name="company" value="<?= e(old('company')) ?>"></div>
  <div class="form_field"><label class="form_label" for="em"><?= t('Email') ?></label><input class="form_input" id="em" type="email" name="email" value="<?= e(old('email')) ?>" required></div>
  <div class="form_grid">
    <div class="form_field"><label class="form_label" for="lv"><?= t('My level') ?></label><select class="form_input" id="lv" name="cefr_level"><?php foreach (CEFR as $l): ?><option value="<?= $l ?>" <?= old('cefr_level', in_array(input('level'), CEFR, true) ? input('level') : 'B1') === $l ? 'selected' : '' ?>><?= $l ?> — <?= e(cefr_label($l)) ?></option><?php endforeach; ?></select></div>
    <div class="form_field"><label class="form_label" for="gl"><?= t('Main goal') ?></label><select class="form_input" id="gl" name="goal"><?php foreach (GOALS as $k => $g): ?><option value="<?= $k ?>" <?= old('goal') === $k ? 'selected' : '' ?>><?= e(goal_label($k)) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="form_field"><label class="form_label" for="pw"><?= t('Password') ?></label><input class="form_input" id="pw" type="password" name="password" minlength="8" autocomplete="new-password" required><div class="form_help"><?= t('At least 8 characters.') ?></div></div>
  <label class="form_checkbox form_field"><input type="checkbox" name="terms" value="1" required> <span><?= t('I accept the') ?> <a class="text-link" href="<?= url('legal') ?>" target="_blank"><?= t('terms & privacy policy') ?></a>.</span></label>
  <button class="button is-full" type="submit"><?= t('Create my account') ?> <?= icon('arrow', 'button_arrow') ?></button>
</form>
<p class="text-size-small text-color-muted margin-top margin-medium"><?= t('Already a member?') ?> <a class="text-link" href="<?= url('login') ?>"><?= t('Sign in') ?></a></p>
<script>
  // Sélection visuelle du rôle + champ entreprise
  document.querySelectorAll('[data-register] input[name=role]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('[data-register] .choice_card').forEach(function (c) { c.classList.toggle('is-selected', c.contains(r) && r.checked); });
      document.querySelector('[data-company]').hidden = r.value !== 'corporate';
    });
  });
</script>
