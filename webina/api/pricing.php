<?php
// /api/pricing.php — lecture (GET) et modification (PUT) du barème pricing.json. Admin uniquement.
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require_admin();

$file = dirname(__DIR__) . '/pricing.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['pricing' => load_pricing()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') fail('Méthode non autorisée.', 405);

$in = body();
$p = load_pricing();

// Nombre entier positif borné, sinon erreur explicite
$int = function ($v, string $label, int $min = 0, int $max = 10000000): int {
    if (!is_numeric($v) || (float)$v != (int)$v) fail("Valeur invalide : $label", 422);
    $v = (int)$v;
    if ($v < $min || $v > $max) fail("Valeur hors limites : $label", 422);
    return $v;
};
$num = function ($v, string $label, float $min, float $max): float {
    if (!is_numeric($v)) fail("Valeur invalide : $label", 422);
    $v = (float)$v;
    if ($v < $min || $v > $max) fail("Valeur hors limites : $label", 422);
    return $v;
};

$keys = ['base', 'extraPage', 'extraLanguage', 'bookingCalendar', 'contentPartial', 'contentFull', 'shop',
    'logoValue', 'renewalYearly', 'photographer', 'gallery', 'blog', 'promoDiscount'];
foreach ($keys as $k) {
    if (isset($in['prices'][$k])) $p['prices'][$k] = $int($in['prices'][$k], $k);
}
if (($p['prices']['promoDiscount'] ?? 0) >= ($p['prices']['base'] ?? 0)) fail('La réduction doit rester inférieure au prix du pack.', 422);
if (isset($in['includedPages'])) $p['includedPages'] = $int($in['includedPages'], 'includedPages', 1, 50);
if (isset($in['includedLanguages'])) $p['includedLanguages'] = $int($in['includedLanguages'], 'includedLanguages', 1, 9);
if (isset($in['depositPercent'])) $p['depositPercent'] = $int($in['depositPercent'], 'depositPercent', 0, 100);
foreach (['USD', 'EUR'] as $c) {
    if (isset($in['tlPer'][$c])) $p['tlPer'][$c] = $num($in['tlPer'][$c], "tlPer.$c", 1, 100000);
    if (isset($in['rounding'][$c])) $p['rounding'][$c] = $int($in['rounding'][$c], "rounding.$c", 1, 1000);
}
if (isset($in['rounding']['TRY'])) $p['rounding']['TRY'] = $int($in['rounding']['TRY'], 'rounding.TRY', 1, 10000);

// Écriture atomique : fichier temporaire puis renommage
$json = json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
$tmp = $file . '.tmp';
if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $file)) {
    @unlink($tmp);
    fail("Impossible d'écrire pricing.json (droits du fichier ?).", 500);
}
json_out(['ok' => true, 'pricing' => $p]);
