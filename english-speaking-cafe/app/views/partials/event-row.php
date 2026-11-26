<?php /** @var array $ev */ $left = max(0, (int)$ev['capacity'] - (int)$ev['taken']); ?>
<div class="event-row_component" data-reveal>
  <div class="event-row_date"><strong><?= date('d', strtotime($ev['starts_at'])) ?></strong><span><?= date('M · D', strtotime($ev['starts_at'])) ?></span></div>
  <div>
    <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem"><span class="badge is-amber"><?= event_icon($ev['type']) ?> <?= e(event_label($ev['type'])) ?></span><span class="text-size-small" style="opacity:.7"><?= date('H:i', strtotime($ev['starts_at'])) ?> · from <?= e($ev['min_level']) ?></span></div>
    <div class="event-row_title"><?= e($ev['title']) ?></div>
    <?php if (!empty($withDesc)): ?><p class="text-size-small" style="margin:.5rem 0 0;opacity:.8;max-width:40rem"><?= e($ev['description']) ?></p><?php endif; ?>
  </div>
  <div class="text-size-small" style="text-align:right"><b><?= (float)$ev['price'] > 0 ? money($ev['price']) : 'Free' ?></b><br><span style="opacity:.7"><?= $left > 0 ? $left . ' spots left' : 'Waitlist' ?></span></div>
  <form method="post" action="<?= url('events/' . (int)$ev['id'] . '/register') ?>"><?= csrf_field() ?><button class="button is-secondary is-small" type="submit"><?= $left > 0 ? 'Join' : 'Waitlist' ?></button></form>
</div>
