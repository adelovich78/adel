<?php $heading = t('Terms &') . ' <span class="serif-italic">' . t('privacy') . '</span>'; $eyebrow = __('Legal'); require APP . '/views/partials/page-hero.php'; ?>
<section><div class="padding-global"><div class="container-small"><div class="padding-section-medium text-size-medium">
  <h2 class="heading-style-h4 margin-bottom margin-small"><?= t('Bookings & cancellations') ?></h2>
  <p><?= t('Seats are confirmed once paid, covered by a membership credit or reserved with “pay at the café”. Online payments hold your seat for :min minutes. Free cancellation until :hours hours before the session; membership credits are returned automatically. Waitlisted learners are promoted in order and notified by email.', ['min' => (int)setting('booking_hold_minutes', 20), 'hours' => (int)setting('cancellation_hours', 12)]) ?></p>
  <h2 class="heading-style-h4 margin-bottom margin-small margin-top margin-medium"><?= t('Payments') ?></h2>
  <p><?= t('Online payments are processed securely by Stripe. We never store card numbers.') ?></p>
  <h2 class="heading-style-h4 margin-bottom margin-small margin-top margin-medium"><?= t('Personal data') ?></h2>
  <p><?= t('We store the information needed to run your learning experience: account details, bookings, payments and progress notes. Parents manage their children’s data; companies see their employees’ bookings and progress. You can request access, correction or deletion at any time by contacting :email.', ['email' => setting('contact_email', '')]) ?></p>
  <h2 class="heading-style-h4 margin-bottom margin-small margin-top margin-medium"><?= t('Library') ?></h2>
  <p><?= t('Downloadable books are for personal learning use only and may not be redistributed.') ?></p>
</div></div></div></section>
