<?php
use App\Core\Auth;
$me = Auth::user();
$data = [
    'api' => url('api/v1/sessions'),
    'locale' => lang() === 'tr' ? 'tr-TR' : 'en-GB',
    'levels' => array_map(fn($l) => ['code' => $l, 'label' => cefr_label($l)], CEFR),
    'goals' => array_map(fn($k) => ['key' => $k, 'title' => goal_label($k), 'desc' => goal_desc($k)], array_keys(GOALS)),
    'programs' => array_map(fn($p) => [
        'slug' => $p['slug'], 'name' => tf($p, 'name'), 'category' => category_label($p['category']), 'tagline' => tf($p, 'tagline'),
        'min' => cefr_index($p['min_level']), 'max' => cefr_index($p['max_level']), 'goals' => array_values(array_filter(explode(',', (string)$p['goals']))),
        'price' => money($p['price']), 'capacity' => (int)$p['capacity'], 'duration' => (int)$p['duration_min'], 'credit' => (bool)$p['membership_eligible'],
    ], $programs),
    'teachers' => array_map(fn($t) => ['id' => (int)$t['id'], 'name' => $t['display_name'], 'accent' => __($t['accent']), 'origin' => $t['origin'], 'tags' => implode(', ', array_map('__', array_map('trim', explode(',', (string)$t['specialties'])))), 'initials' => initials($t['display_name'])], $teachers),
    'pre' => array_merge($pre, ['session' => (int)input('session')]),
    'loggedIn' => (bool)$me,
    'hasCredits' => (bool)$membership,
    'onsite' => setting('allow_onsite_payment', '1') === '1',
    'loginUrl' => url('login'),
    'registerUrl' => url('register'),
    // Textes de l'interface JS
    'i18n' => [
        'loading' => __('Brewing availability'), 'loadError' => __('Could not load availability. Please refresh.'),
        'noSessions' => __('No upcoming sessions for this combination. Try “Any teacher” or another program — or ask us.'),
        'anyTeacher' => __('Any teacher'), 'mostAvailability' => __('Most availability'), 'any' => __('Any'),
        'notFor' => __('Not for'), 'min' => __('min'), 'max' => __('max'), 'private' => __('private'),
        'with' => __('with'), 'withName' => __('with :name'), 'waitlistWaiting' => __('Waitlist · :n waiting'), 'seatLeft' => __(':n seat left'), 'seatsLeft' => __(':n seats left'),
        'freeOf' => __(':free / :cap free'), 'fullWaitlist' => __('Full — waitlist'), 'teacher' => __('Native teacher'),
        'joinWaitlist' => __('Join the waitlist'), 'confirmSeat' => __('Confirm my seat'),
    ],
];
?>
<section class="section_page-hero" style="padding-bottom:2rem">
  <div class="page-hero_deco" aria-hidden="true"></div>
  <div class="padding-global"><div class="container-large">
    <div class="eyebrow margin-bottom margin-small" data-reveal><?= t('Book your seat') ?></div>
    <h1 class="heading-style-h1" data-split><?= t('Pull up a') ?> <span class="serif-italic"><?= t('chair.') ?></span></h1>
  </div></div>
</section>
<section>
  <div class="padding-global"><div class="container-large"><div style="padding-bottom:6rem">
    <div class="booking_layout" data-booking>
      <div>
        <div class="booking_steps" data-steps>
          <?php foreach (['Level', 'Goal', 'Program', 'Teacher', 'Date & seat', 'Confirm'] as $k => $s): ?>
          <button type="button" class="booking_step-tab<?= $k === 0 ? ' is-active' : '' ?>" data-step-tab="<?= $k ?>" <?= $k ? 'disabled' : '' ?>><i><?= $k + 1 ?></i><?= t($s) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="booking_panel is-active" data-panel="0">
          <h2 class="heading-style-h3 margin-bottom margin-small"><?= t('What’s your English level?') ?></h2>
          <p class="text-color-muted margin-bottom margin-medium"><?= t('Not sure? Pick the one that feels closest — your teacher will confirm it.') ?> <a class="text-link" href="<?= url('level-test') ?>"><?= t('Take the free level test') ?></a></p>
          <div class="choice_grid is-levels" data-levels></div>
        </div>
        <div class="booking_panel" data-panel="1">
          <h2 class="heading-style-h3 margin-bottom margin-medium"><?= t('What brings you to the café?') ?></h2>
          <div class="choice_grid" data-goals></div>
        </div>
        <div class="booking_panel" data-panel="2">
          <h2 class="heading-style-h3 margin-bottom margin-small"><?= t('Choose your program') ?></h2>
          <p class="text-color-muted margin-bottom margin-medium"><?= t('Highlighted programs match your level and goal.') ?></p>
          <div class="choice_grid" data-programs></div>
        </div>
        <div class="booking_panel" data-panel="3">
          <h2 class="heading-style-h3 margin-bottom margin-medium"><?= t('Any favourite teacher?') ?></h2>
          <div class="choice_grid" data-teachers></div>
        </div>
        <div class="booking_panel" data-panel="4">
          <h2 class="heading-style-h3 margin-bottom margin-small"><?= t('Pick a date & a seat') ?></h2>
          <p class="text-color-muted margin-bottom margin-medium"><?= t('Live availability') ?> · <span class="seat_dot" style="display:inline-block;vertical-align:middle"></span> <?= t('taken') ?> · <span class="seat_dot is-free" style="display:inline-block;vertical-align:middle"></span> <?= t('free seat') ?>. <?= t('Full? Join the waitlist.') ?></p>
          <div class="slot_days" data-days></div>
          <div class="slot_list" data-slots><div class="empty_state loading_dots"><?= t('Brewing availability') ?></div></div>
        </div>
        <div class="booking_panel" data-panel="5">
          <h2 class="heading-style-h3 margin-bottom margin-medium"><?= t('Confirm your seat') ?></h2>
          <?php if ($me): ?>
          <form method="post" action="<?= url('book') ?>" data-book-form>
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" data-f-session>
            <input type="hidden" name="level" data-f-level>
            <input type="hidden" name="goal" data-f-goal>
            <?php if (count($learners) > 1 || $learners[0]['id'] !== $me['id']): ?>
            <div class="form_field"><label class="form_label" for="pp"><?= t('Who is this seat for?') ?></label>
              <select class="form_input" id="pp" name="participant_id"><?php foreach ($learners as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['first_name'] . ' ' . $l['last_name']) ?> (<?= e($l['cefr_level']) ?>)<?= (int)$l['id'] === (int)$me['id'] ? ' — ' . t('me') : '' ?></option><?php endforeach; ?></select></div>
            <?php else: ?><input type="hidden" name="participant_id" value="<?= (int)$me['id'] ?>"><?php endif; ?>
            <span class="form_label" data-pay-title><?= t('Payment') ?></span>
            <div class="pay_options" data-pay-options>
              <?php if ($membership): ?><label class="pay_option" data-pay-credit><input type="radio" name="method" value="credit" checked><span><b><?= t('Use a membership credit') ?></b><br><span class="text-size-small text-color-muted"><?= (int)$membership['plan_credits'] === 0 ? t('Unlimited plan') : t(':n credits left', ['n' => (int)$membership['credits_remaining']]) ?></span></span></label><?php endif; ?>
              <label class="pay_option"><input type="radio" name="method" value="stripe" <?= $membership ? '' : 'checked' ?>><span><b><?= t('Pay online now') ?></b><br><span class="text-size-small text-color-muted"><?= t('Card · seat held :n min during payment', ['n' => (int)setting('booking_hold_minutes', 20)]) ?></span></span></label>
              <?php if (setting('allow_onsite_payment', '1') === '1'): ?><label class="pay_option"><input type="radio" name="method" value="onsite"><span><b><?= t('Pay at the café') ?></b><br><span class="text-size-small text-color-muted"><?= t('Seat confirmed now, pay on arrival') ?></span></span></label><?php endif; ?>
            </div>
            <p class="text-size-small text-color-muted" data-waitlist-note hidden><?= t('This session is full: you’ll join the waitlist and won’t be charged until a seat opens.') ?></p>
            <button class="button is-full" type="submit" data-submit data-magnetic><?= t('Confirm my seat') ?> <?= icon('arrow', 'button_arrow') ?></button>
            <p class="text-size-tiny text-color-muted margin-top margin-xsmall"><?= t('Free cancellation until :hh before the session.', ['h' => (int)setting('cancellation_hours', 12)]) ?></p>
          </form>
          <?php else: ?>
          <div class="card is-flat">
            <p class="text-size-large" style="margin-top:0"><?= t('Almost done! Sign in or create a free account to confirm your seat.') ?></p>
            <div class="button-group"><a class="button" href="#" data-auth-link="login"><?= t('Sign in & book') ?></a><a class="button is-secondary" href="#" data-auth-link="register"><?= t('Create account') ?></a></div>
          </div>
          <?php endif; ?>
        </div>

        <div class="button-group margin-top margin-large" style="justify-content:space-between">
          <button type="button" class="button is-secondary" data-prev hidden>← <?= t('Back') ?></button>
          <button type="button" class="button" data-next disabled><?= t('Continue') ?> <?= icon('arrow', 'button_arrow') ?></button>
        </div>
      </div>

      <aside class="card booking_summary" aria-live="polite">
        <div class="eyebrow margin-bottom margin-small"><?= t('Your table') ?></div>
        <div class="summary_row"><span><?= t('Level') ?></span><span data-s-level>—</span></div>
        <div class="summary_row"><span><?= t('Goal') ?></span><span data-s-goal>—</span></div>
        <div class="summary_row"><span><?= t('Program') ?></span><span data-s-program>—</span></div>
        <div class="summary_row"><span><?= t('Teacher') ?></span><span data-s-teacher><?= t('Any') ?></span></div>
        <div class="summary_row"><span><?= t('When') ?></span><span data-s-when>—</span></div>
        <div class="summary_row"><span><?= t('Seats') ?></span><span data-s-seats>—</span></div>
        <div class="summary_total"><span><?= t('Total') ?></span><span data-s-price>—</span></div>
      </aside>
    </div>
  </div></div></div>
</section>
<script type="application/json" id="booking-data"><?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
