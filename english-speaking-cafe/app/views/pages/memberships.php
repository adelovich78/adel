<?php
$heading = t('Make English') . ' <span class="serif-italic">' . t('a habit.') . '</span>';
$eyebrow = __('Memberships');
$aside = t('Monthly plans with session credits, free events and priority on waitlists. Cancel anytime from your dashboard.');
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-large"><div class="padding-section-medium">
    <div class="pricing_grid"><?php foreach ($plans as $pl) include APP . '/views/partials/plan-card.php'; ?></div>
    <p class="text-align-center text-size-small text-color-muted margin-top margin-large"><?= t('Credits are valid on groups, conversation tables, Pronunciation Lab and kids & teens clubs. 1-to-1 coaching is billed separately (one included with Club Unlimited).') ?></p>
  </div></div></div>
</section>
<section style="background:var(--color-surface-2)">
  <div class="padding-global"><div class="container-medium"><div class="padding-section-large">
    <h2 class="heading-style-h2 margin-bottom margin-large" data-split><?= t('Members ask') ?></h2>
    <?php foreach ([
      ['How do credits work?', 'Each booking uses one credit. Cancel in time and the credit comes back automatically. Club Unlimited has no credit limit.'],
      ['Can I share my membership with my kids?', 'Parents can book for their children with their own membership credits from the family dashboard.'],
      ['Can I pause?', 'Yes, contact us and we’ll pause your membership for up to one month per year.'],
    ] as $f): ?>
    <div class="faq_item" data-faq><button class="faq_question" type="button" aria-expanded="false"><?= t($f[0]) ?><span class="faq_icon"><?= icon('plus') ?></span></button><div class="faq_answer"><div><p><?= t($f[1]) ?></p></div></div></div>
    <?php endforeach; ?>
  </div></div></div>
</section>
