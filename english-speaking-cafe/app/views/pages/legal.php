<?php $heading = 'Terms & <span class="serif-italic">privacy</span>'; $eyebrow = 'Legal'; require APP . '/views/partials/page-hero.php'; ?>
<section><div class="padding-global"><div class="container-small"><div class="padding-section-medium text-size-medium">
  <h2 class="heading-style-h4 margin-bottom margin-small">Bookings & cancellations</h2>
  <p>Seats are confirmed once paid, covered by a membership credit or reserved with “pay at the café”. Online payments hold your seat for <?= (int)setting('booking_hold_minutes', 20) ?> minutes. Free cancellation until <?= (int)setting('cancellation_hours', 12) ?> hours before the session; membership credits are returned automatically. Waitlisted learners are promoted in order and notified by email.</p>
  <h2 class="heading-style-h4 margin-bottom margin-small margin-top margin-medium">Payments</h2>
  <p>Online payments are processed securely by Stripe. We never store card numbers.</p>
  <h2 class="heading-style-h4 margin-bottom margin-small margin-top margin-medium">Personal data</h2>
  <p>We store the information needed to run your learning experience: account details, bookings, payments and progress notes. Parents manage their children’s data; companies see their employees’ bookings and progress. You can request access, correction or deletion at any time by contacting <?= e(setting('contact_email', '')) ?>.</p>
  <h2 class="heading-style-h4 margin-bottom margin-small margin-top margin-medium">Library</h2>
  <p>Downloadable books are for personal learning use only and may not be redistributed.</p>
</div></div></div></section>
