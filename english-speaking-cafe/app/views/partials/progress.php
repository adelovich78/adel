<?php
/** Graphiques de progression : $progress = DashboardController::progressFor(), $compact optionnel */
$skills = \App\Controllers\DashboardController::SKILLS;
$latest = $progress['latest'];
$hist = $progress['history'];
$prev = count($hist) > 1 ? $hist[count($hist) - 2] : null;
$uid = 'c' . substr(md5(json_encode($latest) . mt_rand()), 0, 6);
if (!$latest): ?>
  <div class="empty_mini">No progress report yet — it appears after your first session.</div>
<?php return; endif;
// Radar : 5 axes
$cx = 120; $cy = 115; $r = 85;
$pt = function (array $row) use ($skills, $cx, $cy, $r) {
    $pts = [];
    foreach ($skills as $k => $s) {
        $a = deg2rad(-90 + $k * 72);
        $v = max(4, (int)$row[$s]) / 100;
        $pts[] = round($cx + cos($a) * $r * $v, 1) . ',' . round($cy + sin($a) * $r * $v, 1);
    }
    return implode(' ', $pts);
};
$avg = fn($row) => array_sum(array_map(fn($s) => (int)$row[$s], $skills)) / count($skills);
?>
<div class="dash_grid">
  <div class="<?= !empty($compact) ? 'span-12' : 'span-5' ?>">
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.5rem">
      <span class="level-pill" style="height:2.2rem;font-size:1rem;min-width:3rem"><?= e($latest['cefr_level']) ?></span>
      <div><b><?= e(CEFR_LABELS[$latest['cefr_level']]) ?></b><br><span class="text-size-small text-color-muted">Updated <?= e(fmt_date($latest['created_at'], 'j M Y')) ?><?= $latest['teacher_name'] ? ' by ' . e($latest['teacher_name']) : '' ?></span></div>
    </div>
    <svg class="chart_svg" viewBox="0 0 240 235" role="img" aria-label="Skills radar">
      <?php foreach ([.25, .5, .75, 1] as $g): $ring = []; foreach ($skills as $k => $s) { $a = deg2rad(-90 + $k * 72); $ring[] = round($cx + cos($a) * $r * $g, 1) . ',' . round($cy + sin($a) * $r * $g, 1); } ?>
        <polygon class="grid" fill="none" points="<?= implode(' ', $ring) ?>"/>
      <?php endforeach; ?>
      <?php foreach ($skills as $k => $s): $a = deg2rad(-90 + $k * 72); ?>
        <line class="grid" x1="<?= $cx ?>" y1="<?= $cy ?>" x2="<?= round($cx + cos($a) * $r, 1) ?>" y2="<?= round($cy + sin($a) * $r, 1) ?>"/>
        <text class="axis-label" x="<?= round($cx + cos($a) * ($r + 16), 1) ?>" y="<?= round($cy + sin($a) * ($r + 14) + 3, 1) ?>" text-anchor="middle"><?= e(ucfirst($s)) ?></text>
      <?php endforeach; ?>
      <?php if ($prev): ?><polygon class="radar-prev" points="<?= $pt($prev) ?>"/><?php endif; ?>
      <polygon class="radar-area" points="<?= $pt($latest) ?>"/>
    </svg>
  </div>
  <div class="<?= !empty($compact) ? 'span-12' : 'span-7' ?>">
    <?php foreach ($skills as $s): $v = (int)$latest[$s]; $d = $prev ? $v - (int)$prev[$s] : 0; ?>
      <div class="skill_bar"><span><?= e(ucfirst($s)) ?></span><span class="skill_track"><span class="skill_fill" style="display:block;width:<?= $v ?>%"></span></span><span class="text-size-small text-weight-bold"><?= $v ?><?php if ($d): ?><small class="<?= $d > 0 ? 'text-color-accent' : '' ?>" style="font-size:.65rem"> <?= $d > 0 ? '+' . $d : $d ?></small><?php endif; ?></span></div>
    <?php endforeach; ?>
    <?php if (count($hist) > 1):
      // Courbe : moyenne des compétences dans le temps
      $w = 420; $h = 130; $n = count($hist); $pts = [];
      foreach ($hist as $k => $row) $pts[] = [round(10 + $k * ($w - 20) / max(1, $n - 1), 1), round($h - 10 - $avg($row) / 100 * ($h - 20), 1)];
      $line = implode(' ', array_map(fn($p) => $p[0] . ',' . $p[1], $pts));
      $area = '10,' . ($h - 10) . ' ' . $line . ' ' . end($pts)[0] . ',' . ($h - 10); ?>
      <div class="text-size-tiny text-style-allcaps text-color-muted" style="margin-top:1.25rem">Overall score over time</div>
      <svg class="chart_svg" viewBox="0 0 <?= $w ?> <?= $h + 14 ?>" role="img" aria-label="Progress over time">
        <defs><linearGradient id="gradArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="var(--color-accent)" stop-opacity=".28"/><stop offset="1" stop-color="var(--color-accent)" stop-opacity="0"/></linearGradient></defs>
        <?php foreach ([25, 50, 75] as $g): $y = $h - 10 - $g / 100 * ($h - 20); ?><line class="grid" x1="10" x2="<?= $w - 10 ?>" y1="<?= $y ?>" y2="<?= $y ?>" stroke-dasharray="2 4"/><?php endforeach; ?>
        <polygon class="area" points="<?= $area ?>"/>
        <polyline class="line" points="<?= $line ?>"/>
        <?php foreach ($pts as $k => $p): ?><circle class="dot" cx="<?= $p[0] ?>" cy="<?= $p[1] ?>" r="3.5"/><text class="axis-label" x="<?= $p[0] ?>" y="<?= $h + 10 ?>" text-anchor="middle"><?= e(fmt_date($hist[$k]['created_at'], 'j M')) ?></text><?php endforeach; ?>
      </svg>
    <?php endif; ?>
  </div>
</div>
