<?php
/** Tableaux de bord : élève, professeur, parent, entreprise */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, View, Auth, Booking};

final class DashboardController
{
    public const SKILLS = ['speaking', 'listening', 'pronunciation', 'vocabulary', 'confidence'];

    /** Synthèse de progression d'un apprenant */
    public static function progressFor(int $userId): array
    {
        $history = DB::all('SELECT p.*, t.display_name AS teacher_name FROM progress p LEFT JOIN teachers t ON t.id = p.teacher_id WHERE p.user_id = ? ORDER BY p.created_at ASC, p.id ASC', [$userId]);
        $att = DB::one("SELECT SUM(status = 'attended') a, SUM(status = 'no_show') n FROM bookings WHERE user_id = ?", [$userId]);
        $a = (int)($att['a'] ?? 0); $n = (int)($att['n'] ?? 0);
        return [
            'latest' => $history ? end($history) : null,
            'history' => $history,
            'attended' => $a,
            'attendance_rate' => ($a + $n) > 0 ? (int)round($a / ($a + $n) * 100) : null,
        ];
    }

    public static function bookingsFor(string $where, array $params, string $order = 'ASC', int $limit = 50): array
    {
        return DB::all("SELECT b.*, s.starts_at, s.ends_at, s.room, s.mode, p.name AS program_name, p.name_tr AS program_name_tr, p.category, t.display_name AS teacher_name,
                               u.first_name AS learner_first, u.last_name AS learner_last, pay.status AS pay_status, pay.method AS pay_method, pay.amount AS pay_amount
                        FROM bookings b JOIN sessions s ON s.id = b.session_id JOIN programs p ON p.id = s.program_id
                        LEFT JOIN teachers t ON t.id = s.teacher_id JOIN users u ON u.id = b.user_id LEFT JOIN payments pay ON pay.id = b.payment_id
                        WHERE $where ORDER BY s.starts_at $order LIMIT $limit", $params);
    }

    public function index(): void
    {
        $u = Auth::require();
        Booking::releaseExpired();
        if ($u['role'] === 'admin') redirect('/admin');
        $method = $u['role'];
        $this->$method($u);
    }

    private function common(array $u): array
    {
        return [
            'u' => $u,
            'payments' => DB::all('SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 30', [$u['id']]),
            'memberships' => DB::all('SELECT m.*, p.name, p.credits, p.includes_events, p.period FROM memberships m JOIN membership_plans p ON p.id = m.plan_id WHERE m.user_id = ? ORDER BY m.created_at DESC', [$u['id']]),
            'events' => DB::all("SELECT r.*, e.title, e.title_tr, e.type, e.starts_at FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND e.starts_at > NOW() AND r.status <> 'cancelled' ORDER BY e.starts_at", [$u['id']]),
        ];
    }

    private function student(array $u): void
    {
        View::render('dashboard/student', $this->common($u) + [
            'title' => 'My café',
            'upcoming' => self::bookingsFor("b.user_id = ? AND s.starts_at > NOW() AND b.status IN ('pending','confirmed','waitlist')", [$u['id']]),
            'past' => self::bookingsFor("b.user_id = ? AND s.starts_at <= NOW() AND b.status <> 'cancelled'", [$u['id']], 'DESC', 20),
            'progress' => self::progressFor((int)$u['id']),
            'books' => DB::all('SELECT * FROM books WHERE is_published = 1 AND level = ? ORDER BY downloads DESC LIMIT 4', [$u['cefr_level']]),
        ], 'dashboard');
    }

    private function parent(array $u): void
    {
        $children = DB::all('SELECT * FROM users WHERE parent_id = ? ORDER BY first_name', [$u['id']]);
        foreach ($children as &$c) {
            $c['progress'] = self::progressFor((int)$c['id']);
            $c['upcoming'] = self::bookingsFor("b.user_id = ? AND s.starts_at > NOW() AND b.status IN ('pending','confirmed','waitlist')", [$c['id']], 'ASC', 10);
        }
        View::render('dashboard/parent', $this->common($u) + [
            'title' => 'Family dashboard',
            'children' => $children,
            'bookings' => self::bookingsFor("b.booked_by = ? AND s.starts_at > NOW() AND b.status <> 'cancelled'", [$u['id']]),
        ], 'dashboard');
    }

    private function corporate(array $u): void
    {
        $company = DB::one('SELECT * FROM companies WHERE id = ?', [$u['company_id']]);
        $employees = DB::all("SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status IN ('confirmed','attended')) AS sessions_count,
                                     (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'attended') AS attended_count,
                                     (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'no_show') AS noshow_count
                              FROM users u WHERE u.company_id = ? AND u.id <> ? ORDER BY u.first_name", [$u['company_id'] ?? 0, $u['id']]);
        foreach ($employees as &$em) $em['progress'] = self::progressFor((int)$em['id']);
        View::render('dashboard/corporate', $this->common($u) + [
            'title' => 'Company dashboard',
            'company' => $company,
            'employees' => $employees,
            'bookings' => self::bookingsFor("u.company_id = ? AND b.status <> 'cancelled'", [$u['company_id'] ?? 0], 'DESC', 60),
        ], 'dashboard');
    }

    private function teacher(array $u): void
    {
        $t = Auth::teacherProfile();
        if (!$t) abort(403, __('No teacher profile is linked to this account yet. Ask an admin.'));
        $sessions = DB::all("SELECT s.*, p.name AS program_name, p.category,
                                    (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status IN " . Booking::SEATED . ") AS taken
                             FROM sessions s JOIN programs p ON p.id = s.program_id
                             WHERE s.teacher_id = ? AND s.starts_at > DATE_SUB(NOW(), INTERVAL 2 DAY) AND s.status <> 'cancelled'
                             ORDER BY s.starts_at LIMIT 40", [$t['id']]);
        $ids = array_column($sessions, 'id') ?: [0];
        $in = implode(',', array_map('intval', $ids));
        $roster = [];
        foreach (DB::all("SELECT b.*, u.first_name, u.last_name, u.cefr_level, u.email FROM bookings b JOIN users u ON u.id = b.user_id WHERE b.session_id IN ($in) AND b.status IN ('pending','confirmed','attended','no_show','waitlist') ORDER BY b.status = 'waitlist', b.waitlist_position, u.first_name") as $r) {
            $roster[$r['session_id']][] = $r;
        }
        $students = DB::all("SELECT DISTINCT u.id, u.first_name, u.last_name, u.cefr_level, u.birth_year,
                                    (SELECT MAX(s2.starts_at) FROM bookings b2 JOIN sessions s2 ON s2.id = b2.session_id WHERE b2.user_id = u.id AND s2.teacher_id = ?) AS last_session
                             FROM users u JOIN bookings b ON b.user_id = u.id JOIN sessions s ON s.id = b.session_id
                             WHERE s.teacher_id = ? AND b.status IN ('confirmed','attended','no_show','pending') ORDER BY u.first_name", [$t['id'], $t['id']]);
        foreach ($students as &$st) $st['progress'] = self::progressFor((int)$st['id']);
        View::render('dashboard/teacher', [
            'title' => 'Teacher studio', 'u' => $u, 't' => $t,
            'sessions' => $sessions, 'roster' => $roster, 'students' => $students,
        ], 'dashboard');
    }

    /** Vérifie que la réservation appartient à une séance du professeur connecté */
    private function teacherBooking(int $bookingId): array
    {
        $u = Auth::require('teacher', 'admin');
        $b = DB::one('SELECT b.*, s.teacher_id FROM bookings b JOIN sessions s ON s.id = b.session_id WHERE b.id = ?', [$bookingId]);
        if (!$b) abort(404);
        if ($u['role'] === 'teacher') {
            $t = Auth::teacherProfile();
            if (!$t || (int)$t['id'] !== (int)$b['teacher_id']) abort(403);
        }
        return $b;
    }

    public function attendance(string $id): void
    {
        $b = $this->teacherBooking((int)$id);
        $status = in_array(input('status'), ['attended', 'no_show', 'confirmed'], true) ? input('status') : 'attended';
        if (!in_array($b['status'], ['confirmed', 'attended', 'no_show', 'pending'], true)) { flash('error', __('Attendance can only be set for seated learners.')); back(); }
        DB::update('bookings', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$b['id']]);
        flash('success', __('Attendance saved.'));
        back();
    }

    public function note(string $id): void
    {
        $b = $this->teacherBooking((int)$id);
        DB::update('bookings', ['teacher_note' => mb_substr((string)input('note'), 0, 4000)], 'id = ?', [$b['id']]);
        flash('success', __('Note saved.'));
        back();
    }

    public function saveProgress(): void
    {
        $u = Auth::require('teacher', 'admin');
        $t = Auth::teacherProfile();
        $uid = (int)input('user_id');
        // Un professeur ne note que ses propres élèves
        if ($u['role'] === 'teacher' && !DB::val('SELECT b.id FROM bookings b JOIN sessions s ON s.id = b.session_id WHERE b.user_id = ? AND s.teacher_id = ? LIMIT 1', [$uid, $t['id'] ?? 0])) abort(403);
        $level = in_array(input('cefr_level'), CEFR, true) ? input('cefr_level') : 'A1';
        $row = ['user_id' => $uid, 'teacher_id' => $t['id'] ?? null, 'cefr_level' => $level, 'source' => 'teacher', 'notes' => mb_substr((string)input('notes'), 0, 4000)];
        foreach (self::SKILLS as $k) $row[$k] = max(0, min(100, (int)input($k)));
        DB::insert('progress', $row);
        DB::update('users', ['cefr_level' => $level], 'id = ?', [$uid]);
        flash('success', __('Progress report saved.'));
        back();
    }

    public function profile(): void
    {
        $u = Auth::require();
        $d = [
            'first_name' => mb_substr((string)input('first_name'), 0, 80) ?: $u['first_name'],
            'last_name' => mb_substr((string)input('last_name'), 0, 80),
            'phone' => mb_substr((string)input('phone'), 0, 40) ?: null,
            'goal' => array_key_exists((string)input('goal'), GOALS) ? input('goal') : $u['goal'],
        ];
        $pass = (string)($_POST['password'] ?? '');
        if ($pass !== '') {
            if (mb_strlen($pass) < 8) { flash('error', __('Password must be at least 8 characters.')); back(); }
            $d['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        }
        DB::update('users', $d, 'id = ?', [$u['id']]);
        flash('success', __('Profile updated.'));
        back();
    }

    public function addChild(): void
    {
        $u = Auth::require('parent');
        $name = trim((string)input('first_name'));
        if ($name === '') { flash('error', __('Name required.')); back(); }
        $id = DB::insert('users', [
            'role' => 'student', 'first_name' => mb_substr($name, 0, 80), 'last_name' => mb_substr((string)input('last_name', $u['last_name']), 0, 80),
            'cefr_level' => in_array(input('cefr_level'), CEFR, true) ? input('cefr_level') : 'A1',
            'birth_year' => (int)input('birth_year') ?: null, 'parent_id' => $u['id'], 'goal' => 'school',
        ]);
        DB::insert('progress', ['user_id' => $id, 'cefr_level' => input('cefr_level', 'A1'), 'speaking' => 20, 'listening' => 22, 'pronunciation' => 18, 'vocabulary' => 20, 'confidence' => 15, 'source' => 'self', 'notes' => 'Starting point']);
        flash('success', __(':name has been added to your family.', ['name' => $name]));
        back();
    }

    public function addEmployee(): void
    {
        $u = Auth::require('corporate');
        $email = mb_strtolower((string)input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !input('first_name')) { flash('error', __('Name and a valid email are required.')); back(); }
        if (DB::val('SELECT id FROM users WHERE email = ?', [$email])) { flash('error', __('A user with this email already exists.')); back(); }
        $temp = bin2hex(random_bytes(5));
        $id = DB::insert('users', [
            'role' => 'student', 'first_name' => mb_substr((string)input('first_name'), 0, 80), 'last_name' => mb_substr((string)input('last_name'), 0, 80),
            'email' => $email, 'password_hash' => password_hash($temp, PASSWORD_DEFAULT),
            'cefr_level' => in_array(input('cefr_level'), CEFR, true) ? input('cefr_level') : 'B1', 'company_id' => $u['company_id'], 'goal' => 'career',
        ]);
        DB::insert('progress', ['user_id' => $id, 'cefr_level' => input('cefr_level', 'B1'), 'speaking' => 40, 'listening' => 45, 'pronunciation' => 40, 'vocabulary' => 42, 'confidence' => 35, 'source' => 'self', 'notes' => 'Starting point']);
        \App\Core\Mailer::send($email, __('Your company enrolled you at XY Academy'), '<p>' . e(__('Welcome! Sign in with this email and the temporary password :pw, then change it from your dashboard.', ['pw' => $temp])) . '</p><p><a href="' . e(full_url('login')) . '">' . e(__('Sign in')) . '</a></p>');
        flash('success', __('Employee added. Temporary password: :pw', ['pw' => $temp]));
        back();
    }

    /** Jeton API personnel (future app mobile) */
    public function apiToken(): void
    {
        $u = Auth::require();
        $token = bin2hex(random_bytes(24));
        DB::insert('api_tokens', ['user_id' => $u['id'], 'token_hash' => hash('sha256', $token), 'name' => 'Personal token', 'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days'))]);
        flash('success', __('API token (copy it now, it won’t be shown again): :token', ['token' => $token]));
        back();
    }
}
