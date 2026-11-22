<?php
// Mini-CRM Webina : schéma, outils communs et automatisations
// (remplace EspoCRM : clients, ventes, tâches, projets, paiements, historique)

const CRM_STAGES = ['new', 'contacted', 'proposal', 'won', 'lost'];
const CRM_PROJECT_STATUS = ['in_progress', 'delivered', 'paused', 'cancelled'];
const CRM_TASK_KINDS = ['todo', 'call', 'meeting', 'email'];
const CRM_EXPENSE_CATEGORIES = ['ads', 'domain', 'hosting', 'software', 'freelance', 'salary', 'office', 'equipment', 'travel', 'bank', 'tax', 'other'];
const CRM_AD_PLATFORMS = ['instagram', 'facebook', 'youtube', 'google', 'tiktok', 'linkedin'];
const CRM_RECURRENCES = ['none', 'monthly', 'quarterly', 'yearly'];

// Délais des relances automatiques (jours)
const CRM_FOLLOWUP_DAYS = 5;      // vente sans nouvelle depuis X jours → tâche « Relancer »
const CRM_RENEWAL_NOTICE = 30;    // renouvellement d'hébergement dans X jours → tâche

function crm_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_clients (
        id CHAR(36) NOT NULL PRIMARY KEY,
        kind VARCHAR(10) NOT NULL DEFAULT 'person',
        parent_id CHAR(36) NULL,
        name VARCHAR(160) NOT NULL,
        company VARCHAR(160) NULL,
        email VARCHAR(255) NULL,
        phone VARCHAR(60) NULL,
        sector VARCHAR(80) NULL,
        city VARCHAR(80) NULL,
        website VARCHAR(255) NULL,
        source VARCHAR(40) NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'lead',
        notes TEXT NULL,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_activity_at DATETIME NULL,
        INDEX idx_email (email), INDEX idx_status (status), INDEX idx_parent (parent_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_deals (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        title VARCHAR(200) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        stage VARCHAR(12) NOT NULL DEFAULT 'new',
        submission_id CHAR(36) NULL,
        close_date DATE NULL,
        notes TEXT NULL,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        stage_changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id), INDEX idx_stage (stage)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_tasks (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NULL,
        deal_id CHAR(36) NULL,
        project_id CHAR(36) NULL,
        title VARCHAR(200) NOT NULL,
        kind VARCHAR(10) NOT NULL DEFAULT 'todo',
        due_at DATETIME NULL,
        done_at DATETIME NULL,
        notes TEXT NULL,
        auto_key VARCHAR(120) NULL UNIQUE,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_due (done_at, due_at), INDEX idx_client (client_id), INDEX idx_deal (deal_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_projects (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        deal_id CHAR(36) NULL UNIQUE,
        name VARCHAR(200) NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'in_progress',
        start_date DATE NULL,
        delivery_date DATE NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        hosting_renewal DATE NULL,
        hosting_amount DECIMAL(12,2) NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id), INDEX idx_renewal (hosting_renewal)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_payments (
        id CHAR(36) NOT NULL PRIMARY KEY,
        project_id CHAR(36) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        paid_at DATE NOT NULL,
        method VARCHAR(40) NULL,
        note VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_project (project_id)
    ) $o");
    // Dépenses : une ligne par dépense. « recurrence » + « next_due » = modèle d'abonnement
    // qui recrée la dépense à chaque échéance (cron quotidien).
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_expenses (
        id CHAR(36) NOT NULL PRIMARY KEY,
        category VARCHAR(20) NOT NULL,
        platform VARCHAR(12) NULL,
        label VARCHAR(160) NOT NULL,
        supplier VARCHAR(120) NULL,
        client_id CHAR(36) NULL,
        spent_on DATE NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        impressions INT NULL,
        clicks INT NULL,
        leads INT NULL,
        recurrence VARCHAR(10) NOT NULL DEFAULT 'none',
        next_due DATE NULL,
        parent_id CHAR(36) NULL,
        note VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (spent_on), INDEX idx_cat (category), INDEX idx_due (recurrence, next_due),
        UNIQUE KEY uq_occurrence (parent_id, spent_on)
    ) $o");
    // ---------- Espace client (my.webina.com.tr) ----------
    // Un projet = un service suivi par le client : domaine, hébergement, abonnement, statut du site et SSL
    $pcols = array_column($pdo->query('SHOW COLUMNS FROM crm_projects')->fetchAll(), 'Field');
    if (!in_array('domain', $pcols, true)) {
        $pdo->exec("ALTER TABLE crm_projects ADD COLUMN domain VARCHAR(190) NULL, ADD COLUMN plan VARCHAR(20) NULL,
            ADD COLUMN site_status VARCHAR(12) NOT NULL DEFAULT 'building', ADD COLUMN preview_url VARCHAR(255) NULL,
            ADD COLUMN domain_expires DATE NULL, ADD COLUMN ssl_status VARCHAR(10) NOT NULL DEFAULT 'pending', ADD COLUMN ssl_expires DATE NULL");
    }
    $ccols = array_column($pdo->query('SHOW COLUMNS FROM crm_clients')->fetchAll(), 'Field');
    if (!in_array('portal_enabled', $ccols, true)) {
        $pdo->exec("ALTER TABLE crm_clients ADD COLUMN portal_enabled TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN portal_last_login DATETIME NULL, ADD COLUMN lang VARCHAR(5) NULL");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_invoices (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        project_id CHAR(36) NULL,
        number VARCHAR(20) NOT NULL UNIQUE,
        label VARCHAR(200) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'TRY',
        issued_on DATE NOT NULL,
        due_on DATE NOT NULL,
        paid_at DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id), INDEX idx_due (paid_at, due_on)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_notices (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NULL,
        level VARCHAR(10) NOT NULL DEFAULT 'info',
        title VARCHAR(160) NOT NULL,
        body TEXT NULL,
        expires_on DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_requests (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        project_id CHAR(36) NULL,
        kind VARCHAR(12) NOT NULL,
        detail TEXT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        done_at DATETIME NULL,
        INDEX idx_status (status, created_at), INDEX idx_client (client_id)
    ) $o");
    // Rappels d'échéance envoyés au client (dédup : un envoi par étape et par date d'échéance)
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_reminders (
        id CHAR(36) NOT NULL PRIMARY KEY,
        rkey VARCHAR(190) NOT NULL UNIQUE,
        project_id CHAR(36) NOT NULL,
        kind VARCHAR(10) NOT NULL,
        stage VARCHAR(16) NOT NULL,
        sent_on DATE NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_project (project_id)
    ) $o");
    // Fil de discussion d'une demande : le client et Webina échangent jusqu'à résolution
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_messages (
        id CHAR(36) NOT NULL PRIMARY KEY,
        request_id CHAR(36) NOT NULL,
        client_id CHAR(36) NOT NULL,
        sender VARCHAR(8) NOT NULL DEFAULT 'client',
        body TEXT NOT NULL,
        read_by_client TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
        INDEX idx_request (request_id, created_at)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_tokens (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        kind VARCHAR(6) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        attempts TINYINT NOT NULL DEFAULT 0,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_hash (token_hash), INDEX idx_client (client_id, kind)
    ) $o");
    // Documents d'un client (contrats, devis signés…) : fichiers rangés hors du dossier public
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_documents (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        project_id CHAR(36) NULL,
        kind VARCHAR(12) NOT NULL DEFAULT 'contract',
        title VARCHAR(200) NOT NULL,
        filename VARCHAR(200) NOT NULL,
        mime VARCHAR(80) NOT NULL,
        size INT NOT NULL DEFAULT 0,
        stored VARCHAR(80) NOT NULL,
        visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id, created_at)
    ) $o");
    // Documents envoyés par le client lui-même + note qui les accompagne
    $dcols = array_column($pdo->query('SHOW COLUMNS FROM crm_documents')->fetchAll(), 'Field');
    if (!in_array('source', $dcols, true)) {
        $pdo->exec("ALTER TABLE crm_documents ADD COLUMN source VARCHAR(8) NOT NULL DEFAULT 'webina', ADD COLUMN note TEXT NULL");
    }
    // Plusieurs fichiers par message : liste JSON des documents joints
    $ccols2 = $pdo->query("SHOW TABLES LIKE 'portal_chat'")->fetchColumn() ? array_column($pdo->query('SHOW COLUMNS FROM portal_chat')->fetchAll(), 'Field') : [];
    if ($ccols2 && !in_array('doc_ids', $ccols2, true)) $pdo->exec('ALTER TABLE portal_chat ADD COLUMN doc_ids TEXT NULL');
    // Messagerie Webina ⇄ client : messages, propositions à valider (logo, textes…), fichiers joints
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_chat (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NOT NULL,
        sender VARCHAR(8) NOT NULL,
        kind VARCHAR(10) NOT NULL DEFAULT 'message',
        body TEXT NULL,
        doc_id CHAR(36) NULL,
        doc_ids TEXT NULL,
        status VARCHAR(10) NULL,
        reply_to CHAR(36) NULL,
        read_by_client TINYINT(1) NOT NULL DEFAULT 0,
        read_by_admin TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
        INDEX idx_client (client_id, created_at)
    ) $o");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_activity (
        id CHAR(36) NOT NULL PRIMARY KEY,
        client_id CHAR(36) NULL,
        deal_id CHAR(36) NULL,
        kind VARCHAR(20) NOT NULL DEFAULT 'note',
        text TEXT NOT NULL,
        espo_id VARCHAR(40) NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id, created_at)
    ) $o");
}

function crm_db(): PDO {
    $pdo = db();
    crm_schema($pdo);
    return $pdo;
}

// Historique : chaque événement met aussi à jour la « dernière activité » du client
function crm_log(PDO $pdo, ?string $clientId, ?string $dealId, string $kind, string $text, ?string $at = null): void {
    $pdo->prepare('INSERT INTO crm_activity (id, client_id, deal_id, kind, text, created_at) VALUES (?, ?, ?, ?, ?, COALESCE(?, NOW()))')
        ->execute([uuid4(), $clientId, $dealId, $kind, mb_substr($text, 0, 5000), $at]);
    if ($clientId) $pdo->prepare('UPDATE crm_clients SET last_activity_at = NOW() WHERE id = ?')->execute([$clientId]);
}

// Tâche automatique : la clé évite de créer deux fois la même relance
function crm_auto_task(PDO $pdo, string $key, string $title, string $due, ?string $clientId, ?string $dealId = null, ?string $projectId = null, string $kind = 'todo'): bool {
    $st = $pdo->prepare('INSERT IGNORE INTO crm_tasks (id, client_id, deal_id, project_id, title, kind, due_at, auto_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $st->execute([uuid4(), $clientId, $dealId, $projectId, mb_substr($title, 0, 200), $kind, $due, $key]);
    return $st->rowCount() === 1;
}

// Prochain jour ouvré à 10 h (la demande arrive le vendredi soir → lundi)
function crm_next_workday(int $days = 1): string {
    $t = strtotime("+$days day");
    while ((int)date('N', $t) >= 6) $t = strtotime('+1 day', $t);
    return date('Y-m-d 10:00:00', $t);
}

/* ---------- Automatisation 1 : chaque demande du site entre dans le CRM ---------- */
function crm_ingest_submission(PDO $pdo, array $s): void {
    try {
        crm_schema($pdo);
        $email = strtolower(trim((string)($s['email'] ?? '')));
        $st = $pdo->prepare("SELECT id, status FROM crm_clients WHERE LOWER(email) = ? AND status <> 'archived' ORDER BY created_at LIMIT 1");
        $st->execute([$email]);
        $client = $st->fetch();
        $isQuote = ($s['kind'] ?? '') === 'quote';
        if (!$client) {
            $cid = uuid4();
            $pdo->prepare("INSERT INTO crm_clients (id, kind, name, company, email, phone, sector, city, source, status) VALUES (?, 'person', ?, ?, ?, ?, ?, ?, ?, 'lead')")
                ->execute([$cid, $s['name'], $s['company'] ?: null, $email, $s['phone'] ?: null, $s['sector'] ?: null, $s['city'] ?: null, $isQuote ? 'Devis en ligne' : 'Formulaire de contact']);
        } else {
            $cid = $client['id'];
            // Complète la fiche sans écraser ce qui existe déjà
            $pdo->prepare('UPDATE crm_clients SET phone = COALESCE(phone, ?), company = COALESCE(company, ?), sector = COALESCE(sector, ?), city = COALESCE(city, ?), updated_at = NOW() WHERE id = ?')
                ->execute([$s['phone'] ?: null, $s['company'] ?: null, $s['sector'] ?: null, $s['city'] ?: null, $cid]);
        }
        $title = $isQuote ? 'Site web — devis en ligne' : ('Demande : ' . ($s['service'] ?: 'contact'));
        $did = uuid4();
        $pdo->prepare("INSERT INTO crm_deals (id, client_id, title, amount, currency, stage, submission_id, notes) VALUES (?, ?, ?, ?, ?, 'new', ?, ?)")
            ->execute([$did, $cid, $title, (float)($s['amount'] ?? 0), $s['currency'] ?? 'TRY', $s['submission_id'] ?? null, $s['budget'] ? 'Budget indiqué : ' . $s['budget'] : null]);
        $msg = $isQuote ? 'Devis en ligne reçu' . (!empty($s['amount']) ? ' : ' . money((int)$s['amount'], $s['currency'] ?? 'TRY') : '') . (!empty($s['promo']) ? ' (code ' . $s['promo'] . ')' : '')
                        : 'Message reçu via le formulaire de contact';
        crm_log($pdo, $cid, $did, 'submission', $msg . (trim((string)($s['message'] ?? '')) !== '' ? "\n\n" . $s['message'] : ''));
        crm_auto_task($pdo, 'first-contact:' . $did, 'Rappeler ' . $s['name'], crm_next_workday(), $cid, $did, null, 'call');
    } catch (Throwable $e) {
        error_log('[webina] CRM ingest: ' . $e->getMessage());
    }
}

/* ---------- Automatisation 2 : changement d'étape d'une vente ---------- */
function crm_on_stage_change(PDO $pdo, array $deal, string $from, string $to): void {
    $labels = ['new' => 'Nouveau', 'contacted' => 'Contacté', 'proposal' => 'Proposition envoyée', 'won' => 'Client réel (gagné)', 'lost' => 'Abandonné'];
    crm_log($pdo, $deal['client_id'], $deal['id'], 'stage', 'Étape : ' . ($labels[$from] ?? $from) . ' → ' . ($labels[$to] ?? $to));
    if ($to === 'won') {
        $pdo->prepare("UPDATE crm_clients SET status = 'client', updated_at = NOW() WHERE id = ?")->execute([$deal['client_id']]);
        // Projet déjà créé à la main pour ce service (même nom, sans vente liée) : on le rattache au lieu d'en créer un second
        $st = $pdo->prepare('SELECT id FROM crm_projects WHERE client_id = ? AND deal_id IS NULL AND LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
        $st->execute([$deal['client_id'], $deal['title']]);
        if ($existing = $st->fetchColumn()) {
            $pdo->prepare('UPDATE crm_projects SET deal_id = ?, amount = ?, updated_at = NOW() WHERE id = ?')->execute([$deal['id'], $deal['amount'], $existing]);
            crm_log($pdo, $deal['client_id'], $deal['id'], 'project', 'Vente rattachée au projet existant « ' . $deal['title'] . ' »');
        }
        // Sinon, projet créé automatiquement à partir de la vente gagnée
        $pid = uuid4();
        if (!$existing) {
            $st = $pdo->prepare("INSERT IGNORE INTO crm_projects (id, client_id, deal_id, name, status, start_date, amount, currency, hosting_renewal, hosting_amount)
                VALUES (?, ?, ?, ?, 'in_progress', CURDATE(), ?, ?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)");
            $st->execute([$pid, $deal['client_id'], $deal['id'], $deal['title'], $deal['amount'], $deal['currency'], crm_renewal_price($deal['currency'])]);
            if ($st->rowCount()) {
                crm_log($pdo, $deal['client_id'], $deal['id'], 'project', 'Projet créé : ' . $deal['title']);
                crm_auto_task($pdo, 'kickoff:' . $deal['id'], 'Lancer le projet « ' . $deal['title'] . ' »', crm_next_workday(), $deal['client_id'], $deal['id'], $pid);
            }
        }
    }
    // La demande du site liée à cette vente suit le même avancement
    if (!empty($deal['submission_id'])) {
        $sub = in_array($to, ['won', 'lost'], true) ? 'done' : (in_array($to, ['contacted', 'proposal'], true) ? 'in_progress' : 'new');
        try { $pdo->prepare('UPDATE contact_submissions SET status = ? WHERE id = ?')->execute([$sub, $deal['submission_id']]); } catch (Throwable $e) {}
    }
    if (in_array($to, ['won', 'lost'], true)) {
        // Les relances automatiques ouvertes n'ont plus lieu d'être
        $pdo->prepare("UPDATE crm_tasks SET done_at = NOW() WHERE deal_id = ? AND done_at IS NULL AND (auto_key LIKE 'followup:%' OR auto_key LIKE 'first-contact:%')")->execute([$deal['id']]);
    }
}

// Prix du renouvellement d'hébergement depuis le barème du site
function crm_renewal_price(string $currency): ?float {
    $file = dirname(__DIR__) . '/pricing.json';
    $p = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    if (!is_array($p) || empty($p['prices']['renewalYearly'])) return null;
    $per = (float)($p['tlPer'][$currency] ?? 1);
    $step = (float)($p['rounding'][$currency] ?? 1);
    return round(((float)$p['prices']['renewalYearly'] / $per) / $step) * $step;
}

/* ---------- Automatisation 3 : passage quotidien (cron) ---------- */
function crm_daily(PDO $pdo): array {
    crm_schema($pdo);
    $created = ['followup' => 0, 'renewal' => 0, 'balance' => 0];

    // Ventes en cours sans nouvelle depuis X jours → « Relancer »
    $st = $pdo->prepare("SELECT d.id, d.title, d.client_id, c.name FROM crm_deals d JOIN crm_clients c ON c.id = d.client_id
        WHERE d.stage IN ('new', 'contacted', 'proposal')
          AND GREATEST(d.stage_changed_at, COALESCE(c.last_activity_at, d.created_at)) < (NOW() - INTERVAL " . CRM_FOLLOWUP_DAYS . " DAY)
          AND NOT EXISTS (SELECT 1 FROM crm_tasks t WHERE t.deal_id = d.id AND t.done_at IS NULL)");
    $st->execute();
    foreach ($st->fetchAll() as $d) {
        if (crm_auto_task($pdo, 'followup:' . $d['id'] . ':' . date('Y-m-d'), 'Relancer ' . $d['name'] . ' — ' . $d['title'], date('Y-m-d 10:00:00'), $d['client_id'], $d['id'], null, 'call')) {
            $created['followup']++;
            crm_log($pdo, $d['client_id'], $d['id'], 'auto', 'Relance automatique programmée (sans nouvelle depuis ' . CRM_FOLLOWUP_DAYS . ' jours)');
        }
    }

    // Renouvellements d'hébergement à venir
    $st = $pdo->prepare("SELECT p.id, p.name, p.client_id, p.hosting_renewal, c.name AS client FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.hosting_renewal IS NOT NULL AND p.status <> 'cancelled' AND p.hosting_renewal <= (CURDATE() + INTERVAL " . CRM_RENEWAL_NOTICE . " DAY)");
    $st->execute();
    foreach ($st->fetchAll() as $p) {
        if (crm_auto_task($pdo, 'renewal:' . $p['id'] . ':' . $p['hosting_renewal'], 'Renouvellement hébergement ' . $p['client'] . ' (' . date('d/m/Y', strtotime($p['hosting_renewal'])) . ')', date('Y-m-d 10:00:00', strtotime($p['hosting_renewal'] . ' -7 day') < time() ? time() : strtotime($p['hosting_renewal'] . ' -7 day')), $p['client_id'], null, $p['id'], 'email')) {
            $created['renewal']++;
        }
    }

    // Projets livrés avec un solde à encaisser
    $st = $pdo->query("SELECT p.id, p.name, p.client_id, p.amount, p.currency, c.name AS client,
            (SELECT COALESCE(SUM(amount), 0) FROM crm_payments WHERE project_id = p.id) AS paid
        FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id WHERE p.status = 'delivered'
          AND NOT EXISTS (SELECT 1 FROM crm_deals d WHERE d.id = p.deal_id AND d.espo_id IS NOT NULL)");
    foreach ($st->fetchAll() as $p) {
        $rest = (float)$p['amount'] - (float)$p['paid'];
        if ($rest > 0 && crm_auto_task($pdo, 'balance:' . $p['id'] . ':' . date('Y-W'), 'Encaisser le solde ' . money((int)$rest, $p['currency']) . ' — ' . $p['client'], date('Y-m-d 10:00:00'), $p['client_id'], null, $p['id'])) {
            $created['balance']++;
        }
    }
    // Dépenses récurrentes : la dépense de l'échéance est ajoutée automatiquement
    $created['expenses'] = 0;
    $step = ['monthly' => '+1 month', 'quarterly' => '+3 month', 'yearly' => '+1 year'];
    $due = $pdo->query("SELECT * FROM crm_expenses WHERE recurrence <> 'none' AND next_due IS NOT NULL AND next_due <= CURDATE()")->fetchAll();
    $ins = $pdo->prepare("INSERT IGNORE INTO crm_expenses (id, category, platform, label, supplier, client_id, spent_on, amount, currency, parent_id, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ajoutée automatiquement (abonnement)')");
    foreach ($due as $e) {
        $next = $e['next_due'];
        $guard = 0;
        while ($next <= date('Y-m-d') && $guard++ < 36) {
            $ins->execute([uuid4(), $e['category'], $e['platform'], $e['label'], $e['supplier'], $e['client_id'], $next, $e['amount'], $e['currency'], $e['id']]);
            $created['expenses'] += $ins->rowCount();
            $next = date('Y-m-d', strtotime($step[$e['recurrence']] ?? '+1 month', strtotime($next)));
        }
        $pdo->prepare('UPDATE crm_expenses SET next_due = ? WHERE id = ?')->execute([$next, $e['id']]);
    }
    // Domaines et hébergements annuels : rappel 14 jours avant le renouvellement
    $soon = $pdo->query("SELECT * FROM crm_expenses WHERE recurrence = 'yearly' AND category IN ('domain', 'hosting')
        AND next_due BETWEEN CURDATE() AND (CURDATE() + INTERVAL 14 DAY)")->fetchAll();
    foreach ($soon as $e) {
        crm_auto_task($pdo, 'expense:' . $e['id'] . ':' . $e['next_due'], 'Renouveler ' . $e['label'] . ' (' . date('d/m/Y', strtotime($e['next_due'])) . ', ' . money((int)$e['amount'], $e['currency']) . ')',
            date('Y-m-d 10:00:00'), $e['client_id'], null, null, 'todo');
    }
    return $created;
}

// Résumé du jour envoyé par e-mail (tâches en retard / du jour, nouveaux prospects, renouvellements)
function crm_digest_html(PDO $pdo): ?string {
    $tasks = $pdo->query("SELECT t.title, t.due_at, c.name FROM crm_tasks t LEFT JOIN crm_clients c ON c.id = t.client_id
        WHERE t.done_at IS NULL AND t.due_at < (CURDATE() + INTERVAL 1 DAY) ORDER BY t.due_at")->fetchAll();
    $leads = $pdo->query("SELECT name, email, source FROM crm_clients WHERE created_at >= (NOW() - INTERVAL 1 DAY) ORDER BY created_at DESC")->fetchAll();
    $renew = $pdo->query("SELECT p.name, p.hosting_renewal, c.name AS client FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.hosting_renewal BETWEEN CURDATE() AND (CURDATE() + INTERVAL " . CRM_RENEWAL_NOTICE . " DAY) AND p.status <> 'cancelled' ORDER BY p.hosting_renewal")->fetchAll();
    if (!$tasks && !$leads && !$renew) return null;
    $row = fn($a, $b) => '<tr><td style="padding:8px 0;border-bottom:1px solid #eee">' . $a . '</td><td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;color:#666;white-space:nowrap">' . $b . '</td></tr>';
    $html = '<h2 style="margin:0 0 4px;font-size:20px">Votre journée</h2><p style="margin:0 0 20px;color:#666;font-size:13px">' . date('d/m/Y') . '</p>';
    if ($tasks) {
        $html .= '<h3 style="font-size:15px;margin:18px 0 6px">À faire (' . count($tasks) . ')</h3><table width="100%" style="font-size:14px">';
        foreach ($tasks as $t) $html .= $row(h($t['title']) . ($t['name'] ? ' <span style="color:#888">· ' . h($t['name']) . '</span>' : ''), strtotime($t['due_at']) < strtotime('today') ? '<b style="color:#c4321f">en retard</b>' : date('H:i', strtotime($t['due_at'])));
        $html .= '</table>';
    }
    if ($leads) {
        $html .= '<h3 style="font-size:15px;margin:18px 0 6px">Nouveaux prospects (' . count($leads) . ')</h3><table width="100%" style="font-size:14px">';
        foreach ($leads as $l) $html .= $row(h($l['name']) . ' <span style="color:#888">· ' . h((string)$l['email']) . '</span>', h((string)$l['source']));
        $html .= '</table>';
    }
    if ($renew) {
        $html .= '<h3 style="font-size:15px;margin:18px 0 6px">Renouvellements à venir</h3><table width="100%" style="font-size:14px">';
        foreach ($renew as $r) $html .= $row(h($r['client']) . ' <span style="color:#888">· ' . h($r['name']) . '</span>', date('d/m/Y', strtotime($r['hosting_renewal'])));
        $html .= '</table>';
    }
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? ($GLOBALS['CONFIG']['website'] ?? 'webina.com.tr'));
    return $html . '<p style="margin-top:24px"><a href="https://' . h($host) . '/admin" style="background:#23772a;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:700">Ouvrir le CRM</a></p>';
}

/* ---------- Rappels d'échéance au client (domaine + hébergement) ----------
   Étapes : J-30 (une fois), J-15 (une fois), puis chaque jour à partir de J-7 et au-delà
   (retard), jusqu'à ce que la date soit repoussée (renouvellement) ou le projet annulé. */
function crm_client_reminders(PDO $pdo): int {
    crm_schema($pdo);
    $sent = 0;
    $cols = ['hosting' => 'hosting_renewal', 'domain' => 'domain_expires'];
    foreach ($cols as $kind => $col) {
        $rows = $pdo->query("SELECT p.id, p.$col AS expiry, p.name, p.domain, p.plan, p.hosting_amount, p.currency,
                c.id AS client_id, c.name AS client, c.email, c.lang
            FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
            WHERE p.$col IS NOT NULL AND p.status <> 'cancelled'
              AND p.$col <= (CURDATE() + INTERVAL 30 DAY)")->fetchAll();
        foreach ($rows as $r) {
            if (!filter_var((string)$r['email'], FILTER_VALIDATE_EMAIL)) continue;
            $days = (int)floor((strtotime($r['expiry']) - strtotime('today')) / 86400);
            if ($days <= 7) $stage = 'daily:' . date('Y-m-d');  // J-7, J0 et retard : tous les jours
            elseif ($days <= 15) $stage = 'd15';
            elseif ($days <= 30) $stage = 'm1';
            else continue;
            $rkey = 'rem:' . $r['id'] . ':' . $kind . ':' . $r['expiry'] . ':' . $stage;
            $ins = $pdo->prepare('INSERT IGNORE INTO crm_reminders (id, rkey, project_id, kind, stage, sent_on) VALUES (?, ?, ?, ?, ?, CURDATE())');
            $ins->execute([uuid4(), $rkey, $r['id'], $kind, $stage]);
            if ($ins->rowCount() !== 1) continue;  // déjà envoyé pour cette étape / ce jour
            if (crm_send_client_reminder($r, $kind, $days)) {
                $sent++;
                crm_log($pdo, $r['client_id'], null, 'auto', ($kind === 'hosting' ? 'Hébergement' : 'Domaine') . ' — rappel envoyé au client ('
                    . ($days < 0 ? abs($days) . ' j de retard' : ($days === 0 ? 'échéance aujourd\'hui' : 'J-' . $days)) . ')');
            } else {
                // Envoi échoué : on retire la trace pour réessayer au prochain passage
                $pdo->prepare('DELETE FROM crm_reminders WHERE rkey = ?')->execute([$rkey]);
            }
        }
    }
    return $sent;
}

// E-mail de rappel d'échéance envoyé au client, dans sa langue
function crm_send_client_reminder(array $r, string $kind, int $days): bool {
    global $CONFIG;
    $lang = in_array($r['lang'] ?? '', ['tr', 'en', 'fr', 'ar'], true) ? $r['lang'] : 'tr';
    $date = date('d/m/Y', strtotime($r['expiry']));
    $svc = $r['domain'] ?: $r['name'];
    $late = $days < 0;
    $L = [
        'tr' => [
            'subj_h' => 'Hosting yenileme hatırlatması', 'subj_d' => 'Alan adı yenileme hatırlatması',
            'head_h' => 'Hosting süreniz yakında doluyor', 'head_d' => 'Alan adınızın süresi yakında doluyor',
            'head_h_late' => 'Hosting süreniz doldu', 'head_d_late' => 'Alan adınızın süresi doldu',
            'when' => $late ? ($date . ' tarihinde doldu (' . abs($days) . ' gün önce)') : ($days === 0 ? 'Bugün doluyor (' . $date . ')' : $date . ' tarihinde doluyor (' . $days . ' gün kaldı)'),
            'warn' => $late ? 'Siteniz ve e-postalarınızın kesintiye uğramaması için lütfen en kısa sürede yenileyin.' : 'Kesinti yaşamamak için lütfen süresi dolmadan yenileyin.',
            'how' => 'Yenilemek için:', 'portal' => 'Müşteri panelinizden yenileyin', 'wa' => 'WhatsApp ile yazın', 'bank' => 'Banka havalesi',
        ],
        'en' => [
            'subj_h' => 'Hosting renewal reminder', 'subj_d' => 'Domain renewal reminder',
            'head_h' => 'Your hosting is about to expire', 'head_d' => 'Your domain is about to expire',
            'head_h_late' => 'Your hosting has expired', 'head_d_late' => 'Your domain has expired',
            'when' => $late ? ('expired on ' . $date . ' (' . abs($days) . ' days ago)') : ($days === 0 ? 'expires today (' . $date . ')' : 'expires on ' . $date . ' (' . $days . ' days left)'),
            'warn' => $late ? 'Please renew as soon as possible so your site and emails are not interrupted.' : 'Please renew before it expires to avoid any interruption.',
            'how' => 'To renew:', 'portal' => 'Renew from your client area', 'wa' => 'Message us on WhatsApp', 'bank' => 'Bank transfer',
        ],
        'fr' => [
            'subj_h' => 'Rappel : renouvellement de l’hébergement', 'subj_d' => 'Rappel : renouvellement du nom de domaine',
            'head_h' => 'Votre hébergement arrive à échéance', 'head_d' => 'Votre nom de domaine arrive à échéance',
            'head_h_late' => 'Votre hébergement a expiré', 'head_d_late' => 'Votre nom de domaine a expiré',
            'when' => $late ? ('a expiré le ' . $date . ' (il y a ' . abs($days) . ' jours)') : ($days === 0 ? 'expire aujourd’hui (' . $date . ')' : 'expire le ' . $date . ' (' . $days . ' jours restants)'),
            'warn' => $late ? 'Merci de renouveler au plus vite pour que votre site et vos e-mails ne soient pas interrompus.' : 'Merci de renouveler avant l’échéance pour éviter toute interruption.',
            'how' => 'Pour renouveler :', 'portal' => 'Renouveler depuis votre espace client', 'wa' => 'Nous écrire sur WhatsApp', 'bank' => 'Virement bancaire',
        ],
        'ar' => [
            'subj_h' => 'تذكير بتجديد الاستضافة', 'subj_d' => 'تذكير بتجديد النطاق',
            'head_h' => 'اقترب انتهاء استضافتك', 'head_d' => 'اقترب انتهاء نطاقك',
            'head_h_late' => 'انتهت استضافتك', 'head_d_late' => 'انتهى نطاقك',
            'when' => $late ? ('انتهى في ' . $date . ' (منذ ' . abs($days) . ' يوماً)') : ($days === 0 ? 'ينتهي اليوم (' . $date . ')' : 'ينتهي في ' . $date . ' (باقٍ ' . $days . ' يوماً)'),
            'warn' => $late ? 'يرجى التجديد في أقرب وقت حتى لا ينقطع موقعك وبريدك.' : 'يرجى التجديد قبل انتهاء المدة لتجنّب أي انقطاع.',
            'how' => 'للتجديد:', 'portal' => 'جدّد من منطقة العملاء', 'wa' => 'راسلنا على واتساب', 'bank' => 'حوالة بنكية',
        ],
    ][$lang];
    $head = $late ? $L['head_' . ($kind === 'domain' ? 'd' : 'h') . '_late'] : $L['head_' . ($kind === 'domain' ? 'd' : 'h')];
    $subj = $L['subj_' . ($kind === 'domain' ? 'd' : 'h')];
    $wa = preg_replace('/\D/', '', (string)($CONFIG['whatsapp_site'] ?? $CONFIG['whatsapp'] ?? '905399662557'));
    $portal = function_exists('portal_url') ? portal_url() : 'https://my.webina.com.tr';
    $amount = $kind === 'hosting' && $r['hosting_amount'] !== null ? ' <span style="color:#555">(' . h(money((int)$r['hosting_amount'], $r['currency'])) . ' / ' . ($lang === 'fr' ? 'an' : ($lang === 'en' ? 'year' : ($lang === 'ar' ? 'سنة' : 'yıl'))) . ')</span>' : '';
    $bank = array_filter(['name' => $CONFIG['bank_name'] ?? null, 'iban' => $CONFIG['bank_iban'] ?? null, 'holder' => $CONFIG['bank_holder'] ?? null]);
    $html = '<h2 style="margin:0 0 10px;font-size:20px">' . h($head) . '</h2>'
        . '<p style="margin:0 0 6px"><b>' . h($svc) . '</b>' . $amount . '</p>'
        . '<p style="margin:0 0 16px;' . ($late ? 'color:#c4321f;font-weight:600' : 'color:#555') . '">' . h($L['when']) . '</p>'
        . '<p style="margin:0 0 18px">' . h($L['warn']) . '</p>'
        . '<p style="margin:0 0 6px;font-weight:600">' . h($L['how']) . '</p><ul style="margin:0 0 16px;padding-inline-start:18px;color:#333">'
        . '<li><a href="' . h($portal) . '" style="color:#1f6f2a">' . h($L['portal']) . '</a></li>'
        . '<li><a href="https://wa.me/' . h($wa) . '" style="color:#1f6f2a">' . h($L['wa']) . '</a></li>'
        . (!empty($bank['iban']) ? '<li>' . h($L['bank']) . ' — ' . (isset($bank['holder']) ? h($bank['holder']) . ' · ' : '') . '<b dir="ltr">' . h($bank['iban']) . '</b></li>' : '')
        . '</ul>';
    return send_html_mail((string)$r['email'], $subj . ' — ' . $svc, mail_layout($html, $lang === 'ar'));
}

/* ---------- Compte rendu mensuel : situation de tous les clients (admin) ---------- */
function crm_monthly_html(PDO $pdo): ?string {
    crm_schema($pdo);
    $rows = $pdo->query("SELECT p.name, p.domain, p.status, p.hosting_renewal, p.domain_expires, c.name AS client
        FROM crm_projects p JOIN crm_clients c ON c.id = p.client_id
        WHERE p.status <> 'cancelled' AND (p.hosting_renewal IS NOT NULL OR p.domain_expires IS NOT NULL)
        ORDER BY LEAST(COALESCE(p.hosting_renewal, '9999-12-31'), COALESCE(p.domain_expires, '9999-12-31'))")->fetchAll();
    $unpaid = (int)($pdo->query("SELECT COUNT(*) n FROM crm_invoices WHERE paid_at IS NULL")->fetch()['n'] ?? 0);
    if (!$rows && !$unpaid) return null;
    $today = strtotime('today');
    $fmt = function ($d) use ($today) {
        if (!$d) return '<span style="color:#aaa">—</span>';
        $days = (int)floor((strtotime($d) - $today) / 86400);
        $col = $days < 0 ? '#c4321f' : ($days <= 30 ? '#b45309' : '#555');
        $tag = $days < 0 ? ' (' . abs($days) . 'j retard)' : ($days <= 30 ? ' (J-' . $days . ')' : '');
        return '<span style="color:' . $col . '">' . date('d/m/Y', strtotime($d)) . $tag . '</span>';
    };
    $html = '<h2 style="margin:0 0 4px;font-size:20px">Compte rendu mensuel</h2>'
        . '<p style="margin:0 0 20px;color:#666;font-size:13px">' . date('F Y') . ' · ' . count($rows) . ' service(s) suivi(s)'
        . ($unpaid ? ' · ' . $unpaid . ' facture(s) impayée(s)' : '') . '</p>'
        . '<table width="100%" style="font-size:14px;border-collapse:collapse"><tr style="text-align:left;color:#888;font-size:12px">'
        . '<th style="padding:6px 0;border-bottom:1px solid #eee">Client</th><th style="padding:6px 0;border-bottom:1px solid #eee">Hébergement</th><th style="padding:6px 0;border-bottom:1px solid #eee">Domaine</th></tr>';
    foreach ($rows as $r) {
        $html .= '<tr><td style="padding:8px 0;border-bottom:1px solid #f0f0f0">' . h($r['client']) . '<br><span style="color:#999;font-size:12px">' . h($r['domain'] ?: $r['name']) . '</span></td>'
            . '<td style="padding:8px 0;border-bottom:1px solid #f0f0f0">' . $fmt($r['hosting_renewal']) . '</td>'
            . '<td style="padding:8px 0;border-bottom:1px solid #f0f0f0">' . $fmt($r['domain_expires']) . '</td></tr>';
    }
    $html .= '</table>';
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? ($GLOBALS['CONFIG']['website'] ?? 'webina.com.tr'));
    return $html . '<p style="margin-top:24px"><a href="https://' . h($host) . '/admin" style="background:#23772a;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:700">Ouvrir le CRM</a></p>';
}

/* ---------- Rapport mensuel envoyé à chaque client : l'état de son site ---------- */
function crm_client_monthly_reports(PDO $pdo): int {
    crm_schema($pdo);
    $month = date('Y-m');
    $sent = 0;
    $clients = $pdo->query("SELECT DISTINCT c.id, c.name, c.email, c.lang
        FROM crm_clients c JOIN crm_projects p ON p.client_id = c.id
        WHERE c.status <> 'archived' AND p.status <> 'cancelled'")->fetchAll();
    foreach ($clients as $c) {
        if (!filter_var((string)$c['email'], FILTER_VALIDATE_EMAIL)) continue;
        // Dédup : un rapport par client et par mois
        $rkey = 'report:' . $c['id'] . ':' . $month;
        $ins = $pdo->prepare('INSERT IGNORE INTO crm_reminders (id, rkey, project_id, kind, stage, sent_on) VALUES (?, ?, ?, ?, ?, CURDATE())');
        $ins->execute([uuid4(), $rkey, $c['id'], 'report', $month]);
        if ($ins->rowCount() !== 1) continue;
        $pr = $pdo->prepare("SELECT * FROM crm_projects WHERE client_id = ? AND status <> 'cancelled' ORDER BY created_at");
        $pr->execute([$c['id']]);
        $projects = $pr->fetchAll();
        if (!$projects) { $pdo->prepare('DELETE FROM crm_reminders WHERE rkey = ?')->execute([$rkey]); continue; }
        $iv = $pdo->prepare("SELECT number, amount, currency, due_on FROM crm_invoices WHERE client_id = ? AND paid_at IS NULL ORDER BY due_on");
        $iv->execute([$c['id']]);
        if (crm_send_client_report($c, $projects, $iv->fetchAll())) $sent++;
        else $pdo->prepare('DELETE FROM crm_reminders WHERE rkey = ?')->execute([$rkey]);
    }
    return $sent;
}

// E-mail « état de votre site » envoyé au client, dans sa langue
function crm_send_client_report(array $c, array $projects, array $invoices): bool {
    global $CONFIG;
    $lang = in_array($c['lang'] ?? '', ['tr', 'en', 'fr', 'ar'], true) ? $c['lang'] : 'tr';
    $rtl = $lang === 'ar';
    $today = strtotime('today');
    $days = fn($d) => $d ? (int)floor((strtotime($d) - $today) / 86400) : null;
    $T = [
        'tr' => ['subj' => 'Web sitenizin aylık durumu', 'head' => 'Web sitenizin durumu', 'intro' => 'İşte sitenizin bu ayki genel durumu.',
            'site' => ['live' => 'Yayında', 'preview' => 'Önizlemede', 'building' => 'Hazırlanıyor', 'suspended' => 'Durduruldu'],
            'domain' => 'Alan adı', 'hosting' => 'Hosting', 'ssl' => 'SSL sertifikası (https)', 'backup' => 'Yedekleme',
            'until' => '{d} tarihine kadar geçerli', 'soon' => '{d} tarihinde yenilenmeli', 'expired' => '{d} tarihinde doldu',
            'sslon' => 'etkin', 'backupon' => 'düzenli ve otomatik', 'reassure' => 'Siteniz yayında ve normal çalışıyor. Erişilebilirliğini izliyor ve verilerinizi yedekliyoruz.',
            'note' => 'Hatırlatma', 'inv' => 'Bekleyen fatura: {n} ({a})', 'renew' => 'Yakında yenileme: {d}', 'preview' => 'Siteniz hazır: panelinizden onaylayabilirsiniz.',
            'portal' => 'Müşteri paneline git', 'q' => 'Sorunuz mu var? Bize yazın.', 'wa' => 'WhatsApp'],
        'en' => ['subj' => 'Your website — monthly status', 'head' => 'Your website status', 'intro' => 'Here is how your site is doing this month.',
            'site' => ['live' => 'Live', 'preview' => 'In preview', 'building' => 'In progress', 'suspended' => 'Suspended'],
            'domain' => 'Domain name', 'hosting' => 'Hosting', 'ssl' => 'SSL certificate (https)', 'backup' => 'Backups',
            'until' => 'active until {d}', 'soon' => 'to renew on {d}', 'expired' => 'expired on {d}',
            'sslon' => 'active', 'backupon' => 'regular, automatic', 'reassure' => 'Your site is online and running normally. We monitor its availability and back up your data.',
            'note' => 'To note', 'inv' => 'Invoice pending: {n} ({a})', 'renew' => 'Renewal coming up: {d}', 'preview' => 'Your site is ready: you can approve it from your client area.',
            'portal' => 'Open my client area', 'q' => 'A question? Write to us.', 'wa' => 'WhatsApp'],
        'fr' => ['subj' => 'Votre site web — état du mois', 'head' => 'L’état de votre site', 'intro' => 'Voici l’état de votre site web ce mois-ci.',
            'site' => ['live' => 'En ligne', 'preview' => 'En prévisualisation', 'building' => 'En préparation', 'suspended' => 'Suspendu'],
            'domain' => 'Nom de domaine', 'hosting' => 'Hébergement', 'ssl' => 'Certificat SSL (https)', 'backup' => 'Sauvegardes',
            'until' => 'actif jusqu’au {d}', 'soon' => 'à renouveler le {d}', 'expired' => 'expiré le {d}',
            'sslon' => 'actif', 'backupon' => 'régulières et automatiques', 'reassure' => 'Votre site est en ligne et fonctionne normalement. Nous surveillons sa disponibilité et sauvegardons vos données.',
            'note' => 'À noter', 'inv' => 'Facture en attente : {n} ({a})', 'renew' => 'Renouvellement à prévoir : {d}', 'preview' => 'Votre site est prêt : vous pouvez le valider depuis votre espace client.',
            'portal' => 'Ouvrir mon espace client', 'q' => 'Une question ? Écrivez-nous.', 'wa' => 'WhatsApp'],
        'ar' => ['subj' => 'موقعك — الحالة الشهرية', 'head' => 'حالة موقعك', 'intro' => 'إليك حالة موقعك هذا الشهر.',
            'site' => ['live' => 'منشور', 'preview' => 'قيد المعاينة', 'building' => 'قيد الإعداد', 'suspended' => 'موقوف'],
            'domain' => 'اسم النطاق', 'hosting' => 'الاستضافة', 'ssl' => 'شهادة SSL (https)', 'backup' => 'النسخ الاحتياطي',
            'until' => 'سارٍ حتى {d}', 'soon' => 'يُجدَّد في {d}', 'expired' => 'انتهى في {d}',
            'sslon' => 'مفعّلة', 'backupon' => 'منتظمة وتلقائية', 'reassure' => 'موقعك يعمل بشكل طبيعي. نراقب توفّره ونحتفظ بنسخ احتياطية لبياناتك.',
            'note' => 'ملاحظة', 'inv' => 'فاتورة معلّقة: {n} ({a})', 'renew' => 'تجديد قادم: {d}', 'preview' => 'موقعك جاهز: يمكنك الموافقة عليه من منطقة العملاء.',
            'portal' => 'افتح منطقة العملاء', 'q' => 'لديك سؤال؟ راسلنا.', 'wa' => 'واتساب'],
    ][$lang];
    $line = function ($label, $value, $alert = false) {
        return '<tr><td style="padding:5px 0;color:#888;font-size:13px;white-space:nowrap">' . h($label) . '</td>'
            . '<td style="padding:5px 0;text-align:right;' . ($alert ? 'color:#b45309;font-weight:600' : 'color:#222') . '">' . $value . '</td></tr>';
    };
    $dateState = function ($d) use ($T, $days) {
        if (!$d) return ['—', false];
        $n = $days($d); $f = date('d/m/Y', strtotime($d));
        if ($n < 0) return [str_replace('{d}', $f, $T['expired']), true];
        if ($n <= 30) return [str_replace('{d}', $f, $T['soon']), true];
        return [str_replace('{d}', $f, $T['until']), false];
    };
    $cards = '';
    $actions = [];
    foreach ($projects as $p) {
        $siteKey = in_array($p['site_status'], ['live', 'preview', 'building', 'suspended'], true) ? $p['site_status'] : 'live';
        $siteTxt = $T['site'][$siteKey];
        $tag = $siteKey === 'live' ? '#1f6f2a' : ($siteKey === 'suspended' ? '#c4321f' : '#b45309');
        [$domTxt, $domA] = $dateState($p['domain_expires']);
        [$hostTxt, $hostA] = $dateState($p['hosting_renewal']);
        $sslOk = ($p['ssl_status'] ?? '') !== 'expired' && !($p['ssl_expires'] && $days($p['ssl_expires']) < 0);
        $cards .= '<div style="border:1px solid #eee;border-radius:12px;padding:16px;margin:0 0 12px">'
            . '<p style="margin:0 0 10px"><b style="font-size:15px">' . h($p['domain'] ?: $p['name']) . '</b> '
            . '<span style="background:' . $tag . '1a;color:' . $tag . ';border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;margin-inline-start:6px">' . h($siteTxt) . '</span></p>'
            . '<table width="100%" style="font-size:14px;border-collapse:collapse">'
            . ($p['domain_expires'] ? $line($T['domain'], h($domTxt), $domA) : '')
            . ($p['hosting_renewal'] ? $line($T['hosting'], h($hostTxt), $hostA) : '')
            . $line($T['ssl'], $sslOk ? h($T['sslon']) : '<span style="color:#c4321f">—</span>', !$sslOk)
            . $line($T['backup'], h($T['backupon']))
            . '</table></div>';
        if ($siteKey === 'preview') $actions[] = $T['preview'];
        if ($p['hosting_renewal'] && $days($p['hosting_renewal']) !== null && $days($p['hosting_renewal']) <= 30)
            $actions[] = str_replace('{d}', date('d/m/Y', strtotime($p['hosting_renewal'])), $T['renew']);
    }
    foreach ($invoices as $i)
        $actions[] = str_replace(['{n}', '{a}'], [h($i['number']), h(money((int)round((float)$i['amount']), $i['currency']))], $T['inv']);
    $wa = preg_replace('/\D/', '', (string)($CONFIG['whatsapp_site'] ?? $CONFIG['whatsapp'] ?? '905399662557'));
    $portal = function_exists('portal_url') ? portal_url() : 'https://my.webina.com.tr';
    $html = '<h2 style="margin:0 0 6px;font-size:20px">' . h($T['head']) . '</h2>'
        . '<p style="margin:0 0 18px;color:#666;font-size:13px">' . h($T['intro']) . ' · ' . date('m/Y') . '</p>'
        . $cards
        . '<p style="margin:14px 0 18px;color:#1f6f2a">' . h($T['reassure']) . '</p>'
        . ($actions ? '<p style="margin:0 0 6px;font-weight:600">' . h($T['note']) . '</p><ul style="margin:0 0 18px;padding-inline-start:18px;color:#333">'
            . implode('', array_map(fn($a) => '<li>' . $a . '</li>', $actions)) . '</ul>' : '')
        . '<p style="margin:18px 0 0"><a href="' . h($portal) . '" style="background:#23772a;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:700">' . h($T['portal']) . '</a></p>'
        . '<p style="margin:14px 0 0;font-size:13px;color:#666">' . h($T['q']) . ' <a href="https://wa.me/' . h($wa) . '" style="color:#1f6f2a">' . h($T['wa']) . '</a></p>';
    return send_html_mail((string)$c['email'], $T['subj'], mail_layout($html, $rtl));
}

// Avance une échéance (hébergement ou domaine) d'un an, à partir de la date la plus tardive entre aujourd'hui et l'échéance actuelle
function crm_renew_project(PDO $pdo, string $projectId, string $kind, int $years = 1): ?array {
    $col = $kind === 'domain' ? 'domain_expires' : 'hosting_renewal';
    $p = $pdo->prepare('SELECT * FROM crm_projects WHERE id = ?'); $p->execute([$projectId]); $p = $p->fetch();
    if (!$p) return null;
    $base = $p[$col] && strtotime($p[$col]) > time() ? $p[$col] : date('Y-m-d');
    $new = date('Y-m-d', strtotime("+$years year", strtotime($base)));
    $pdo->prepare("UPDATE crm_projects SET $col = ?, updated_at = NOW() WHERE id = ?")->execute([$new, $projectId]);
    crm_log($pdo, $p['client_id'], $p['deal_id'], 'project', ($kind === 'domain' ? 'Domaine' : 'Hébergement') . ' renouvelé jusqu\'au ' . date('d/m/Y', strtotime($new)));
    // Clôt la tâche de renouvellement automatique de l'ancienne échéance, s'il y en a une
    $pdo->prepare("UPDATE crm_tasks SET done_at = NOW() WHERE project_id = ? AND done_at IS NULL AND auto_key LIKE 'renewal:%'")->execute([$projectId]);
    $out = $pdo->prepare('SELECT * FROM crm_projects WHERE id = ?'); $out->execute([$projectId]);
    return $out->fetch() ?: null;
}

// Montant converti en TL avec les taux du barème (pricing.json), pour additionner $ / € / TL
function crm_to_try(float $amount, string $currency): float {
    if ($currency === 'TRY') return $amount;
    static $rates = null;
    if ($rates === null) {
        $file = dirname(__DIR__) . '/pricing.json';
        $p = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
        $rates = is_array($p['tlPer'] ?? null) ? $p['tlPer'] : [];
    }
    return $amount * (float)($rates[$currency] ?? 1);
}

/* ---------- Documents clients ---------- */
// Dossier hors de public_html (ex. /home/compte/webina_files) ; modifiable par 'files_dir' dans config.php
function crm_files_dir(): string {
    $dir = $GLOBALS['CONFIG']['files_dir'] ?? (dirname(__DIR__, 2) . '/webina_files');
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) fail('Dossier des documents inaccessible.', 500);
    // Ceinture et bretelles si le dossier se retrouvait un jour sous la racine web
    if (!is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    return $dir;
}

const CRM_DOC_TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', 'application/msword' => 'doc',
    'application/zip' => 'zip', 'application/x-zip-compressed' => 'zip', 'application/x-zip' => 'zip'];
const CRM_DOC_KINDS = ['contract', 'quote', 'invoice', 'other'];

// Enregistre le fichier envoyé (champ « file ») pour un client ; 15 Mo maximum
function crm_doc_upload(PDO $pdo, string $cid, array $f, string $kind, string $title, bool $visible, ?string $pid = null, string $source = 'webina', ?string $note = null): array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) fail('Fichier non reçu (taille maximale du serveur dépassée ?).', 422);
    if ($f['size'] > 15 * 1024 * 1024) fail('Fichier trop lourd (15 Mo maximum).', 422);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
    if (!isset(CRM_DOC_TYPES[$mime])) fail('Format accepté : PDF, Word, JPG, PNG ou ZIP.', 422);
    $id = uuid4();
    $stored = $id . '.' . CRM_DOC_TYPES[$mime];
    if (!move_uploaded_file($f['tmp_name'], crm_files_dir() . '/' . $stored)) fail('Enregistrement du fichier impossible.', 500);
    $name = mb_substr(preg_replace('/[^\p{L}\p{N} ._()-]+/u', '_', basename((string)$f['name'])), 0, 200) ?: $stored;
    $title = mb_substr(trim($title), 0, 200) ?: preg_replace('/\.[^.]+$/', '', $name);
    $kind = in_array($kind, CRM_DOC_KINDS, true) ? $kind : 'other';
    $note = $note !== null ? (mb_substr(trim($note), 0, 2000) ?: null) : null;
    $pdo->prepare('INSERT INTO crm_documents (id, client_id, project_id, kind, title, filename, mime, size, stored, visible, source, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$id, $cid, $pid, $kind, $title, $name, $mime, (int)$f['size'], $stored, $visible ? 1 : 0, $source === 'client' ? 'client' : 'webina', $note]);
    crm_log($pdo, $cid, null, 'document', $source === 'client'
        ? 'Document envoyé par le client : ' . $title . ($note ? "\n" . $note : '')
        : 'Document ajouté : ' . $title . ($visible ? ' (visible dans l\'espace client)' : ''));
    $st = $pdo->prepare('SELECT * FROM crm_documents WHERE id = ?'); $st->execute([$id]);
    return $st->fetch();
}

// Envoie le fichier au navigateur (téléchargement ou affichage du PDF)
function crm_doc_stream(array $doc, bool $inline = false): void {
    $path = crm_files_dir() . '/' . basename($doc['stored']);
    if (!is_file($path)) fail('Fichier introuvable.', 404);
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . $doc['mime']);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    $disp = $inline && ($doc['mime'] === 'application/pdf' || strpos($doc['mime'], 'image/') === 0) ? 'inline' : 'attachment';
    header("Content-Disposition: $disp; filename*=UTF-8''" . rawurlencode($doc['filename']));
    readfile($path);
    exit;
}

// $_FILES['file'] simple ou multiple (file[]) → liste de fichiers individuels, vides exclus
function crm_files_list($f): array {
    if (!is_array($f) || !isset($f['name'])) return [];
    if (!is_array($f['name'])) return ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$f];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i] ?? '', 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}
