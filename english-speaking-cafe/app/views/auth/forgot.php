<h1 class="heading-style-h2 margin-bottom margin-small">Forgot your <span class="serif-italic">password?</span></h1>
<p class="text-color-muted margin-bottom margin-medium">Enter your email and we’ll send you a reset link.</p>
<form method="post" action="<?= url('forgot') ?>"><?= csrf_field() ?>
  <div class="form_field"><label class="form_label" for="email">Email</label><input class="form_input" id="email" type="email" name="email" required></div>
  <button class="button is-full" type="submit">Send reset link</button>
</form>
