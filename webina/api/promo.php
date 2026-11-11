<?php
// /api/promo.php — codes de réduction à usage unique
//   GET  ?action=check&code=XXX   (public)  → { valid }  sans consommer le code
//   GET                            (admin)   → liste des codes
//   POST { count, note, kind, value } (admin) → génère de nouveaux codes
//        kind = 'percent' (value 1–100) ou 'amount' (value en TL)
//   DELETE ?id=                    (admin)   → supprime un code non utilisé
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_promo.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && ($_GET['action'] ?? '') === 'check') {
    rate_limit('promo', 20);
    $code = promo_normalise((string)($_GET['code'] ?? ''));
    if ($code === '' || strlen($code) > 24) json_out(['valid' => false]);
    $st = db()->prepare('SELECT used_at, kind, value FROM promo_codes WHERE code = ?');
    $st->execute([$code]);
    $row = $st->fetch();
    $valid = $row !== false && $row['used_at'] === null;
    json_out(['valid' => $valid, 'used' => $row !== false && $row['used_at'] !== null] + ($valid ? promo_rule($row) : []));
}

require_admin();

if ($method === 'GET') {
    $rows = db()->query("SELECT id, code, kind, value, note, used_name, used_email,
        DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%s') AS created_at,
        DATE_FORMAT(used_at, '%Y-%m-%dT%H:%i:%s') AS used_at
        FROM promo_codes ORDER BY (used_at IS NULL) DESC, created_at DESC")->fetchAll();
    json_out(['items' => $rows]);
}

if ($method === 'POST') {
    $d = body();
    $count = max(1, min(50, (int)($d['count'] ?? 1)));
    $note = str_field($d, 'note', 120);
    $kind = ($d['kind'] ?? '') === 'percent' ? 'percent' : 'amount';
    $value = (int)($d['value'] ?? 0);
    if ($kind === 'percent' && ($value < 1 || $value > 100)) fail('Le pourcentage doit être entre 1 et 100.', 422);
    if ($kind === 'amount' && ($value < 1 || $value > 1000000)) fail('Indiquez un montant en TL.', 422);
    $st = db()->prepare('INSERT INTO promo_codes (id, code, kind, value, note) VALUES (?, ?, ?, ?, ?)');
    $made = [];
    while (count($made) < $count) {
        $code = promo_generate();
        try { $st->execute([uuid4(), $code, $kind, $value, $note]); $made[] = $code; }
        catch (PDOException $e) { if ($e->getCode() !== '23000') throw $e; } // doublon : on retire un code
    }
    json_out(['codes' => $made], 201);
}

if ($method === 'DELETE') {
    $id = (string)($_GET['id'] ?? '');
    if (!preg_match('/^[0-9a-f-]{36}$/', $id)) fail('Identifiant invalide.', 422);
    db()->prepare('DELETE FROM promo_codes WHERE id = ? AND used_at IS NULL')->execute([$id]);
    json_out(['ok' => true]);
}

fail('Méthode non autorisée.', 405);
