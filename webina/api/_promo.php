<?php
// Outils communs aux codes de réduction

function promo_normalise(string $code): string {
    return strtoupper(preg_replace('/\s+/', '', $code));
}

// Format WB-XXXX-XXXX, sans caractères ambigus (0/O, 1/I/L)
function promo_generate(): string {
    $a = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $part = function () use ($a) {
        $s = '';
        for ($i = 0; $i < 4; $i++) $s .= $a[random_int(0, strlen($a) - 1)];
        return $s;
    };
    return 'WB-' . $part() . '-' . $part();
}

// Consomme le code de façon atomique : true seulement pour le premier qui l'utilise
function promo_consume(PDO $pdo, string $code, string $name, string $email): bool {
    if ($code === '') return false;
    $st = $pdo->prepare('UPDATE promo_codes SET used_at = NOW(), used_name = ?, used_email = ? WHERE code = ? AND used_at IS NULL');
    $st->execute([mb_substr($name, 0, 120), mb_substr($email, 0, 255), $code]);
    return $st->rowCount() === 1;
}
