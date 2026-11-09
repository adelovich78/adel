<?php
declare(strict_types=1);

// Socle commun : config, PDO, session sécurisée, helpers JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$CONFIG = require __DIR__ . '/config.php';

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $msg, int $code = 400): void {
    json_out(['error' => $msg], $code);
}

// Connexion sans arrêt du script : null si la base est indisponible
function db_or_null(): ?PDO {
    static $tried = false, $pdo = null;
    if (!$tried) {
        $tried = true;
        try { $pdo = db_connect(); } catch (Throwable $e) { error_log('[webina] DB: ' . $e->getMessage()); }
    }
    return $pdo;
}

function db_connect(): PDO {
    global $CONFIG;
    $pdo = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']};charset=utf8mb4",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    ensure_schema($pdo);
    return $pdo;
}

function db(): PDO {
    static $pdo = null;
    global $CONFIG;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']};charset=utf8mb4",
                $CONFIG['db_user'],
                $CONFIG['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Throwable $e) {
            error_log('[webina] DB: ' . $e->getMessage());
            fail('Base de données indisponible.', 500);
        }
        ensure_schema($pdo);
    }
    return $pdo;
}

// Crée les tables au premier appel (équivalent de install.sql)
function ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id CHAR(36) NOT NULL PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','user') NOT NULL DEFAULT 'user',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(40) NULL,
        company VARCHAR(120) NULL,
        sector VARCHAR(60) NULL,
        service VARCHAR(60) NULL,
        budget VARCHAR(60) NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_ip_created (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Migration : colonnes pour les demandes de devis (formulaire /teklif)
    $cols = $pdo->query("SHOW COLUMNS FROM contact_submissions")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kind', $cols, true)) {
        $pdo->exec("ALTER TABLE contact_submissions ADD COLUMN kind VARCHAR(20) NOT NULL DEFAULT 'contact' AFTER id, ADD COLUMN details TEXT NULL AFTER message");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id CHAR(36) NOT NULL PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        lang VARCHAR(5) NOT NULL DEFAULT 'tr',
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Codes de réduction à usage unique (générés depuis /admin)
    $isNew = !$pdo->query("SHOW TABLES LIKE 'promo_codes'")->fetchColumn();
    $pdo->exec("CREATE TABLE IF NOT EXISTS promo_codes (
        id CHAR(36) NOT NULL PRIMARY KEY,
        code VARCHAR(24) NOT NULL UNIQUE,
        note VARCHAR(120) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME NULL,
        used_name VARCHAR(120) NULL,
        used_email VARCHAR(255) NULL,
        INDEX idx_used (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Premiers codes distribués avant l'admin : ajoutés une seule fois, à la création de la table
    if ($isNew) {
        $st = $pdo->prepare("INSERT IGNORE INTO promo_codes (id, code, note) VALUES (?, ?, 'Premiers codes')");
        foreach (['WB-496K-ZZRG', 'WB-5W3S-X8FT', 'WB-M7Z9-GRWF', 'WB-VAZX-W9B8', 'WB-ECRK-PD4N', 'WB-3DQ8-FPHT', 'WB-WJB3-74UP', 'WB-PRUG-CZ3T', 'WB-W2NW-3C24', 'WB-4N4K-754H'] as $c) $st->execute([uuid4(), $c]);
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_time (ip, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function uuid4(): string {
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function client_ip(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

// Limitation de débit par IP et par usage (fichier temporaire, indépendant de la base)
function rate_limit(string $bucket, int $max, int $seconds = 600): void {
    $file = sys_get_temp_dir() . '/webina_rl_' . $bucket . '_' . md5(client_ip());
    $hits = array_filter(json_decode((string)@file_get_contents($file), true) ?: [], fn($t) => $t > time() - $seconds);
    if (count($hits) >= $max) fail('Trop de demandes, réessayez plus tard.', 429);
    $hits[] = time();
    @file_put_contents($file, json_encode(array_values($hits)));
}

function body(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function str_field(array $d, string $k, int $max, bool $required = false): ?string {
    $v = isset($d[$k]) && is_scalar($d[$k]) ? trim((string)$d[$k]) : '';
    if ($v === '') {
        if ($required) fail("Champ requis : $k", 422);
        return null;
    }
    if (mb_strlen($v) > $max) fail("Champ trop long : $k", 422);
    return $v;
}

// Protection CSRF : les requêtes modifiantes doivent venir du front (en-tête custom + même origine)
function require_same_origin(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') return;
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'webina') fail('Requête refusée.', 403);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $host = parse_url($origin, PHP_URL_HOST);
        if ($host !== ($_SERVER['HTTP_HOST'] ?? '') && $host !== explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]) {
            fail('Origine refusée.', 403);
        }
    }
}

function start_session(): void {
    global $CONFIG;
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('webina_admin');
    session_set_cookie_params([
        'lifetime' => $CONFIG['session_lifetime'],
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function current_user(): ?array {
    start_session();
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT id, email, role FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    return $u ?: null;
}

function require_admin(): array {
    $u = current_user();
    if (!$u) fail('Non authentifié.', 401);
    if ($u['role'] !== 'admin') fail('Accès refusé.', 403);
    return $u;
}

function public_user(?array $u): ?array {
    return $u ? ['id' => $u['id'], 'email' => $u['email'], 'isAdmin' => $u['role'] === 'admin'] : null;
}

require_same_origin();
