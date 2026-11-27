<?php
/** Résultat du test de niveau */
use App\Core\Auth;
$me = Auth::user();
$lvl = $r['level'];
$canDo = ['A1' => 'I can introduce myself and order a coffee.', 'A2' => 'I can talk about my day and my plans.', 'B1' => 'I can share opinions and tell stories.', 'B2' => 'I can debate and work in English.', 'C1' => 'I can express nuance and lead discussions.', 'C2' => 'I speak with precision and ease.'];
$advice = [
    'A1' => 'Start with our Conversation Tables and the Kids or Teens clubs: simple topics, lots of support, real confidence from the first session.',
    'A2' => 'Small Group Conversation at A2 is perfect for you: everyday topics, five learners, one native teacher.',
    'B1' => 'Join Small Group Conversation at B1 and the Pronunciation Lab — this is where speaking really takes off.',
    'B2' => 'Try B2 groups, Business English and our debate evenings to sound natural and precise.',
    'C1' => 'C1 groups, Business English and 1-to-1 coaching will polish nuance, idioms and leadership language.',
    'C2' => 'You speak at mastery level. 1-to-1 coaching, debates and the book club will keep your English sharp.',
];
?>
<section class="section_page-hero">
  <div class="page-hero_deco" aria-hidden="true"></div>
  <div class="padding-global"><div class="container-medium">
    <div class="test_result card" data-reveal>
      <div class="eyebrow margin-bottom margin-small"><?= t('Your result') ?></div>
      <div class="test_result-level"><span><?= e($lvl) ?></span></div>
      <h1 class="heading-style-h2 margin-top margin-small"><?= e(cefr_label($lvl)) ?></h1>
      <p class="text-size-large text-color-muted"><?= t($canDo[$lvl]) ?></p>
      <p class="text-size-small"><?= t('Score: :score / :total correct answers', ['score' => $r['score'], 'total' => $total]) ?></p>

      <div class="test_bands">
        <?php foreach (CEFR as $l): $n = (int)($r['bands'][$l] ?? 0); ?>
        <div class="test_band<?= $l === $lvl ? ' is-current' : '' ?>"><span class="test_band-bar"><span style="height:<?= $n * 25 ?>%"></span></span><b><?= $l ?></b><small><?= $n ?>/4</small></div>
        <?php endforeach; ?>
      </div>

      <div class="card is-flat text-align-left margin-top margin-medium"><b><?= t('Our recommendation') ?></b><p style="margin:.4rem 0 0"><?= t($advice[$lvl]) ?></p></div>

      <div class="button-group is-center margin-top margin-medium">
        <?php if ($me): ?>
          <form method="post" action="<?= url('level-test/save') ?>"><?= csrf_field() ?><button class="button" type="submit"><?= t('Save :level as my level', ['level' => $lvl]) ?></button></form>
        <?php else: ?>
          <a class="button" href="<?= url('register?level=' . $lvl) ?>"><?= t('Create my account at :level', ['level' => $lvl]) ?></a>
        <?php endif; ?>
        <a class="button is-secondary" href="<?= url('book?level=' . $lvl) ?>"><?= t('Book a table at :level', ['level' => $lvl]) ?></a>
        <a class="button is-secondary" href="<?= url('library?level=' . $lvl) ?>"><?= t('Books for :level', ['level' => $lvl]) ?></a>
      </div>
      <p class="text-size-tiny text-color-muted margin-top margin-small"><?= t('This test is an estimate. Your native teacher confirms your level at your first table.') ?> <a class="text-link" href="<?= url('level-test') ?>"><?= t('Take the test again') ?></a></p>
    </div>
  </div></div>
</section>
