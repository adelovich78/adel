<?php
// ==========================================================================
// /api/team.php — comptes de l'équipe (administrateur et développeurs).
// Réservé à l'administrateur : créer un accès /cowork, le désactiver,
// changer le mot de passe. Un développeur n'entre jamais dans /admin.
// ==========================================================================
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_cowork.php';
require_admin();

$pdo = db();
cowork_schema($pdo);
$me = current_user();
$method = $_SERVER['REQUEST_METHOD'];
$in = in_array($method, ['POST', 'PATCH'], true) ? body() : [];
$id = (string)($_GET['id'] ?? '');
$uuid = function (string $v): string { if (!preg_match('/^[0-9a-f-]{36}$/', $v)) fail('Identifiant invalide.', 422); return $v; };

function team_rows(PDO $pdo): array {
    $rows = $pdo->query("SELECT id, email, name, role, active, last_seen, created_at,
            TIMESTAMPDIFF(SECOND, last_seen, NOW()) AS idle
        FROM users WHERE role IN ('admin', 'dev') ORDER BY FIELD(role, 'admin', 'dev'), created_at")->fetchAll();
    foreach ($rows as &$r) {
        $r['active'] = (int)$r['active'];
        $r['online'] = $r['last_seen'] !== null && (int)$r['idle'] <= COWORK_PRESENCE_SECONDS;
        unset($r['idle']);
    }
    return $rows;
}

if ($method === 'GET') json_out(['items' => team_rows($pdo)]);

if ($method === 'POST') {
    $email = strtolower(trim((string)($in['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Adresse e-mail invalide.', 422);
    $pass = (string)($in['password'] ?? '');
    if (strlen($pass) < 10) fail('Mot de passe : 10 caractères minimum.', 422);
    $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetchColumn()) fail('Cette adresse a déjà un compte.', 409);
    $uid = uuid4();
    $pdo->prepare("INSERT INTO users (id, email, password_hash, role, name, active) VALUES (?, ?, ?, 'dev', ?, 1)")
        ->execute([$uid, $email, password_hash($pass, PASSWORD_DEFAULT), mb_substr(trim((string)($in['name'] ?? '')), 0, 120) ?: null]);
    json_out(['user' => ['id' => $uid, 'email' => $email]], 201);
}

if ($method === 'PATCH') {
    $u = null;
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$uuid($id)]);
    $u = $st->fetch();
    if (!$u) fail('Compte introuvable.', 404);
    if ($u['role'] === 'admin' && array_key_exists('active', $in)) fail('Le compte administrateur ne se désactive pas ici.', 422);
    if (array_key_exists('active', $in)) {
        $pdo->prepare('UPDATE users SET active = ? WHERE id = ?')->execute([!empty($in['active']) ? 1 : 0, $id]);
    }
    if (array_key_exists('name', $in)) {
        $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([mb_substr(trim((string)$in['name']), 0, 120) ?: null, $id]);
    }
    if (!empty($in['password'])) {
        if (strlen((string)$in['password']) < 10) fail('Mot de passe : 10 caractères minimum.', 422);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash((string)$in['password'], PASSWORD_DEFAULT), $id]);
    }
    json_out(['ok' => true]);
}

if ($method === 'DELETE') {
    $st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $st->execute([$uuid($id)]);
    $role = $st->fetchColumn();
    if ($role === false) fail('Compte introuvable.', 404);
    if ($role === 'admin') fail('Le compte administrateur ne se supprime pas ici.', 422);
    if ($id === $me['id']) fail('Vous ne pouvez pas supprimer votre propre compte.', 422);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    json_out(['ok' => true]);
}

fail('Méthode non autorisée.', 405);
