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

// Règle de réduction d'un code : ['kind' => 'percent'|'amount', 'value' => int|null]
// value null = ancien code : la réduction par défaut des tarifs (promoDiscount) s'applique
function promo_rule(array $row): array {
    $kind = ($row['kind'] ?? 'amount') === 'percent' ? 'percent' : 'amount';
    $value = isset($row['value']) && $row['value'] !== null ? (int)$row['value'] : null;
    return ['kind' => $kind, 'value' => $value];
}

// Consomme le code de façon atomique. Renvoie sa règle, ou null si le code est inconnu ou déjà utilisé.
function promo_consume(PDO $pdo, string $code, string $name, string $email): ?array {
    if ($code === '') return null;
    $st = $pdo->prepare('UPDATE promo_codes SET used_at = NOW(), used_name = ?, used_email = ? WHERE code = ? AND used_at IS NULL');
    $st->execute([mb_substr($name, 0, 120), mb_substr($email, 0, 255), $code]);
    if ($st->rowCount() !== 1) return null;
    $r = $pdo->prepare('SELECT kind, value FROM promo_codes WHERE code = ?');
    $r->execute([$code]);
    return promo_rule($r->fetch() ?: []);
}
