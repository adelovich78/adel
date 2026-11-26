<div class="eyebrow margin-bottom margin-small"><?= t('Welcome back') ?></div>
<h1 class="heading-style-h2 margin-bottom margin-medium"><?= t('Sign in to') ?> <span class="serif-italic"><?= t('the café.') ?></span></h1>
<form method="post" action="<?= url('login') ?>">
  <?= csrf_field() ?>
  <div class="form_field"><label class="form_label" for="email"><?= t('Email') ?></label><input class="form_input" id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus></div>
  <div class="form_field"><label class="form_label" for="password"><?= t('Password') ?></label><input class="form_input" id="password" type="password" name="password" autocomplete="current-password" required></div>
  <div style="display:flex;justify-content:flex-end;margin:-.25rem 0 1.25rem"><a class="text-size-small text-link" href="<?= url('forgot') ?>"><?= t('Forgot password?') ?></a></div>
  <button class="button is-full" type="submit"><?= t('Sign in') ?> <?= icon('arrow', 'button_arrow') ?></button>
</form>
<p class="text-size-small text-color-muted margin-top margin-medium"><?= t('New here?') ?> <a class="text-link" href="<?= url('register') ?>"><?= t('Create your account') ?></a></p>
