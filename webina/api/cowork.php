<?php
// ==========================================================================
// /api/cowork.php?r=… — espace de travail technique (Webina + développeur)
//   state    GET                      tout l'espace en un appel (équipe, projets, tâches, notes, messages, mises en ligne)
//   poll     GET &after=&rev=         rafraîchissement léger : présence, nouveaux messages, bloc-notes
//   tasks    POST | PATCH &id= | DELETE &id=
//   notes    PATCH {body, rev}        bloc-notes commun (refus si l'autre a écrit entre-temps)
//   message  POST (multipart body, file) | GET &file= (télécharge une pièce jointe)
//   project  PATCH &id=               champs techniques d'un site
//   deploys  POST                     journal des mises en ligne
// Aucune donnée commerciale n'est exposée ici.
// ==========================================================================
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_quote.php';
require __DIR__ . '/_crm.php';
require __DIR__ . '/_cowork.php';

$me = require_cowork();
$pdo = crm_db();
cowork_schema($pdo);
cowork_touch($pdo, $me['id']);

$r = (string)($_GET['r'] ?? 'state');
$id = (string)($_GET['id'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$in = in_array($method, ['POST', 'PATCH'], true) && empty($_FILES) ? body() : [];
$uuid = function (string $v): string { if (!preg_match('/^[0-9a-f-]{36}$/', $v)) fail('Identifiant invalide.', 422); return $v; };
$one = function (string $sql, array $p = []) use ($pdo) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetch(); };
$all = function (string $sql, array $p = []) use ($pdo) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); };

function cowork_tasks_list(callable $all): array {
    $rows = $all("SELECT t.*, p.name AS project_name, p.domain, u.name AS assignee_name, u.email AS assignee_email
        FROM cowork_tasks t LEFT JOIN crm_projects p ON p.id = t.project_id LEFT JOIN users u ON u.id = t.assignee
        ORDER BY FIELD(t.status, 'todo', 'doing', 'review', 'done'), t.position, t.created_at DESC");
    foreach ($rows as &$t) {
        $t['assignee_label'] = $t['assignee_name'] ?: ($t['assignee_email'] ? explode('@', $t['assignee_email'])[0] : null);
        unset($t['assignee_name'], $t['assignee_email']);
    }
    return $rows;
}

switch ($r) {
case 'state':
    $notes = $one('SELECT n.body, n.rev, n.updated_at, u.name, u.email FROM cowork_notes n LEFT JOIN users u ON u.id = n.updated_by WHERE n.id = 1');
    json_out([
        'me' => ['id' => $me['id'], 'email' => $me['email'], 'role' => $me['role']],
        'team' => cowork_team($pdo),
        'projects' => cowork_projects($pdo),
        'tasks' => cowork_tasks_list($all),
        'notes' => ['body' => $notes['body'], 'rev' => (int)$notes['rev'], 'updated_at' => $notes['updated_at'],
                    'by' => $notes['name'] ?: ($notes['email'] ? explode('@', $notes['email'])[0] : null)],
        'messages' => cowork_messages($pdo),
        'deploys' => $all("SELECT d.*, p.name AS project_name, u.name, u.email FROM cowork_deploys d
            LEFT JOIN crm_projects p ON p.id = d.project_id LEFT JOIN users u ON u.id = d.user_id
            ORDER BY d.created_at DESC LIMIT 40"),
    ]);

case 'poll':
    // Appelé toutes les quelques secondes tant que l'onglet est visible : réponse volontairement légère
    $after = (string)($_GET['after'] ?? '');
    if ($after !== '' && !preg_match('/^[\d\- :.]{10,26}$/', $after)) fail('Paramètre invalide.', 422);
    $notes = $one('SELECT rev, updated_at FROM cowork_notes WHERE id = 1');
    json_out([
        'team' => cowork_team($pdo),
        'messages' => cowork_messages($pdo, $after),
        'notesRev' => (int)$notes['rev'],
        'tasksRev' => (int)($one('SELECT COALESCE(MAX(UNIX_TIMESTAMP(updated_at)), 0) AS r FROM cowork_tasks')['r'] ?? 0),
    ]);

case 'tasks':
    if ($method === 'POST') {
        $title = mb_substr(trim((string)($in['title'] ?? '')), 0, 200);
        if ($title === '') fail('Titre requis.', 422);
        $tid = uuid4();
        $pdo->prepare('INSERT INTO cowork_tasks (id, project_id, title, detail, status, assignee, due_on, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$tid, !empty($in['project_id']) ? $uuid((string)$in['project_id']) : null, $title,
                mb_substr(trim((string)($in['detail'] ?? '')), 0, 5000) ?: null,
                in_array($in['status'] ?? '', COWORK_TASK_STATUS, true) ? $in['status'] : 'todo',
                !empty($in['assignee']) ? $uuid((string)$in['assignee']) : null,
                !empty($in['due_on']) ? date('Y-m-d', strtotime((string)$in['due_on'])) : null, $me['id']]);
        json_out(['task' => $one('SELECT * FROM cowork_tasks WHERE id = ?', [$tid])], 201);
    }
    if ($method === 'PATCH') {
        $uuid($id);
        if (!$one('SELECT 1 FROM cowork_tasks WHERE id = ?', [$id])) fail('Tâche introuvable.', 404);
        $set = [];
        $args = [];
        if (isset($in['title']) && trim((string)$in['title']) !== '') { $set[] = 'title = ?'; $args[] = mb_substr(trim((string)$in['title']), 0, 200); }
        if (array_key_exists('detail', $in)) { $set[] = 'detail = ?'; $args[] = mb_substr(trim((string)$in['detail']), 0, 5000) ?: null; }
        if (isset($in['status'])) {
            if (!in_array($in['status'], COWORK_TASK_STATUS, true)) fail('Statut invalide.', 422);
            $set[] = 'status = ?'; $args[] = $in['status'];
        }
        if (array_key_exists('assignee', $in)) { $set[] = 'assignee = ?'; $args[] = !empty($in['assignee']) ? $uuid((string)$in['assignee']) : null; }
        if (array_key_exists('project_id', $in)) { $set[] = 'project_id = ?'; $args[] = !empty($in['project_id']) ? $uuid((string)$in['project_id']) : null; }
        if (array_key_exists('due_on', $in)) { $set[] = 'due_on = ?'; $args[] = !empty($in['due_on']) ? date('Y-m-d', strtotime((string)$in['due_on'])) : null; }
        if ($set) {
            $args[] = $id;
            $pdo->prepare('UPDATE cowork_tasks SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = ?')->execute($args);
        }
        json_out(['task' => $one('SELECT * FROM cowork_tasks WHERE id = ?', [$id])]);
    }
    if ($method === 'DELETE') {
        $uuid($id);
        $pdo->prepare('DELETE FROM cowork_tasks WHERE id = ?')->execute([$id]);
        json_out(['ok' => true]);
    }
    break;

case 'notes':
    // Bloc-notes commun : on refuse l'enregistrement si l'autre a écrit depuis la dernière lecture
    if ($method !== 'PATCH') break;
    $cur = $one('SELECT body, rev FROM cowork_notes WHERE id = 1');
    $rev = (int)($in['rev'] ?? -1);
    if ($rev !== (int)$cur['rev']) {
        json_out(['conflict' => true, 'body' => $cur['body'], 'rev' => (int)$cur['rev']], 409);
    }
    $body = (string)($in['body'] ?? '');
    if (mb_strlen($body) > 200000) fail('Note trop longue.', 422);
    $pdo->prepare('UPDATE cowork_notes SET body = ?, rev = rev + 1, updated_by = ?, updated_at = NOW() WHERE id = 1')->execute([$body, $me['id']]);
    json_out(['rev' => (int)$cur['rev'] + 1]);

case 'message':
    if ($method === 'GET') {
        // Pièce jointe : servie par PHP depuis le dossier protégé, jamais exécutée
        $f = $one('SELECT * FROM cowork_files WHERE id = ?', [$uuid((string)($_GET['file'] ?? ''))]);
        if (!$f) fail('Fichier introuvable.', 404);
        $path = crm_files_dir() . '/' . basename($f['stored']);
        if (!is_file($path)) fail('Fichier introuvable.', 404);
        while (ob_get_level()) ob_end_clean();
        $inline = !empty($_GET['view']) && (strpos($f['mime'], 'image/') === 0 || $f['mime'] === 'application/pdf');
        header('Content-Type: ' . $f['mime']);
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header(($inline ? 'Content-Disposition: inline' : 'Content-Disposition: attachment') . "; filename*=UTF-8''" . rawurlencode($f['filename']));
        readfile($path);
        exit;
    }
    if ($method !== 'POST') break;
    $text = mb_substr(trim((string)($_POST['body'] ?? '')), 0, 5000);
    $fileId = null;
    if (!empty($_FILES['file']['name'])) {
        $up = cowork_file_upload($pdo, $_FILES['file'], $me['id']);
        $fileId = $up['id'];
    }
    if ($text === '' && !$fileId) fail('Écrivez un message ou joignez un fichier.', 422);
    $mid = uuid4();
    $pdo->prepare('INSERT INTO cowork_messages (id, user_id, body, file_id) VALUES (?, ?, ?, ?)')
        ->execute([$mid, $me['id'], $text !== '' ? $text : null, $fileId]);
    json_out(['ok' => true, 'id' => $mid], 201);

case 'project':
    if ($method !== 'PATCH') break;
    $uuid($id);
    if (!$one('SELECT 1 FROM crm_projects WHERE id = ?', [$id])) fail('Projet introuvable.', 404);
    cowork_patch_project($pdo, $id, $in);
    json_out(['ok' => true]);

case 'deploys':
    if ($method !== 'POST') break;
    $label = mb_substr(trim((string)($in['label'] ?? '')), 0, 120);
    if ($label === '') fail('Indiquez ce qui a été mis en ligne.', 422);
    $pdo->prepare('INSERT INTO cowork_deploys (id, project_id, label, note, env, user_id) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([uuid4(), !empty($in['project_id']) ? $uuid((string)$in['project_id']) : null, $label,
            mb_substr(trim((string)($in['note'] ?? '')), 0, 2000) ?: null,
            in_array($in['env'] ?? '', ['prod', 'test'], true) ? $in['env'] : 'prod', $me['id']]);
    json_out(['ok' => true], 201);
}

fail('Requête inconnue.', 404);
