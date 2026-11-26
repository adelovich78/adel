<h1 class="heading-style-h2 margin-bottom margin-small"><?= t('Forgot your') ?> <span class="serif-italic"><?= t('password?') ?></span></h1>
<p class="text-color-muted margin-bottom margin-medium"><?= t('Enter your email and we’ll send you a reset link.') ?></p>
<form method="post" action="<?= url('forgot') ?>"><?= csrf_field() ?>
  <div class="form_field"><label class="form_label" for="email"><?= t('Email') ?></label><input class="form_input" id="email" type="email" name="email" required></div>
  <button class="button is-full" type="submit"><?= t('Send reset link') ?></button>
</form>
