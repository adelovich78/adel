<?php
use App\Core\Auth;
$me = Auth::user();
$data = [
    'api' => url('api/v1/sessions'),
    'levels' => array_map(fn($l) => ['code' => $l, 'label' => CEFR_LABELS[$l]], CEFR),
    'goals' => array_map(fn($k, $g) => ['key' => $k, 'title' => $g[0], 'desc' => $g[1]], array_keys(GOALS), GOALS),
    'programs' => array_map(fn($p) => [
        'slug' => $p['slug'], 'name' => $p['name'], 'category' => category_label($p['category']), 'tagline' => $p['tagline'],
        'min' => cefr_index($p['min_level']), 'max' => cefr_index($p['max_level']), 'goals' => array_filter(explode(',', (string)$p['goals'])),
        'price' => money($p['price']), 'capacity' => (int)$p['capacity'], 'duration' => (int)$p['duration_min'], 'credit' => (bool)$p['membership_eligible'],
    ], $programs),
    'teachers' => array_map(fn($t) => ['id' => (int)$t['id'], 'name' => $t['display_name'], 'accent' => $t['accent'], 'origin' => $t['origin'], 'tags' => $t['specialties'], 'initials' => initials($t['display_name'])], $teachers),
    'pre' => array_merge($pre, ['session' => (int)input('session')]),
    'loggedIn' => (bool)$me,
    'hasCredits' => (bool)$membership,
    'onsite' => setting('allow_onsite_payment', '1') === '1',
    'loginUrl' => url('login'),
    'registerUrl' => url('register'),
];
?>
<section class="section_page-hero" style="padding-bottom:2rem">
  <div class="page-hero_deco" aria-hidden="true"></div>
  <div class="padding-global"><div class="container-large">
    <div class="eyebrow margin-bottom margin-small" data-reveal>Book your seat</div>
    <h1 class="heading-style-h1" data-split>Pull up a <span class="serif-italic">chair.</span></h1>
  </div></div>
</section>
<section>
  <div class="padding-global"><div class="container-large"><div style="padding-bottom:6rem">
    <div class="booking_layout" data-booking>
      <div>
        <div class="booking_steps" data-steps>
          <?php foreach (['Level', 'Goal', 'Program', 'Teacher', 'Date & seat', 'Confirm'] as $k => $s): ?>
          <button type="button" class="booking_step-tab<?= $k === 0 ? ' is-active' : '' ?>" data-step-tab="<?= $k ?>" <?= $k ? 'disabled' : '' ?>><i><?= $k + 1 ?></i><?= $s ?></button>
          <?php endforeach; ?>
        </div>

        <div class="booking_panel is-active" data-panel="0">
          <h2 class="heading-style-h3 margin-bottom margin-small">What’s your English level?</h2>
          <p class="text-color-muted margin-bottom margin-medium">Not sure? Pick the one that feels closest — your teacher will confirm it.</p>
          <div class="choice_grid is-levels" data-levels></div>
        </div>
        <div class="booking_panel" data-panel="1">
          <h2 class="heading-style-h3 margin-bottom margin-medium">What brings you to the café?</h2>
          <div class="choice_grid" data-goals></div>
        </div>
        <div class="booking_panel" data-panel="2">
          <h2 class="heading-style-h3 margin-bottom margin-small">Choose your program</h2>
          <p class="text-color-muted margin-bottom margin-medium">Highlighted programs match your level and goal.</p>
          <div class="choice_grid" data-programs></div>
        </div>
        <div class="booking_panel" data-panel="3">
          <h2 class="heading-style-h3 margin-bottom margin-medium">Any favourite teacher?</h2>
          <div class="choice_grid" data-teachers></div>
        </div>
        <div class="booking_panel" data-panel="4">
          <h2 class="heading-style-h3 margin-bottom margin-small">Pick a date & a seat</h2>
          <p class="text-color-muted margin-bottom margin-medium">Live availability · <span class="seat_dot" style="display:inline-block;vertical-align:middle"></span> taken · <span class="seat_dot is-free" style="display:inline-block;vertical-align:middle"></span> free. Full? Join the waitlist.</p>
          <div class="slot_days" data-days></div>
          <div class="slot_list" data-slots><div class="empty_state loading_dots">Brewing availability</div></div>
        </div>
        <div class="booking_panel" data-panel="5">
          <h2 class="heading-style-h3 margin-bottom margin-medium">Confirm your seat</h2>
          <?php if ($me): ?>
          <form method="post" action="<?= url('book') ?>" data-book-form>
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" data-f-session>
            <input type="hidden" name="level" data-f-level>
            <input type="hidden" name="goal" data-f-goal>
            <?php if (count($learners) > 1 || $learners[0]['id'] !== $me['id']): ?>
            <div class="form_field"><label class="form_label" for="pp">Who is this seat for?</label>
              <select class="form_input" id="pp" name="participant_id"><?php foreach ($learners as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['first_name'] . ' ' . $l['last_name']) ?> (<?= e($l['cefr_level']) ?>)<?= (int)$l['id'] === (int)$me['id'] ? ' — me' : '' ?></option><?php endforeach; ?></select></div>
            <?php else: ?><input type="hidden" name="participant_id" value="<?= (int)$me['id'] ?>"><?php endif; ?>
            <span class="form_label" data-pay-title>Payment</span>
            <div class="pay_options" data-pay-options>
              <?php if ($membership): ?><label class="pay_option" data-pay-credit><input type="radio" name="method" value="credit" checked><span><b>Use a membership credit</b><br><span class="text-size-small text-color-muted"><?= (int)$membership['plan_credits'] === 0 ? 'Unlimited plan' : (int)$membership['credits_remaining'] . ' credits left' ?></span></span></label><?php endif; ?>
              <label class="pay_option"><input type="radio" name="method" value="stripe" <?= $membership ? '' : 'checked' ?>><span><b>Pay online now</b><br><span class="text-size-small text-color-muted">Card · seat held <?= (int)setting('booking_hold_minutes', 20) ?> min during payment</span></span></label>
              <?php if (setting('allow_onsite_payment', '1') === '1'): ?><label class="pay_option"><input type="radio" name="method" value="onsite"><span><b>Pay at the café</b><br><span class="text-size-small text-color-muted">Seat confirmed now, pay on arrival</span></span></label><?php endif; ?>
            </div>
            <p class="text-size-small text-color-muted" data-waitlist-note hidden>This session is full: you’ll join the waitlist and won’t be charged until a seat opens.</p>
            <button class="button is-full" type="submit" data-submit data-magnetic>Confirm my seat <?= icon('arrow', 'button_arrow') ?></button>
            <p class="text-size-tiny text-color-muted margin-top margin-xsmall">Free cancellation until <?= (int)setting('cancellation_hours', 12) ?>h before the session.</p>
          </form>
          <?php else: ?>
          <div class="card is-flat">
            <p class="text-size-large" style="margin-top:0">Almost done! Sign in or create a free account to confirm your seat.</p>
            <div class="button-group"><a class="button" href="#" data-auth-link="login">Sign in & book</a><a class="button is-secondary" href="#" data-auth-link="register">Create account</a></div>
          </div>
          <?php endif; ?>
        </div>

        <div class="button-group margin-top margin-large" style="justify-content:space-between">
          <button type="button" class="button is-secondary" data-prev hidden>← Back</button>
          <button type="button" class="button" data-next disabled>Continue <?= icon('arrow', 'button_arrow') ?></button>
        </div>
      </div>

      <aside class="card booking_summary" aria-live="polite">
        <div class="eyebrow margin-bottom margin-small">Your table</div>
        <div class="summary_row"><span>Level</span><span data-s-level>—</span></div>
        <div class="summary_row"><span>Goal</span><span data-s-goal>—</span></div>
        <div class="summary_row"><span>Program</span><span data-s-program>—</span></div>
        <div class="summary_row"><span>Teacher</span><span data-s-teacher>Any</span></div>
        <div class="summary_row"><span>When</span><span data-s-when>—</span></div>
        <div class="summary_row"><span>Seats</span><span data-s-seats>—</span></div>
        <div class="summary_total"><span>Total</span><span data-s-price>—</span></div>
      </aside>
    </div>
  </div></div></div>
</section>
<script type="application/json" id="booking-data"><?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
