<?php
// ==========================================================================
// Espace de travail technique partagé (/cowork) — schéma et fonctions.
// Webina et son développeur y suivent les projets côté technique : tâches,
// fiches des sites, bloc-notes commun, messagerie avec fichiers, mises en ligne.
// Aucune donnée commerciale (montants, factures, ventes) n'y transite.
// ==========================================================================

const COWORK_TASK_STATUS = ['todo', 'doing', 'review', 'done'];
const COWORK_PRESENCE_SECONDS = 75;   // au-delà, le collaborateur est considéré hors ligne

function cowork_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    // Le rôle « dev » donne accès à /cowork seulement, jamais à /admin
    $role = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if ($role && stripos((string)$role['Type'], "'dev'") === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','dev') NOT NULL DEFAULT 'user'");
    }
    $ucols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(), 'Field');
    if (!in_array('name', $ucols, true)) $pdo->exec("ALTER TABLE users ADD COLUMN name VARCHAR(120) NULL, ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1, ADD COLUMN last_seen DATETIME NULL");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cowork_tasks (
        id CHAR(36) NOT NULL PRIMARY KEY,
        project_id CHAR(36) NULL,
        title VARCHAR(200) NOT NULL,
        detail TEXT NULL,
        status VARCHAR(8) NOT NULL DEFAULT 'todo',
        assignee CHAR(36) NULL,
        due_on DATE NULL,
        position INT NOT NULL DEFAULT 0,
        created_by CHAR(36) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status, position), INDEX idx_project (project_id)
    ) $o");
    // Bloc-notes commun : un seul document, versionné pour éviter d'écraser l'autre
    $pdo->exec("CREATE TABLE IF NOT EXISTS cowork_notes (
        id TINYINT NOT NULL PRIMARY KEY,
        body MEDIUMTEXT NULL,
        rev INT NOT NULL DEFAULT 0,
        updated_by CHAR(36) NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) $o");
    $pdo->exec("INSERT IGNORE INTO cowork_notes (id, body, rev) VALUES (1, NULL, 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS cowork_messages (
        id CHAR(36) NOT NULL PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        body TEXT NULL,
        file_id CHAR(36) NULL,
        created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
        INDEX idx_created (created_at)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS cowork_files (
        id CHAR(36) NOT NULL PRIMARY KEY,
        filename VARCHAR(200) NOT NULL,
        mime VARCHAR(80) NOT NULL,
        size INT NOT NULL DEFAULT 0,
        stored VARCHAR(80) NOT NULL,
        uploaded_by CHAR(36) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS cowork_deploys (
        id CHAR(36) NOT NULL PRIMARY KEY,
        project_id CHAR(36) NULL,
        label VARCHAR(120) NOT NULL,
        note TEXT NULL,
        env VARCHAR(10) NOT NULL DEFAULT 'prod',
        user_id CHAR(36) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) $o");
}

// Accès à /cowork : l'administrateur et le développeur ; le compte doit être actif
function require_cowork(): array {
    $u = current_user();
    if (!$u) fail('Non authentifié.', 401);
    if (!in_array($u['role'], ['admin', 'dev'], true)) fail('Accès refusé.', 403);
    $st = db()->prepare('SELECT active FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    if ((int)$st->fetchColumn() !== 1) fail('Compte désactivé.', 403);
    return $u;
}

// Qui est là : dernière activité de chaque compte ayant accès à l'espace
function cowork_touch(PDO $pdo, string $uid): void {
    $pdo->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?')->execute([$uid]);
}

function cowork_team(PDO $pdo): array {
    $rows = $pdo->query("SELECT id, email, name, role, last_seen,
            TIMESTAMPDIFF(SECOND, last_seen, NOW()) AS idle
        FROM users WHERE role IN ('admin', 'dev') AND active = 1 ORDER BY role, email")->fetchAll();
    foreach ($rows as &$r) {
        $r['online'] = $r['last_seen'] !== null && (int)$r['idle'] <= COWORK_PRESENCE_SECONDS;
        $r['label'] = $r['name'] ?: explode('@', (string)$r['email'])[0];
        unset($r['idle']);
    }
    return $rows;
}

// Les sites suivis côté technique : jamais de montant, de facture ni de vente
function cowork_projects(PDO $pdo): array {
    $rows = $pdo->query("SELECT p.id, p.name, p.kind, p.status, p.site_status, p.preview_url, p.domain,
            p.domain_expires, p.hosting_renewal, p.ssl_status, p.ssl_expires, p.start_date, p.delivery_date, p.notes,
            c.name AS client, c.company, c.email AS client_email, c.phone AS client_phone,
            (SELECT COUNT(*) FROM cowork_tasks t WHERE t.project_id = p.id AND t.status <> 'done') AS open_tasks
        FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.status <> 'cancelled' ORDER BY FIELD(p.status, 'in_progress', 'delivered', 'paused'), p.created_at DESC")->fetchAll();
    $doms = [];
    foreach ($pdo->query('SELECT project_id, name, expires FROM crm_domains ORDER BY expires IS NULL, expires')->fetchAll() as $d) {
        $doms[$d['project_id']][] = ['name' => $d['name'], 'expires' => $d['expires']];
    }
    foreach ($rows as &$r) {
        $r['open_tasks'] = (int)$r['open_tasks'];
        $r['domains'] = $doms[$r['id']] ?? [];
    }
    return $rows;
}

// Champs techniques qu'un développeur peut corriger lui-même
const COWORK_PROJECT_FIELDS = [
    'site_status' => ['building', 'preview', 'live', 'suspended'],
    'ssl_status' => ['pending', 'active', 'expired', 'none'],
    'preview_url' => 255, 'domain' => 190, 'notes' => 5000,
    'domain_expires' => 'date', 'hosting_renewal' => 'date', 'ssl_expires' => 'date',
];

function cowork_patch_project(PDO $pdo, string $pid, array $in): void {
    foreach ($in as $k => $v) {
        if (!isset(COWORK_PROJECT_FIELDS[$k])) continue;
        $rule = COWORK_PROJECT_FIELDS[$k];
        if (is_array($rule)) {
            if (!in_array($v, $rule, true)) fail("Valeur invalide : $k", 422);
        } elseif ($rule === 'date') {
            $v = trim((string)$v);
            if ($v === '') { $v = null; } else { $t = strtotime($v); if ($t === false) fail("Date invalide : $k", 422); $v = date('Y-m-d', $t); }
        } else {
            $v = mb_substr(trim((string)$v), 0, $rule);
            if ($v === '') $v = null;
        }
        $pdo->prepare("UPDATE crm_projects SET $k = ?, updated_at = NOW() WHERE id = ?")->execute([$v, $pid]);
    }
}

/* ---------- Fichiers partagés dans la messagerie ---------- */
function cowork_file_upload(PDO $pdo, array $f, string $uid): array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) fail('Fichier non reçu.', 422);
    if ($f['size'] > 25 * 1024 * 1024) fail('Fichier trop lourd (25 Mo maximum).', 422);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: 'application/octet-stream';
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!preg_match('/^[a-z0-9]{1,8}$/', $ext)) $ext = 'bin';
    // Pas d'exécutable côté serveur : le fichier est servi par PHP, jamais interprété
    if (in_array($ext, ['php', 'phtml', 'php5', 'php7', 'phar', 'cgi', 'pl', 'py', 'sh'], true)) $ext = 'txt';
    $id = uuid4();
    $stored = 'cw-' . $id . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], crm_files_dir() . '/' . $stored)) fail('Enregistrement impossible.', 500);
    $name = mb_substr(preg_replace('/[^\p{L}\p{N} ._()-]+/u', '_', basename((string)$f['name'])), 0, 200) ?: $stored;
    $pdo->prepare('INSERT INTO cowork_files (id, filename, mime, size, stored, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$id, $name, $mime, (int)$f['size'], $stored, $uid]);
    return ['id' => $id, 'filename' => $name, 'mime' => $mime, 'size' => (int)$f['size']];
}

function cowork_messages(PDO $pdo, string $after = ''): array {
    $sql = 'SELECT m.id, m.user_id, m.body, m.created_at, u.name, u.email,
            f.id AS file_id, f.filename, f.mime, f.size
        FROM cowork_messages m LEFT JOIN users u ON u.id = m.user_id LEFT JOIN cowork_files f ON f.id = m.file_id';
    $args = [];
    if ($after !== '') { $sql .= ' WHERE m.created_at > ?'; $args[] = $after; }
    $sql .= ' ORDER BY m.created_at DESC LIMIT 200';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $rows = array_reverse($st->fetchAll());
    foreach ($rows as &$r) {
        $r['author'] = $r['name'] ?: explode('@', (string)$r['email'])[0];
        $r['size'] = $r['size'] !== null ? (int)$r['size'] : null;
        unset($r['name'], $r['email']);
    }
    return $rows;
}
