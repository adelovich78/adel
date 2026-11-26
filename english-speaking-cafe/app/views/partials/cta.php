<section class="section_cta">
  <video class="cta_video" autoplay muted loop playsinline preload="none" poster="<?= asset('img/poster-lamps.jpg') ?>"><source src="<?= url('assets/video/lamps.mp4') ?>" type="video/mp4"></video>
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-large">
        <div class="cta_component">
          <div class="eyebrow margin-bottom margin-medium" style="color:rgba(247,243,234,.7)" data-reveal>Your seat is waiting</div>
          <h2 class="cta_heading" data-split>Pull up a chair. <em>Start talking.</em></h2>
          <p class="text-size-large margin-top margin-medium max-width-medium align-center" style="opacity:.8" data-reveal>Your first table is <?= money(setting('trial_price', 15)) ?>. No level test, no textbook — just real conversation.</p>
          <div class="button-group is-center margin-top margin-large" data-reveal>
            <a href="<?= url('book') ?>" class="button is-cream" data-magnetic>Book my first table <?= icon('arrow', 'button_arrow') ?></a>
            <a href="<?= url('contact') ?>" class="button is-ghost-light">Visit the café</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
