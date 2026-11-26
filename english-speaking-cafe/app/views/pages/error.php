<section class="section_page-hero">
  <div class="padding-global"><div class="container-medium text-align-center">
    <div class="error_code"><?= (int)$code ?></div>
    <h1 class="heading-style-h3 margin-top margin-medium"><?= t($message ?: 'Something went wrong.') ?></h1>
    <div class="button-group is-center margin-top margin-large"><a class="button" href="<?= url('/') ?>"><?= t('Back to the café') ?></a><a class="button is-secondary" href="<?= url('book') ?>"><?= t('Book a seat') ?></a></div>
  </div></div>
</section>
