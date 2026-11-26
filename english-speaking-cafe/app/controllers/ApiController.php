<?php
/**
 * API REST v1 — base pour l'app mobile, l'IA (prononciation / speaking) et l'e-learning.
 * Auth : en-tête "Authorization: Bearer <token>" (jetons dans api_tokens) ou session web.
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, Auth, Booking};

final class ApiController
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $j = json_decode($raw, true);
        return is_array($j) ? $j : $_POST;
    }

    /** Utilisateur authentifié par jeton Bearer */
    private function apiUser(): array
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+([a-f0-9]{48})/i', $h, $m)) {
            $t = DB::one('SELECT * FROM api_tokens WHERE token_hash = ? AND (expires_at IS NULL OR expires_at > NOW())', [hash('sha256', $m[1])]);
            if ($t) {
                DB::update('api_tokens', ['last_used_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
                $u = DB::one('SELECT * FROM users WHERE id = ? AND is_active = 1', [$t['user_id']]);
                if ($u) return $u;
            }
        }
        // Les requêtes web en session (assistant de réservation) restent acceptées
        if (Auth::check()) return Auth::user();
        json_response(['error' => 'unauthorized'], 401);
    }

    private static function publicUser(array $u): array
    {
        return array_intersect_key($u, array_flip(['id', 'role', 'first_name', 'last_name', 'email', 'cefr_level', 'goal', 'company_id', 'parent_id']));
    }

    public function index(): void
    {
        json_response(['name' => 'XY Academy Speaking Café API', 'version' => 'v1', 'endpoints' => [
            'GET /api/v1/programs', 'GET /api/v1/teachers', 'GET /api/v1/sessions?program=&level=&teacher=&date=', 'GET /api/v1/events', 'GET /api/v1/books',
            'POST /api/v1/auth/token {email,password}', 'GET /api/v1/me', 'GET /api/v1/me/bookings', 'GET /api/v1/me/progress',
            'POST /api/v1/bookings {session_id, participant_id?, method}', 'POST /api/v1/ai/pronunciation (multipart audio)', 'POST /api/v1/ai/speaking {prompt}',
        ]]);
    }

    public function programs(): void
    {
        json_response(['data' => DB::all('SELECT id, slug, name, category, tagline, description, audience, min_level, max_level, duration_min, capacity, price, membership_eligible, goals FROM programs WHERE is_active = 1 ORDER BY sort_order')]);
    }

    public function teachers(): void
    {
        json_response(['data' => DB::all('SELECT id, display_name, origin, accent, headline, specialties, photo FROM teachers WHERE is_active = 1 ORDER BY sort_order')]);
    }

    public function sessions(): void
    {
        $rows = Booking::availableSessions([
            'program' => input('program'), 'level' => input('level'), 'teacher' => input('teacher'),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)input('date')) ? input('date') : null, 'limit' => input('limit', 120),
        ]);
        $out = array_map(fn($r) => [
            'id' => (int)$r['id'], 'program' => $r['program_name'], 'program_slug' => $r['program_slug'], 'category' => $r['category'],
            'title' => $r['title'], 'level' => $r['level'], 'starts_at' => $r['starts_at'], 'ends_at' => $r['ends_at'],
            'date' => date('Y-m-d', strtotime($r['starts_at'])), 'day' => fmt_date($r['starts_at'], 'D j M'), 'time' => date('H:i', strtotime($r['starts_at'])),
            'teacher' => $r['teacher_name'], 'teacher_id' => $r['teacher_id'] ? (int)$r['teacher_id'] : null, 'accent' => $r['teacher_accent'],
            'capacity' => (int)$r['capacity'], 'taken' => (int)$r['taken'], 'seats_left' => (int)$r['seats_left'], 'waiting' => (int)$r['waiting'],
            'price' => (float)$r['effective_price'], 'price_label' => money($r['effective_price']), 'room' => $r['room'], 'mode' => $r['mode'],
        ], $rows);
        json_response(['data' => $out]);
    }

    public function events(): void
    {
        json_response(['data' => DB::all("SELECT e.id, e.type, e.title, e.description, e.starts_at, e.capacity, e.price, e.min_level, e.location,
            (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id AND r.status IN ('pending','confirmed','attended')) AS taken
            FROM events e WHERE e.is_published = 1 AND e.starts_at > NOW() ORDER BY e.starts_at")]);
    }

    public function books(): void
    {
        json_response(['data' => DB::all('SELECT id, title, author, level, category, description, pages FROM books WHERE is_published = 1 ORDER BY level, title')]);
    }

    public function token(): void
    {
        $b = $this->body();
        $u = DB::one('SELECT * FROM users WHERE email = ? AND is_active = 1', [mb_strtolower((string)($b['email'] ?? ''))]);
        if (!$u || !password_verify((string)($b['password'] ?? ''), (string)$u['password_hash'])) json_response(['error' => 'invalid_credentials'], 401);
        $token = bin2hex(random_bytes(24));
        DB::insert('api_tokens', ['user_id' => $u['id'], 'token_hash' => hash('sha256', $token), 'name' => mb_substr((string)($b['device'] ?? 'mobile'), 0, 80), 'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days'))]);
        json_response(['token' => $token, 'expires_in' => 90 * 86400, 'user' => self::publicUser($u)]);
    }

    public function me(): void
    {
        json_response(['data' => self::publicUser($this->apiUser())]);
    }

    public function myBookings(): void
    {
        $u = $this->apiUser();
        json_response(['data' => DashboardController::bookingsFor('(b.user_id = ? OR b.booked_by = ?)', [$u['id'], $u['id']], 'DESC', 100)]);
    }

    public function myProgress(): void
    {
        $u = $this->apiUser();
        json_response(['data' => DashboardController::progressFor((int)$u['id'])]);
    }

    public function book(): void
    {
        $u = $this->apiUser();
        $b = $this->body();
        $participant = (int)($b['participant_id'] ?? $u['id']);
        if (!Auth::canActFor($participant, $u)) json_response(['error' => 'forbidden'], 403);
        try {
            $r = Booking::book((int)($b['session_id'] ?? 0), $participant, (int)$u['id'], in_array($b['method'] ?? '', ['stripe', 'onsite', 'credit'], true) ? $b['method'] : 'onsite');
            if ($r['status'] === 'pending') $r['checkout_url'] = \App\Core\Payment::checkoutUrl((int)$r['payment_id']);
            json_response(['data' => $r], 201);
        } catch (\DomainException $e) {
            json_response(['error' => $e->getMessage()], 409);
        }
    }

    /**
     * IA prononciation — point d'entrée prêt : stocke l'audio et crée une tâche "queued".
     * Un worker (cron cPanel) pourra appeler un service de scoring et remplir result_json + progress(source='ai').
     */
    public function aiPronunciation(): void
    {
        $u = $this->apiUser();
        $path = null;
        if (!empty($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['webm', 'wav', 'mp3', 'm4a', 'ogg'], true)) json_response(['error' => 'unsupported_audio'], 415);
            if (!is_dir(STORAGE . '/audio')) mkdir(STORAGE . '/audio', 0755, true);
            $path = 'audio/' . bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($_FILES['audio']['tmp_name'], STORAGE . '/' . $path);
        }
        $id = DB::insert('ai_jobs', ['user_id' => $u['id'], 'type' => 'pronunciation', 'input_path' => $path, 'prompt' => mb_substr((string)($_POST['text'] ?? ''), 0, 2000)]);
        json_response(['data' => ['job_id' => $id, 'status' => 'queued']], 202);
    }

    public function aiSpeaking(): void
    {
        $u = $this->apiUser();
        $b = $this->body();
        $id = DB::insert('ai_jobs', ['user_id' => $u['id'], 'type' => 'speaking', 'prompt' => mb_substr((string)($b['prompt'] ?? ''), 0, 4000)]);
        json_response(['data' => ['job_id' => $id, 'status' => 'queued']], 202);
    }
}
