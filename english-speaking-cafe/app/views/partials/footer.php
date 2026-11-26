<footer class="footer_component">
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-medium">
        <div class="footer_top">
          <div>
            <a href="<?= url('/') ?>" class="footer_logo" aria-label="XY Academy"><?= logo('horizontal') ?></a>
            <p class="text-size-medium" style="margin-top:1.5rem;max-width:22rem;color:rgba(247,243,234,.75)"><?= t('A boutique café where English becomes a conversation, not a lesson.') ?></p>
            <form class="footer_newsletter" action="<?= url('contact') ?>" method="get">
              <label class="sr-only" for="nl">Email</label>
              <input id="nl" class="form_input" type="email" name="email" placeholder="<?= t('Your email for café news') ?>" required>
              <button class="button is-cream is-small" type="submit"><?= t('Join') ?></button>
            </form>
          </div>
          <div>
            <div class="footer_title"><?= t('Speak') ?></div>
            <a class="footer_link" href="<?= url('programs') ?>"><?= t('All programs') ?></a>
            <a class="footer_link" href="<?= url('one-to-one') ?>"><?= t('1-to-1 coaching') ?></a>
            <a class="footer_link" href="<?= url('business') ?>"><?= t('Business English') ?></a>
            <a class="footer_link" href="<?= url('programs/pronunciation-lab') ?>"><?= t('Pronunciation Lab') ?></a>
            <a class="footer_link" href="<?= url('programs/kids-english') ?>"><?= t('Kids & teens') ?></a>
          </div>
          <div>
            <div class="footer_title"><?= t('Café') ?></div>
            <a class="footer_link" href="<?= url('events') ?>"><?= t('Events') ?></a>
            <a class="footer_link" href="<?= url('teachers') ?>"><?= t('Native teachers') ?></a>
            <a class="footer_link" href="<?= url('memberships') ?>"><?= t('Memberships') ?></a>
            <a class="footer_link" href="<?= url('library') ?>"><?= t('Library') ?></a>
            <a class="footer_link" href="<?= url('about') ?>"><?= t('Our story') ?></a>
          </div>
          <div>
            <div class="footer_title"><?= t('Visit') ?></div>
            <p style="color:rgba(247,243,234,.85)"><?= e(setting('address', '')) ?><br><?= e(setting_t('opening_hours', '')) ?></p>
            <a class="footer_link" href="mailto:<?= e(setting('contact_email', '')) ?>"><?= e(setting('contact_email', '')) ?></a>
            <a class="footer_link" href="tel:<?= e(preg_replace('/\s+/', '', (string)setting('phone', ''))) ?>"><?= e(setting('phone', '')) ?></a>
            <?php if (setting('instagram')): ?><a class="footer_link" href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Instagram ↗</a><?php endif; ?>
            <?php if (setting('linkedin')): ?><a class="footer_link" href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener">LinkedIn ↗</a><?php endif; ?>
          </div>
        </div>
        <?php $grp = '<div class="footer_wordmark-group"><span>Speak English</span><i>✦</i><em>for real.</em><i>✦</i><span>Engage</span><i>✦</i><em>Learn</em><i>✦</i><span>Connect</span><i>✦</i></div>'; ?>
        <div class="footer_wordmark" aria-hidden="true" data-footer-wordmark>
          <div class="footer_wordmark-track" data-marquee><?= $grp . $grp ?></div>
        </div>
      </div>
      <div class="footer_bottom">
        <span>© <?= date('Y') ?> XY Academy — English Speaking Café. <?= t('All rights reserved.') ?> · <a href="<?= url('legal') ?>"><?= t('Terms & privacy') ?></a></span>
        <a class="footer_credit" href="https://webina.com.tr" target="_blank" rel="noopener" aria-label="<?= t('Created and developed by Webina') ?> — webina.com.tr">
          <span><?= t('Created and developed by') ?> <b>Webina</b><small>webina.com.tr</small></span>
          <img src="<?= asset('logo/webina-light.png') ?>" alt="Webina — Digital Creation Agency" width="58" height="47" loading="lazy">
        </a>
      </div>
    </div>
  </div>
</footer>
