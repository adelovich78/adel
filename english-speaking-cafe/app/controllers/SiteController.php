<?php
/** Pages publiques du site */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, View, Auth, Mailer};

final class SiteController
{
    private function programs_all(): array
    {
        return DB::all('SELECT * FROM programs WHERE is_active = 1 ORDER BY sort_order, id');
    }

    private function teachers_all(): array
    {
        return DB::all('SELECT * FROM teachers WHERE is_active = 1 ORDER BY sort_order, id');
    }

    private function events_upcoming(int $limit = 12): array
    {
        return DB::all("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id AND r.status IN ('pending','confirmed','attended')) AS taken
                        FROM events e WHERE e.is_published = 1 AND e.starts_at > NOW() ORDER BY e.starts_at LIMIT $limit");
    }

    public function home(): void
    {
        View::render('pages/home', [
            'title' => 'Speak English. For real.',
            'programs' => $this->programs_all(),
            'teachers' => $this->teachers_all(),
            'events' => $this->events_upcoming(4),
            'plans' => DB::all('SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY sort_order LIMIT 3'),
            'stats' => [
                'learners' => (int)setting('stat_learners', 1200),
                'teachers' => (int)DB::val('SELECT COUNT(*) FROM teachers WHERE is_active = 1'),
                'countries' => (int)setting('stat_countries', 38),
                'sessions' => (int)setting('stat_sessions', 9400),
            ],
            'bodyClass' => 'is-home',
        ]);
    }

    public function programs(): void
    {
        View::render('pages/programs', ['title' => 'Programs', 'programs' => $this->programs_all()]);
    }

    public function program(string $slug): void
    {
        $p = DB::one('SELECT * FROM programs WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$p) abort(404, 'Program not found.');
        $sessions = \App\Core\Booking::availableSessions(['program' => $slug, 'limit' => 12]);
        View::render('pages/program', ['title' => $p['name'], 'p' => $p, 'sessions' => $sessions, 'others' => DB::all('SELECT * FROM programs WHERE is_active = 1 AND id <> ? ORDER BY sort_order LIMIT 3', [$p['id']])]);
    }

    public function oneToOne(): void
    {
        View::render('pages/one-to-one', ['title' => '1-to-1 Coaching', 'p' => DB::one("SELECT * FROM programs WHERE category = 'one_to_one' AND is_active = 1 LIMIT 1"), 'teachers' => $this->teachers_all()]);
    }

    public function business(): void
    {
        View::render('pages/business', ['title' => 'Business English', 'p' => DB::one("SELECT * FROM programs WHERE category = 'business' AND is_active = 1 LIMIT 1")]);
    }

    public function events(): void
    {
        View::render('pages/events', ['title' => 'Events', 'events' => $this->events_upcoming(30)]);
    }

    public function teachers(): void
    {
        View::render('pages/teachers', ['title' => 'Native teachers', 'teachers' => $this->teachers_all()]);
    }

    public function memberships(): void
    {
        View::render('pages/memberships', ['title' => 'Memberships', 'plans' => DB::all('SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY sort_order')]);
    }

    public function about(): void
    {
        View::render('pages/about', ['title' => 'Our story', 'teachers' => $this->teachers_all()]);
    }

    public function contact(): void
    {
        View::render('pages/contact', ['title' => 'Contact']);
    }

    public function contactSend(): void
    {
        $d = ['name' => input('name'), 'email' => mb_strtolower((string)input('email')), 'topic' => input('topic'), 'message' => input('message')];
        // Pot de miel anti-spam
        if (input('website')) redirect('/contact');
        if (!$d['name'] || !filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen((string)$d['message']) < 5) {
            keep_old($d);
            flash('error', 'Please fill in your name, a valid email and a message.');
            redirect('/contact');
        }
        DB::insert('contact_messages', array_map(fn($v) => mb_substr((string)$v, 0, 5000), $d));
        Mailer::send((string)setting('contact_email', config('mail.from')), 'New contact message — ' . $d['topic'], '<p><b>' . e($d['name']) . '</b> (' . e($d['email']) . ')</p><p>' . nl2br(e($d['message'])) . '</p>');
        clear_old();
        flash('success', 'Thank you! Our team will get back to you within 24 hours.');
        redirect('/contact');
    }

    public function library(): void
    {
        $u = Auth::user();
        $level = input('level');
        $where = 'is_published = 1';
        $params = [];
        if ($level && in_array($level, CEFR, true)) { $where .= ' AND level = ?'; $params[] = $level; }
        $books = DB::all("SELECT * FROM books WHERE $where ORDER BY FIELD(level,'A1','A2','B1','B2','C1','C2'), title", $params);
        View::render('library/index', [
            'title' => 'Library',
            'books' => $books,
            'filter' => $level,
            'maxLevel' => self::maxLibraryLevel($u),
            'counts' => array_column(DB::all('SELECT level, COUNT(*) c FROM books WHERE is_published = 1 GROUP BY level'), 'c', 'level'),
        ]);
    }

    /** Niveau maximum accessible : propre niveau (ou celui des enfants/salariés), tout pour staff */
    public static function maxLibraryLevel(?array $u): ?string
    {
        if (!$u) return null;
        if (in_array($u['role'], ['admin', 'teacher'], true)) return 'C2';
        $levels = [$u['cefr_level']];
        if ($u['role'] === 'parent') $levels = array_merge($levels, array_column(DB::all('SELECT cefr_level FROM users WHERE parent_id = ?', [$u['id']]), 'cefr_level'));
        if ($u['role'] === 'corporate' && $u['company_id']) $levels = array_merge($levels, array_column(DB::all('SELECT cefr_level FROM users WHERE company_id = ?', [$u['company_id']]), 'cefr_level'));
        usort($levels, fn($a, $b) => cefr_index($b) <=> cefr_index($a));
        return $levels[0] ?? 'A1';
    }

    public function download(string $id): void
    {
        $u = Auth::require();
        $b = DB::one('SELECT * FROM books WHERE id = ? AND is_published = 1', [(int)$id]);
        if (!$b) abort(404, 'Book not found.');
        $max = self::maxLibraryLevel($u);
        if (cefr_index($b['level']) > cefr_index($max)) {
            flash('error', 'This book unlocks at level ' . $b['level'] . '. Keep speaking — you’ll get there!');
            redirect('/library');
        }
        $path = realpath(STORAGE . '/books/' . basename($b['file_path']));
        if (!$path || !str_starts_with($path, realpath(STORAGE . '/books'))) abort(404, 'File missing.');
        DB::q('UPDATE books SET downloads = downloads + 1 WHERE id = ?', [$b['id']]);
        DB::insert('book_downloads', ['book_id' => $b['id'], 'user_id' => $u['id']]);
        $name = preg_replace('/[^A-Za-z0-9\-]+/', '-', $b['level'] . '-' . $b['title']) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        header('Content-Type: ' . ((new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . trim($name, '-') . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function legal(): void
    {
        View::render('pages/legal', ['title' => 'Terms & privacy']);
    }
}
