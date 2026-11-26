<h1 class="heading-style-h2 margin-bottom margin-medium">Choose a new <span class="serif-italic">password.</span></h1>
<form method="post" action="<?= url('reset/' . e($token)) ?>"><?= csrf_field() ?>
  <div class="form_field"><label class="form_label" for="pw">New password</label><input class="form_input" id="pw" type="password" name="password" minlength="8" required></div>
  <button class="button is-full" type="submit">Save password</button>
</form>
