<footer class="footer_component">
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-medium">
        <div class="footer_top">
          <div>
            <a href="<?= url('/') ?>" class="footer_logo" aria-label="Home"><?= logo('horizontal') ?></a>
            <p class="text-size-medium" style="margin-top:1.5rem;max-width:22rem;color:rgba(247,243,234,.75)">A boutique café where English becomes a conversation, not a lesson.</p>
            <form class="footer_newsletter" action="<?= url('contact') ?>" method="get">
              <label class="sr-only" for="nl">Email</label>
              <input id="nl" class="form_input" type="email" name="email" placeholder="Your email for café news" required>
              <button class="button is-cream is-small" type="submit">Join</button>
            </form>
          </div>
          <div>
            <div class="footer_title">Speak</div>
            <a class="footer_link" href="<?= url('programs') ?>">All programs</a>
            <a class="footer_link" href="<?= url('one-to-one') ?>">1-to-1 coaching</a>
            <a class="footer_link" href="<?= url('business') ?>">Business English</a>
            <a class="footer_link" href="<?= url('programs/pronunciation-lab') ?>">Pronunciation Lab</a>
            <a class="footer_link" href="<?= url('programs/kids-english') ?>">Kids & teens</a>
          </div>
          <div>
            <div class="footer_title">Café</div>
            <a class="footer_link" href="<?= url('events') ?>">Events</a>
            <a class="footer_link" href="<?= url('teachers') ?>">Native teachers</a>
            <a class="footer_link" href="<?= url('memberships') ?>">Memberships</a>
            <a class="footer_link" href="<?= url('library') ?>">Library</a>
            <a class="footer_link" href="<?= url('about') ?>">Our story</a>
          </div>
          <div>
            <div class="footer_title">Visit</div>
            <p style="color:rgba(247,243,234,.85)"><?= e(setting('address', '')) ?><br><?= e(setting('opening_hours', '')) ?></p>
            <a class="footer_link" href="mailto:<?= e(setting('contact_email', '')) ?>"><?= e(setting('contact_email', '')) ?></a>
            <a class="footer_link" href="tel:<?= e(preg_replace('/\s+/', '', (string)setting('phone', ''))) ?>"><?= e(setting('phone', '')) ?></a>
            <?php if (setting('instagram')): ?><a class="footer_link" href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Instagram ↗</a><?php endif; ?>
            <?php if (setting('linkedin')): ?><a class="footer_link" href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener">LinkedIn ↗</a><?php endif; ?>
          </div>
        </div>
        <div class="footer_wordmark" aria-hidden="true" data-footer-wordmark><span>Speak</span> <span>for</span> <span>real.</span></div>
      </div>
      <div class="footer_bottom">
        <span>© <?= date('Y') ?> XY Academy — English Speaking Café. Engage · Learn · Connect.</span>
        <span><a href="<?= url('legal') ?>">Terms & privacy</a> · <a href="<?= url('api/v1') ?>">API</a></span>
      </div>
    </div>
  </div>
</footer>
