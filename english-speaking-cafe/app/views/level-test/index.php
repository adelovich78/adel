<?php
/** Test de niveau : une question à la fois (JS), toutes les questions visibles sans JS */
$heading = t('What’s your') . ' <span class="serif-italic">' . t('English level?') . '</span>';
$eyebrow = __('Free level test');
$aside = t(':n quick questions, about 8 minutes. Get your CEFR level (A1 → C2), the right table to book and the books for your level.', ['n' => count($questions)]);
require APP . '/views/partials/page-hero.php';
?>
<section>
  <div class="padding-global"><div class="container-small"><div style="padding-bottom:6rem">
    <form class="test_component card" method="post" action="<?= url('level-test') ?>" data-level-test>
      <?= csrf_field() ?>
      <div class="test_intro" data-test-intro>
        <div class="test_intro-icons">
          <span><b><?= count($questions) ?></b><?= t('questions') ?></span>
          <span><b>~8</b><?= t('minutes') ?></span>
          <span><b>A1→C2</b><?= t('CEFR') ?></span>
        </div>
        <p class="text-color-muted"><?= t('Answer without a dictionary. If you don’t know, choose “I don’t know” — it gives a more accurate result than guessing.') ?></p>
        <button class="button" type="button" data-test-start><?= t('Start the test') ?> <?= icon('arrow', 'button_arrow') ?></button>
      </div>

      <div class="test_head" data-test-head>
        <span class="text-size-small text-weight-bold" data-test-count>1 / <?= count($questions) ?></span>
        <div class="test_progress"><div class="test_progress-bar" data-test-bar></div></div>
      </div>

      <?php foreach ($questions as $i => $q): ?>
      <fieldset class="test_question" data-q="<?= $i ?>">
        <legend class="eyebrow"><?= t('Question :n', ['n' => $i + 1]) ?></legend>
        <p class="test_q" lang="en"><?= e($q['q']) ?></p>
        <div class="test_options">
          <?php foreach ($q['o'] as $k => $o): ?>
          <label class="test_option"><input type="radio" name="q[<?= $i ?>]" value="<?= $k ?>"><span class="test_letter"><?= chr(65 + $k) ?></span><span lang="en"><?= e($o) ?></span></label>
          <?php endforeach; ?>
          <label class="test_option is-skip"><input type="radio" name="q[<?= $i ?>]" value=""><span class="test_letter">?</span><span><?= t('I don’t know') ?></span></label>
        </div>
      </fieldset>
      <?php endforeach; ?>

      <div class="test_nav" data-test-nav>
        <button class="button is-secondary" type="button" data-test-prev>← <?= t('Back') ?></button>
        <button class="button" type="submit" data-test-submit><?= t('See my result') ?> <?= icon('arrow', 'button_arrow') ?></button>
      </div>
    </form>
  </div></div></div>
</section>
