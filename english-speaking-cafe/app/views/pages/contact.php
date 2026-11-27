<?php
$heading = t('Come say') . ' <span class="serif-italic">' . t('hello.') . '</span>';
$eyebrow = __('Contact');
require APP . '/views/partials/page-hero.php';
$topic = old('topic', input('topic', 'General'));
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-medium">
    <div class="split_component" style="align-items:start">
      <form method="post" action="<?= url('contact') ?>" class="card" data-reveal>
        <?= csrf_field() ?>
        <input class="form_honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="form_grid">
          <div class="form_field"><label class="form_label" for="c-name"><?= t('Name') ?></label><input class="form_input" id="c-name" name="name" value="<?= e(old('name')) ?>" required></div>
          <div class="form_field"><label class="form_label" for="c-email"><?= t('Email') ?></label><input class="form_input" id="c-email" type="email" name="email" value="<?= e(old('email', input('email', ''))) ?>" required></div>
        </div>
        <div class="form_field"><label class="form_label" for="c-topic"><?= t('Topic') ?></label>
          <select class="form_input" id="c-topic" name="topic"><?php foreach (['General', 'Booking', 'Business', 'Kids & teens', 'Memberships', 'Events', 'Careers'] as $tp): ?><option value="<?= e($tp) ?>" <?= $tp === $topic ? 'selected' : '' ?>><?= t($tp) ?></option><?php endforeach; ?></select></div>
        <div class="form_field"><label class="form_label" for="c-msg"><?= t('Message') ?></label><textarea class="form_input" id="c-msg" name="message" required><?= e(old('message')) ?></textarea></div>
        <button class="button" type="submit" data-magnetic><?= t('Send message') ?> <?= icon('arrow', 'button_arrow') ?></button>
      </form>
      <div>
        <div class="feature_list" style="margin-top:0">
          <div class="feature_item" data-reveal><span class="feature_icon"><?= icon('pin') ?></span><div><b><?= t('Visit the café') ?></b><p class="text-color-muted" style="margin:.25rem 0 0"><?= e(setting('address', '')) ?></p></div></div>
          <div class="feature_item" data-reveal><span class="feature_icon"><?= icon('clock') ?></span><div><b><?= t('Opening hours') ?></b><p class="text-color-muted" style="margin:.25rem 0 0"><?= e(setting_t('opening_hours', '')) ?></p></div></div>
          <div class="feature_item" data-reveal><span class="feature_icon"><?= icon('phone') ?></span><div><b><?= t('Call us') ?></b><p style="margin:.25rem 0 0"><a href="tel:<?= e(preg_replace('/\s+/', '', (string)setting('phone', ''))) ?>"><?= e(setting('phone', '')) ?></a></p></div></div>
          <div class="feature_item" data-reveal><span class="feature_icon"><?= icon('mail') ?></span><div><b><?= t('Email') ?></b><p style="margin:.25rem 0 0"><a href="mailto:<?= e(setting('contact_email', '')) ?>"><?= e(setting('contact_email', '')) ?></a></p></div></div>
        </div>
        <div class="contact-logo_component margin-top margin-medium" data-reveal="scale" aria-hidden="true"><?= logo('full', 'contact-logo_svg') ?></div>
      </div>
    </div>
  </div></div></div>
</section>
