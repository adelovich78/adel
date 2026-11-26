<?php
/** Accueil */
$tagline = (string)setting('hero_tagline', 'SPEAK ENGLISH. FOR REAL.');
$subtitle = (string)setting_t('hero_subtitle', '');
?>
<!-- HERO vidéo plein écran -->
<section class="section_hero" data-hero>
  <div class="hero_media" data-hero-media>
    <video class="hero_video" autoplay muted loop playsinline preload="auto" poster="<?= asset('img/poster-hero.jpg') ?>" data-hero-video>
      <source src="<?= url('assets/video/hero.mp4') ?>" type="video/mp4">
    </video>
    <div class="hero_overlay"></div>
    <div class="hero_grain" aria-hidden="true"></div>
  </div>
  <a href="<?= url('book') ?>" class="hero_badge" aria-label="<?= t('Book a seat') ?>" data-cursor-label="<?= t('Book') ?>">
    <svg viewBox="0 0 200 200" aria-hidden="true"><defs><path id="circ" d="M100 100m-78 0a78 78 0 1 1 156 0a78 78 0 1 1-156 0"/></defs><text fill="#f7f3ea" font-size="13" font-weight="700" font-family="DM Sans, sans-serif"><textPath href="#circ" textLength="482" lengthAdjust="spacing"><?= t('BOOK A SEAT • MAX 5 PER TABLE • NATIVE TEACHERS •') ?></textPath></text></svg>
    <span class="hero_badge-core"><span>A1→C2</span></span>
  </a>
  <div class="padding-global">
    <div class="container-large">
      <div class="hero_component">
        <div class="hero_top">
          <div class="eyebrow hero_eyebrow" data-hero-fade><?= t('Boutique English café · Native teachers · Since 2019') ?></div>
        </div>
        <h1 class="hero_heading" aria-label="<?= e($tagline) ?>">
          <span class="line"><span class="split-inner" data-hero-line>Speak English.</span></span>
          <span class="line"><span class="split-inner" data-hero-line><span class="serif-italic">For real.</span></span></span>
        </h1>
        <div class="hero_bottom" style="margin-top:2.5rem">
          <p class="text-size-large" style="max-width:30rem;color:rgba(247,243,234,.85);margin:0" data-hero-fade><?= e($subtitle) ?></p>
          <p class="text-size-xlarge" style="margin:0" data-hero-fade><?= t('Talk about') ?>
            <span class="hero_rotator" data-rotator><span class="is-active"><?= t('your career.') ?></span><span><?= t('your travels.') ?></span><span><?= t('your ideas.') ?></span><span><?= t('anything.') ?></span></span>
          </p>
          <div class="button-group" data-hero-fade>
            <a href="<?= url('book') ?>" class="button is-cream" data-magnetic><?= t('Book a seat') ?> <?= icon('arrow', 'button_arrow') ?></a>
            <a href="<?= url('programs') ?>" class="button is-ghost-light"><?= t('Explore programs') ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Marquee -->
<section class="section_marquee" aria-hidden="true">
  <div class="marquee_track" data-marquee>
    <?php for ($k = 0; $k < 2; $k++): ?>
    <div class="marquee_group">
      <span>Hello there</span><?= icon('sparkles', 'marquee_star') ?><span><em>How’s it going?</em></span><?= icon('sparkles', 'marquee_star') ?>
      <span>Fancy a flat white?</span><?= icon('sparkles', 'marquee_star') ?><span><em>Let’s talk</em></span><?= icon('sparkles', 'marquee_star') ?>
      <span>Cheers, mate</span><?= icon('sparkles', 'marquee_star') ?><span><em>Break a leg</em></span><?= icon('sparkles', 'marquee_star') ?>
      <span>Piece of cake</span><?= icon('sparkles', 'marquee_star') ?>
    </div>
    <?php endfor; ?>
  </div>
</section>

<!-- Manifesto -->
<section class="section_manifesto">
  <div class="padding-global">
    <div class="container-medium">
      <div class="padding-section-large">
        <div class="eyebrow margin-bottom margin-medium" data-reveal><?= t('Not a school. A café.') ?></div>
        <p class="manifesto_text" data-scrub-text>
          <?= t('You studied English for years. You know the grammar. But when it’s time to') ?>
          <span class="manifesto_inline-media"><video autoplay muted loop playsinline preload="none" poster="<?= asset('img/poster-people.jpg') ?>"><source src="<?= url('assets/video/people.mp4') ?>" type="video/mp4"></video></span>
          <?= t('speak, the words freeze. Here, you sit at a') ?> <span class="is-accent"><?= t('table of five') ?></span><?= t(', order your favourite coffee and talk with a') ?> <span class="is-accent"><?= t('native teacher') ?></span> <?= t('— until English feels like') ?> <span class="is-accent"><?= t('yours') ?></span>.
        </p>
        <div class="stats_grid margin-top margin-xlarge">
          <div class="stats_item" data-reveal><div class="stats_number"><span data-count="<?= (int)$stats['learners'] ?>">0</span><sup>+</sup></div><p class="text-size-small text-color-muted"><?= t('learners speaking with confidence') ?></p></div>
          <div class="stats_item" data-reveal><div class="stats_number"><span data-count="5">0</span></div><p class="text-size-small text-color-muted"><?= t('seats per table, never more') ?></p></div>
          <div class="stats_item" data-reveal><div class="stats_number"><span data-count="<?= (int)$stats['countries'] ?>">0</span></div><p class="text-size-small text-color-muted"><?= t('nationalities around our tables') ?></p></div>
          <div class="stats_item" data-reveal><div class="stats_number"><span data-count="<?= (int)$stats['sessions'] ?>">0</span><sup>+</sup></div><p class="text-size-small text-color-muted"><?= t('conversations hosted') ?></p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Programmes (scroll horizontal) -->
<section class="section_programs" data-hscroll>
  <div class="padding-global">
    <div class="container-large">
      <div class="section-header_component" style="margin-bottom:2.5rem">
        <div class="max-width-large">
          <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('The menu') ?></div>
          <h2 class="heading-style-h2" data-split><?= t('Seven ways to') ?> <span class="serif-italic"><?= t('find your voice.') ?></span></h2>
        </div>
        <a href="<?= url('programs') ?>" class="button is-secondary" data-reveal><?= t('All programs') ?> <?= icon('arrow', 'button_arrow') ?></a>
      </div>
    </div>
  </div>
  <div class="programs_pin" data-hscroll-pin>
    <div class="programs_track" data-hscroll-track>
      <?php foreach ($programs as $i => $p) include APP . '/views/partials/program-card.php'; ?>
    </div>
  </div>
  <div class="padding-global"><div class="container-large"><div class="programs_progress"><div class="programs_progress-bar" data-hscroll-progress></div></div></div></div>
</section>

<!-- Vidéo qui s'ouvre -->
<section class="section_expand" style="margin-top:6rem">
  <div class="expand_wrap" data-expand>
    <div class="expand_sticky">
      <div class="expand_media" data-expand-media data-cursor-label="<?= t('Feel it') ?>">
        <video autoplay muted loop playsinline preload="none" poster="<?= asset('img/poster-table.jpg') ?>"><source src="<?= url('assets/video/table.mp4') ?>" type="video/mp4"></video>
        <div class="expand_caption" data-expand-caption>
          <p class="heading-style-h3" style="max-width:36rem;margin:0"><?= t('Five seats. One native teacher.') ?> <span class="serif-italic"><?= t('Zero pressure.') ?></span></p>
          <a href="<?= url('book') ?>" class="button is-cream"><?= t('Find my table') ?> <?= icon('arrow', 'button_arrow') ?></a>
        </div>
      </div>
      <div class="expand_title" data-expand-title><span class="heading-style-display"><?= t('The table') ?></span></div>
    </div>
  </div>
</section>

<!-- Comment ça marche -->
<section>
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-large">
        <div class="split_component">
          <div>
            <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('How it works') ?></div>
            <h2 class="heading-style-h2" data-split><?= t('From “um…” to') ?> <span class="serif-italic"><?= t('“actually, I think…”') ?></span></h2>
            <p class="text-size-large text-color-muted margin-top margin-small" data-reveal><?= t('Choose your level and goal, pick a table and a time that suits you, and just show up. We take care of the rest.') ?></p>
            <div class="how_grid margin-top margin-large" style="grid-template-columns:1fr 1fr">
              <div class="how_item" data-reveal><h3 class="heading-style-h5"><?= t('Pick your level') ?></h3><p class="text-size-small text-color-muted" style="margin:.5rem 0 0"><?= t('A1 to C2 — not sure? Your teacher adjusts after the first table.') ?></p></div>
              <div class="how_item" data-reveal><h3 class="heading-style-h5"><?= t('Choose a table') ?></h3><p class="text-size-small text-color-muted" style="margin:.5rem 0 0"><?= t('Groups, 1-to-1, business, pronunciation, kids or teens.') ?></p></div>
              <div class="how_item" data-reveal><h3 class="heading-style-h5"><?= t('Grab a seat') ?></h3><p class="text-size-small text-color-muted" style="margin:.5rem 0 0"><?= t('Live seat availability. Full? Join the waitlist in one tap.') ?></p></div>
              <div class="how_item" data-reveal><h3 class="heading-style-h5"><?= t('Track your progress') ?></h3><p class="text-size-small text-color-muted" style="margin:.5rem 0 0"><?= t('Speaking, listening, pronunciation, vocabulary & confidence.') ?></p></div>
            </div>
          </div>
          <div class="table-visual_component" data-table-visual aria-label="<?= t('A table of five learners and one native teacher') ?>">
            <div class="table-visual_table"><div><strong>5 + 1</strong><span class="text-size-tiny text-style-allcaps" style="opacity:.75"><?= t('per table') ?></span></div></div>
            <?php
            // Places autour de la table (angle en degrés)
            $seats = [[-90, __('Teacher'), 'is-teacher'], [-30, __('You'), 'is-student'], [30, '', ''], [90, '', ''], [150, '', ''], [210, '', '']];
            foreach ($seats as [$a, $label, $cls]):
              $x = 50 + 40 * cos(deg2rad($a)); $y = 50 + 40 * sin(deg2rad($a)); ?>
              <span class="table-visual_seat <?= $cls ?>" style="left:<?= round($x, 2) ?>%;top:<?= round($y, 2) ?>%" data-seat><?= e($label ?: '·') ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Professeurs -->
<section style="background:var(--color-surface-2)">
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-large">
        <div class="section-header_component">
          <div class="max-width-large">
            <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Native teachers') ?></div>
            <h2 class="heading-style-h2" data-split><?= t('Accents from') ?> <span class="serif-italic"><?= t('four') ?></span> <?= t('corners of the English-speaking world.') ?></h2>
          </div>
          <a href="<?= url('teachers') ?>" class="button is-secondary" data-reveal><?= t('Meet the team') ?> <?= icon('arrow', 'button_arrow') ?></a>
        </div>
        <div class="teachers_grid">
          <?php foreach (array_slice($teachers, 0, 4) as $i => $t) include APP . '/views/partials/teacher-card.php'; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Événements -->
<section>
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-large">
        <div class="section-header_component">
          <div class="max-width-large">
            <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('After hours') ?></div>
            <h2 class="heading-style-h2" data-split><?= t('Quiz nights, debates &') ?> <span class="serif-italic"><?= t('karaoke.') ?></span></h2>
          </div>
          <a href="<?= url('events') ?>" class="button is-secondary" data-reveal><?= t('Full calendar') ?> <?= icon('arrow', 'button_arrow') ?></a>
        </div>
        <div class="events_list">
          <?php foreach ($events as $ev) include APP . '/views/partials/event-row.php'; ?>
          <?php if (!$events): ?><div class="empty_state"><?= t('New events are brewing. Check back soon!') ?></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Split photo : la bibliothèque -->
<section style="background:var(--color-inverse-bg);color:var(--color-inverse-text)">
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-large">
        <div class="split_component">
          <div class="split_media" data-reveal="scale"><img src="<?= asset('img/photo-table.jpg') ?>" alt="<?= t('A notebook and coffee cups on a café table') ?>" loading="lazy" data-parallax="-10"><span class="split_media-tag">📚 <?= t('18 books · A1 → C2') ?></span></div>
          <div>
            <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('The library') ?></div>
            <h2 class="heading-style-h2" data-split><?= t('Books that') ?> <span class="serif-italic"><?= t('grow with you.') ?></span></h2>
            <p class="text-size-large margin-top margin-small" style="opacity:.75" data-reveal><?= t('Every learner gets a private library matched to their CEFR level: café dialogues, key phrases, graded readers, business and exam guides. New shelves unlock as you level up.') ?></p>
            <div class="feature_list">
              <div class="feature_item" data-reveal><span class="feature_icon"><?= icon('download') ?></span><div><b><?= t('Download & keep') ?></b><p class="text-size-small" style="opacity:.7;margin:.25rem 0 0"><?= t('PDF guides to prepare each table.') ?></p></div></div>
              <div class="feature_item" data-reveal><span class="feature_icon"><?= icon('lock') ?></span><div><b><?= t('Unlocks with your level') ?></b><p class="text-size-small" style="opacity:.7;margin:.25rem 0 0"><?= t('Reach B2, the B2 shelf opens. Simple.') ?></p></div></div>
            </div>
            <a href="<?= url('library') ?>" class="button is-cream margin-top margin-medium" data-reveal><?= t('Browse the library') ?> <?= icon('arrow', 'button_arrow') ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Formules -->
<section>
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-large">
        <div class="text-align-center margin-bottom margin-xlarge">
          <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Memberships') ?></div>
          <h2 class="heading-style-h2 max-width-large align-center" data-split><?= t('Make English') ?> <span class="serif-italic"><?= t('a habit,') ?></span> <?= t('not a chore.') ?></h2>
        </div>
        <div class="pricing_grid">
          <?php foreach ($plans as $pl) include APP . '/views/partials/plan-card.php'; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Témoignages -->
<section style="background:var(--color-surface-2)">
  <div class="padding-global">
    <div class="container-medium">
      <div class="padding-section-large quotes_component" data-quotes>
        <div class="eyebrow margin-bottom margin-medium"><?= t('Overheard at the café') ?></div>
        <div class="quotes_slider">
          <?php foreach ([
            ['After three months of tables, I led my first meeting in English without notes. My manager asked what changed.', 'Elif Y.', 'Product manager · B1 → B2', 'mono-bg-0'],
            ['My daughter used to hide behind me. Now she corrects my pronunciation at dinner. Hatice is magic.', 'Mehmet K.', 'Parent of Defne, 8', 'mono-bg-2'],
            ['It doesn’t feel like a class. It feels like meeting friends who happen to be brilliant teachers.', 'Can D.', 'Software engineer · C1', 'mono-bg-1'],
            ['We moved our whole team’s training here. Engagement went from “mandatory” to “can I book another one?”.', 'Zeynep A.', 'HR Director, Anadolu Lojistik', 'mono-bg-3'],
          ] as $k => $q): ?>
          <figure class="<?= $k === 0 ? 'is-active' : '' ?>" style="margin:0">
            <blockquote class="quote_item" style="margin:0">“<?= t($q[0]) ?>”</blockquote>
            <figcaption class="quote_author"><span class="quote_avatar <?= $q[3] ?>"><?= e(initials($q[1])) ?></span><span><b><?= e($q[1]) ?></b><br><span class="text-size-small text-color-muted"><?= t($q[2]) ?></span></span></figcaption>
          </figure>
          <?php endforeach; ?>
        </div>
        <div class="quotes_nav" data-quotes-nav></div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section>
  <div class="padding-global">
    <div class="container-medium">
      <div class="padding-section-large">
        <h2 class="heading-style-h2 margin-bottom margin-large" data-split><?= t('Good questions.') ?></h2>
        <?php foreach ([
          ['Do I need to take a level test?', __('No. Choose the level that feels right (A1 to C2). After your first table, your teacher confirms or adjusts it and records it in your dashboard.')],
          ['What if a session is full?', __('Join the waitlist in one click. When a seat opens, it is automatically offered to the next person and you get an email instantly.')],
          ['Can I cancel?', __('Yes — free cancellation up to :hours hours before the session. Membership credits are returned automatically.', ['hours' => (int)setting('cancellation_hours', 12)])],
          ['Are the teachers really native speakers?', __('Yes. All our teachers are native English speakers from the UK, the US, Canada and Australia, and trained in conversation coaching.')],
          ['Do you offer programs for companies?', __('Absolutely. Our Business English team builds tailored programs, with a corporate dashboard to follow employees’ bookings and progress.')],
        ] as $k => $f): ?>
        <div class="faq_item" data-faq>
          <button class="faq_question" type="button" aria-expanded="false"><?= t($f[0]) ?><span class="faq_icon"><?= icon('plus') ?></span></button>
          <div class="faq_answer"><div><p><?= e($f[1]) ?></p></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php require APP . '/views/partials/cta.php'; ?>
