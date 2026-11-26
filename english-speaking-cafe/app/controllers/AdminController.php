<?php
/**
 * Back-office : CRUD générique piloté par configuration.
 * Ajouter une entité = ajouter une entrée dans entities().
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, View, Auth, Booking, Payment};

final class AdminController
{
    public function __construct()
    {
        Auth::require('admin');
    }

    private static function opts(string $sql): array
    {
        $o = [];
        foreach (DB::all($sql) as $r) $o[$r['id']] = $r['label'];
        return $o;
    }

    private static function levels(): array { return array_combine(CEFR, CEFR); }

    /** Définition des entités administrables */
    public static function entities(): array
    {
        return [
            'users' => [
                'table' => 'users', 'label' => 'Users', 'singular' => 'user', 'icon' => 'users',
                'list' => ['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'role' => 'Role', 'cefr_level' => 'Level', 'is_active' => 'Active'],
                'search' => ['first_name', 'last_name', 'email'], 'filter' => ['role' => ROLES],
                'fields' => fn() => [
                    ['first_name', 'text', 'First name', 'required' => true], ['last_name', 'text', 'Last name'],
                    ['email', 'email', 'Email'], ['phone', 'text', 'Phone'],
                    ['role', 'select', 'Role', 'options' => array_combine(ROLES, array_map('ucfirst', ROLES))],
                    ['cefr_level', 'select', 'CEFR level', 'options' => self::levels()],
                    ['birth_year', 'number', 'Birth year'],
                    ['parent_id', 'select', 'Parent (for kids)', 'options' => ['' => '—'] + self::opts("SELECT id, CONCAT(first_name,' ',last_name) label FROM users WHERE role='parent' ORDER BY first_name")],
                    ['company_id', 'select', 'Company', 'options' => ['' => '—'] + self::opts('SELECT id, name label FROM companies ORDER BY name')],
                    ['goal', 'select', 'Goal', 'options' => ['' => '—'] + array_map(fn($g) => $g[0], GOALS)],
                    ['password', 'password', 'New password', 'help' => 'Leave empty to keep the current password'],
                    ['is_active', 'bool', 'Active'],
                ],
            ],
            'teachers' => [
                'table' => 'teachers', 'label' => 'Teachers', 'singular' => 'teacher', 'icon' => 'mic',
                'list' => ['display_name' => 'Name', 'origin' => 'From', 'accent' => 'Accent', 'hourly_rate' => 'Rate', 'is_active' => 'Active'],
                'search' => ['display_name', 'origin', 'specialties'],
                'fields' => fn() => [
                    ['display_name', 'text', 'Display name', 'required' => true],
                    ['user_id', 'select', 'Login account', 'options' => ['' => '—'] + self::opts("SELECT id, CONCAT(first_name,' ',last_name,' — ',COALESCE(email,'')) label FROM users WHERE role='teacher'"), 'help' => 'Create a user with role “Teacher” first'],
                    ['origin', 'text', 'From (city, country)', 'required' => true], ['accent', 'select', 'Accent', 'options' => array_combine($a = ['British', 'American', 'Irish', 'Scottish', 'Canadian', 'Australian', 'South African', 'New Zealand'], $a)],
                    ['headline', 'text', 'Headline (English)'], ['headline_tr', 'text', 'Headline (Turkish)'], ['bio', 'textarea', 'Bio (English)'], ['bio_tr', 'textarea', 'Bio (Turkish)'],
                    ['specialties', 'text', 'Specialties', 'help' => 'Comma separated'],
                    ['photo', 'image', 'Photo'], ['hourly_rate', 'decimal', '1-to-1 hourly rate'],
                    ['sort_order', 'number', 'Order'], ['is_active', 'bool', 'Active'],
                ],
            ],
            'programs' => [
                'table' => 'programs', 'label' => 'Programs', 'singular' => 'program', 'icon' => 'layers',
                'list' => ['name' => 'Name', 'category' => 'Category', 'min_level' => 'From', 'max_level' => 'To', 'capacity' => 'Seats', 'price' => 'Price', 'is_active' => 'Active'],
                'search' => ['name', 'slug'],
                'fields' => fn() => [
                    ['name', 'text', 'Name (English)', 'required' => true], ['name_tr', 'text', 'Name (Turkish)'], ['slug', 'text', 'URL slug', 'required' => true],
                    ['category', 'select', 'Category', 'options' => array_combine($c = ['group', 'one_to_one', 'table', 'business', 'pronunciation', 'kids', 'teens'], array_map('category_label', $c))],
                    ['tagline', 'text', 'Tagline (English)'], ['tagline_tr', 'text', 'Tagline (Turkish)'], ['description', 'textarea', 'Description (English)'], ['description_tr', 'textarea', 'Description (Turkish)'], ['audience', 'text', 'Audience (English)'], ['audience_tr', 'text', 'Audience (Turkish)'],
                    ['min_level', 'select', 'Min level', 'options' => self::levels()], ['max_level', 'select', 'Max level', 'options' => self::levels()],
                    ['duration_min', 'number', 'Duration (min)'], ['capacity', 'number', 'Default capacity', 'help' => 'Groups: max 5 learners + 1 native teacher'],
                    ['price', 'decimal', 'Price per session'], ['membership_eligible', 'bool', 'Included in memberships'],
                    ['goals', 'text', 'Goals', 'help' => 'Comma separated: ' . implode(', ', array_keys(GOALS))],
                    ['color', 'select', 'Color', 'options' => ['green' => 'Green', 'amber' => 'Amber', 'espresso' => 'Espresso', 'charcoal' => 'Charcoal', 'cream' => 'Cream']],
                    ['image', 'image', 'Image'], ['sort_order', 'number', 'Order'], ['is_active', 'bool', 'Active'],
                ],
            ],
            'sessions' => [
                'table' => 'sessions', 'label' => 'Sessions', 'singular' => 'session', 'icon' => 'calendar',
                'query' => "SELECT s.*, p.name AS program, t.display_name AS teacher, CONCAT((SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status IN " . Booking::SEATED . "), ' / ', s.capacity) AS seats, (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status='waitlist') AS waitlist FROM sessions s JOIN programs p ON p.id = s.program_id LEFT JOIN teachers t ON t.id = s.teacher_id",
                'order' => 's.starts_at DESC', 'alias' => 's',
                'list' => ['starts_at' => 'Starts', 'program' => 'Program', 'teacher' => 'Teacher', 'level' => 'Level', 'seats' => 'Seats', 'waitlist' => 'Waitlist', 'price' => 'Price', 'status' => 'Status'],
                'search' => ['p.name', 't.display_name', 's.title'], 'filter' => ['status' => ['scheduled', 'cancelled', 'completed']],
                'fields' => fn() => [
                    ['program_id', 'select', 'Program', 'options' => self::opts('SELECT id, name label FROM programs ORDER BY sort_order'), 'required' => true],
                    ['teacher_id', 'select', 'Teacher', 'options' => ['' => '—'] + self::opts('SELECT id, display_name label FROM teachers ORDER BY sort_order')],
                    ['title', 'text', 'Title / theme'], ['level', 'select', 'Level', 'options' => ['' => 'Any (program range)'] + self::levels()],
                    ['starts_at', 'datetime', 'Starts', 'required' => true], ['ends_at', 'datetime', 'Ends', 'required' => true],
                    ['capacity', 'number', 'Capacity', 'help' => 'Cannot be set below the number of seated learners'], ['price', 'decimal', 'Price override', 'help' => 'Empty = program price'],
                    ['room', 'text', 'Room / table'], ['mode', 'select', 'Mode', 'options' => ['cafe' => 'At the café', 'online' => 'Online']],
                    ['status', 'select', 'Status', 'options' => ['scheduled' => 'Scheduled', 'cancelled' => 'Cancelled', 'completed' => 'Completed']],
                ],
            ],
            'bookings' => [
                'table' => 'bookings', 'label' => 'Bookings', 'singular' => 'booking', 'icon' => 'ticket',
                'query' => "SELECT b.*, CONCAT(u.first_name,' ',u.last_name) AS learner, p.name AS program, s.starts_at FROM bookings b JOIN users u ON u.id = b.user_id JOIN sessions s ON s.id = b.session_id JOIN programs p ON p.id = s.program_id",
                'order' => 's.starts_at DESC', 'alias' => 'b',
                'list' => ['learner' => 'Learner', 'program' => 'Program', 'starts_at' => 'Session', 'status' => 'Status', 'waitlist_position' => 'Wait #'],
                'search' => ['u.first_name', 'u.last_name', 'p.name'], 'filter' => ['status' => ['pending', 'confirmed', 'waitlist', 'cancelled', 'attended', 'no_show']],
                'fields' => fn() => [
                    ['session_id', 'select', 'Session', 'options' => self::opts("SELECT s.id, CONCAT(DATE_FORMAT(s.starts_at,'%a %d %b %H:%i'),' — ',p.name) label FROM sessions s JOIN programs p ON p.id = s.program_id WHERE s.starts_at > NOW() AND s.status='scheduled' ORDER BY s.starts_at LIMIT 300"), 'required' => true],
                    ['user_id', 'select', 'Learner', 'options' => self::opts("SELECT id, CONCAT(first_name,' ',last_name,' (',cefr_level,')') label FROM users WHERE role IN ('student') ORDER BY first_name"), 'required' => true],
                    ['method', 'select', 'Payment', 'options' => ['onsite' => 'Pay at the café', 'credit' => 'Membership credit', 'stripe' => 'Online (link in dashboard)'], 'virtual' => true],
                    ['teacher_note', 'textarea', 'Teacher note'],
                ],
                'create_only' => ['session_id', 'user_id', 'method'],
            ],
            'events' => [
                'table' => 'events', 'label' => 'Events', 'singular' => 'event', 'icon' => 'sparkles',
                'query' => "SELECT e.*, CONCAT((SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id AND r.status IN ('pending','confirmed','attended')),' / ',e.capacity) AS seats FROM events e",
                'order' => 'e.starts_at DESC', 'alias' => 'e',
                'list' => ['starts_at' => 'Date', 'title' => 'Title', 'type' => 'Type', 'seats' => 'Seats', 'price' => 'Price', 'is_published' => 'Published'],
                'search' => ['e.title'],
                'fields' => fn() => [
                    ['type', 'select', 'Type', 'options' => array_combine($t = ['quiz', 'debate', 'movie', 'networking', 'book_club', 'karaoke', 'other'], array_map('event_label', $t))],
                    ['title', 'text', 'Title (English)', 'required' => true], ['title_tr', 'text', 'Title (Turkish)'], ['description', 'textarea', 'Description (English)'], ['description_tr', 'textarea', 'Description (Turkish)'],
                    ['starts_at', 'datetime', 'Starts', 'required' => true], ['ends_at', 'datetime', 'Ends'],
                    ['capacity', 'number', 'Capacity'], ['price', 'decimal', 'Price'], ['min_level', 'select', 'Min level', 'options' => self::levels()],
                    ['location', 'text', 'Location'], ['image', 'image', 'Image'], ['is_published', 'bool', 'Published'],
                ],
            ],
            'registrations' => [
                'table' => 'event_registrations', 'label' => 'Event guests', 'singular' => 'registration', 'icon' => 'ticket', 'hidden' => true,
                'query' => "SELECT r.*, e.title AS event, CONCAT(u.first_name,' ',u.last_name) AS guest FROM event_registrations r JOIN events e ON e.id = r.event_id JOIN users u ON u.id = r.user_id",
                'order' => 'r.created_at DESC', 'alias' => 'r',
                'list' => ['event' => 'Event', 'guest' => 'Guest', 'status' => 'Status', 'created_at' => 'Registered'],
                'search' => ['e.title', 'u.first_name', 'u.last_name'],
                'fields' => fn() => [['status', 'select', 'Status', 'options' => array_combine($s = ['pending', 'confirmed', 'waitlist', 'cancelled', 'attended'], $s)]],
                'no_create' => true,
            ],
            'plans' => [
                'table' => 'membership_plans', 'label' => 'Membership plans', 'singular' => 'plan', 'icon' => 'star',
                'list' => ['name' => 'Name', 'price' => 'Price', 'period' => 'Period', 'credits' => 'Credits', 'includes_events' => 'Events', 'is_active' => 'Active'],
                'search' => ['name'],
                'fields' => fn() => [
                    ['name', 'text', 'Name', 'required' => true], ['slug', 'text', 'Slug', 'required' => true], ['tagline', 'text', 'Tagline (English)'], ['tagline_tr', 'text', 'Tagline (Turkish)'],
                    ['price', 'decimal', 'Price'], ['period', 'select', 'Period', 'options' => ['month' => 'Monthly', 'quarter' => 'Quarterly', 'year' => 'Yearly']],
                    ['credits', 'number', 'Session credits per month', 'help' => '0 = unlimited'], ['includes_events', 'bool', 'Free events'],
                    ['perks', 'textarea', 'Perks (English)', 'help' => 'One per line'], ['perks_tr', 'textarea', 'Perks (Turkish)', 'help' => 'One per line'], ['is_featured', 'bool', 'Featured'], ['sort_order', 'number', 'Order'], ['is_active', 'bool', 'Active'],
                ],
            ],
            'memberships' => [
                'table' => 'memberships', 'label' => 'Memberships', 'singular' => 'membership', 'icon' => 'card',
                'query' => "SELECT m.*, CONCAT(u.first_name,' ',u.last_name) AS member, p.name AS plan FROM memberships m JOIN users u ON u.id = m.user_id JOIN membership_plans p ON p.id = m.plan_id",
                'order' => 'm.created_at DESC', 'alias' => 'm',
                'list' => ['member' => 'Member', 'plan' => 'Plan', 'status' => 'Status', 'starts_at' => 'From', 'ends_at' => 'Until', 'credits_remaining' => 'Credits'],
                'search' => ['u.first_name', 'u.last_name', 'p.name'], 'filter' => ['status' => ['pending', 'active', 'expired', 'cancelled']],
                'fields' => fn() => [
                    ['user_id', 'select', 'Member', 'options' => self::opts("SELECT id, CONCAT(first_name,' ',last_name) label FROM users ORDER BY first_name"), 'required' => true],
                    ['plan_id', 'select', 'Plan', 'options' => self::opts('SELECT id, name label FROM membership_plans'), 'required' => true],
                    ['status', 'select', 'Status', 'options' => array_combine($s = ['pending', 'active', 'expired', 'cancelled'], $s)],
                    ['starts_at', 'date', 'Start'], ['ends_at', 'date', 'End'], ['credits_remaining', 'number', 'Credits remaining'],
                ],
            ],
            'payments' => [
                'table' => 'payments', 'label' => 'Payments', 'singular' => 'payment', 'icon' => 'euro',
                'query' => "SELECT pa.*, CONCAT(u.first_name,' ',u.last_name) AS payer FROM payments pa JOIN users u ON u.id = pa.user_id",
                'order' => 'pa.created_at DESC', 'alias' => 'pa',
                'list' => ['created_at' => 'Date', 'payer' => 'Payer', 'purpose' => 'For', 'amount' => 'Amount', 'method' => 'Method', 'status' => 'Status'],
                'search' => ['u.first_name', 'u.last_name', 'pa.provider_ref'], 'filter' => ['status' => ['pending', 'paid', 'failed', 'refunded', 'cancelled']],
                'fields' => fn() => [
                    ['amount', 'decimal', 'Amount'], ['method', 'select', 'Method', 'options' => array_combine($m = ['stripe', 'onsite', 'credit', 'free'], $m)],
                    ['status', 'select', 'Status', 'options' => array_combine($s = ['pending', 'paid', 'failed', 'refunded', 'cancelled'], $s)],
                    ['provider_ref', 'text', 'Reference'],
                ],
                'no_create' => true,
            ],
            'books' => [
                'table' => 'books', 'label' => 'Library', 'singular' => 'book', 'icon' => 'book',
                'list' => ['title' => 'Title', 'level' => 'Level', 'category' => 'Category', 'downloads' => 'Downloads', 'is_published' => 'Published'],
                'search' => ['title', 'author', 'category'], 'filter' => ['level' => CEFR],
                'fields' => fn() => [
                    ['title', 'text', 'Title', 'required' => true], ['author', 'text', 'Author'],
                    ['level', 'select', 'CEFR level', 'options' => self::levels()],
                    ['category', 'select', 'Category', 'options' => array_combine($c = ['Conversation', 'Grammar', 'Vocabulary', 'Pronunciation', 'Business', 'Readers', 'Kids', 'Exams'], $c)],
                    ['description', 'textarea', 'Description'], ['file_path', 'file', 'PDF / EPUB / ZIP', 'help' => 'Stored privately, downloadable by learners of this level and above'],
                    ['cover_color', 'color', 'Cover color'], ['cover_image', 'image', 'Cover image'], ['pages', 'number', 'Pages'], ['is_published', 'bool', 'Published'],
                ],
            ],
            'companies' => [
                'table' => 'companies', 'label' => 'Companies', 'singular' => 'company', 'icon' => 'building',
                'list' => ['name' => 'Name', 'contact_email' => 'Contact', 'seats_included' => 'Seats'],
                'search' => ['name', 'contact_email'],
                'fields' => fn() => [['name', 'text', 'Name', 'required' => true], ['contact_email', 'email', 'Contact email'], ['seats_included', 'number', 'Seats included'], ['notes', 'textarea', 'Notes']],
            ],
            'messages' => [
                'table' => 'contact_messages', 'label' => 'Messages', 'singular' => 'message', 'icon' => 'mail',
                'list' => ['created_at' => 'Date', 'name' => 'Name', 'email' => 'Email', 'topic' => 'Topic', 'is_read' => 'Read'],
                'search' => ['name', 'email', 'message'],
                'fields' => fn() => [['name', 'text', 'Name'], ['email', 'email', 'Email'], ['topic', 'text', 'Topic'], ['message', 'textarea', 'Message'], ['is_read', 'bool', 'Read']],
                'no_create' => true,
            ],
        ];
    }

    private function entity(string $key): array
    {
        $e = self::entities()[$key] ?? null;
        if (!$e) abort(404, 'Unknown section.');
        return $e + ['key' => $key];
    }

    public function index(): void
    {
        Booking::releaseExpired();
        $kpi = [
            'revenue_month' => (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND paid_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
            'bookings_week' => (int)DB::val("SELECT COUNT(*) FROM bookings b JOIN sessions s ON s.id=b.session_id WHERE b.status IN ('confirmed','pending','attended') AND s.starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"),
            'members' => (int)DB::val("SELECT COUNT(*) FROM memberships WHERE status='active' AND (ends_at IS NULL OR ends_at >= CURDATE())"),
            'learners' => (int)DB::val("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1"),
            'waitlist' => (int)DB::val("SELECT COUNT(*) FROM bookings WHERE status='waitlist'"),
            'unread' => (int)DB::val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0'),
            'fill' => (int)DB::val("SELECT COALESCE(ROUND(100*SUM(t.taken)/NULLIF(SUM(t.capacity),0)),0) FROM (SELECT s.capacity, (SELECT COUNT(*) FROM bookings b WHERE b.session_id=s.id AND b.status IN " . Booking::SEATED . ") taken FROM sessions s WHERE s.status='scheduled' AND s.starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)) t"),
            'pending_onsite' => (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending' AND method='onsite'"),
        ];
        $weeks = DB::all("SELECT YEARWEEK(paid_at,3) wk, MIN(DATE(paid_at)) d, SUM(amount) total FROM payments WHERE status='paid' AND paid_at >= DATE_SUB(NOW(), INTERVAL 10 WEEK) GROUP BY wk ORDER BY wk");
        View::render('admin/index', [
            'title' => 'Admin', 'kpi' => $kpi, 'weeks' => $weeks,
            'upcoming' => Booking::availableSessions(['limit' => 10]),
            'recentPayments' => DB::all("SELECT pa.*, CONCAT(u.first_name,' ',u.last_name) payer FROM payments pa JOIN users u ON u.id=pa.user_id ORDER BY pa.created_at DESC LIMIT 8"),
            'popular' => DB::all("SELECT p.name, COUNT(b.id) c FROM programs p LEFT JOIN sessions s ON s.program_id=p.id LEFT JOIN bookings b ON b.session_id=s.id AND b.status IN ('confirmed','attended') GROUP BY p.id ORDER BY c DESC LIMIT 7"),
            'programs' => DB::all('SELECT id, name, duration_min, capacity FROM programs ORDER BY sort_order'),
            'teachers' => DB::all('SELECT id, display_name FROM teachers WHERE is_active=1 ORDER BY sort_order'),
        ], 'dashboard');
    }

    public function list(string $key): void
    {
        $e = $this->entity($key);
        $alias = $e['alias'] ?? null;
        $base = $e['query'] ?? "SELECT * FROM `{$e['table']}`";
        $where = [];
        $params = [];
        $q = trim((string)input('q'));
        if ($q !== '' && !empty($e['search'])) {
            $where[] = '(' . implode(' OR ', array_map(fn($c) => (str_contains($c, '.') ? $c : "`$c`") . ' LIKE ?', $e['search'])) . ')';
            foreach ($e['search'] as $_) $params[] = "%$q%";
        }
        foreach ($e['filter'] ?? [] as $col => $vals) {
            $v = input($col);
            if ($v !== null && $v !== '' && in_array($v, $vals, true)) { $where[] = ($alias ? "$alias.`$col`" : "`$col`") . ' = ?'; $params[] = $v; }
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int)DB::val("SELECT COUNT(*) FROM ($base$sqlWhere) x", $params);
        $per = 25;
        $page = max(1, (int)input('page', 1));
        $order = $e['order'] ?? ($alias ? "$alias.id DESC" : 'id DESC');
        $rows = DB::all("$base$sqlWhere ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $params);
        View::render('admin/list', ['title' => $e['label'], 'e' => $e, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per), 'q' => $q], 'dashboard');
    }

    public function form(string $key, ?string $id = null): void
    {
        $e = $this->entity($key);
        $row = $id ? DB::one("SELECT * FROM `{$e['table']}` WHERE id = ?", [(int)$id]) : null;
        if ($id && !$row) abort(404);
        if (!$id && !empty($e['no_create'])) abort(404);
        $extra = [];
        if ($key === 'sessions' && $row) $extra['roster'] = DB::all("SELECT b.*, u.first_name, u.last_name, u.cefr_level FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.session_id = ? ORDER BY FIELD(b.status,'confirmed','pending','attended','no_show','waitlist','cancelled'), b.waitlist_position", [$row['id']]);
        if ($key === 'messages' && $row && !$row['is_read']) DB::update('contact_messages', ['is_read' => 1], 'id = ?', [$row['id']]);
        View::render('admin/form', ['title' => ($row ? 'Edit ' : 'New ') . $e['singular'], 'e' => $e, 'row' => $row, 'fields' => ($e['fields'])(), 'extra' => $extra], 'dashboard');
    }

    public function save(string $key): void
    {
        $e = $this->entity($key);
        $id = (int)input('id');
        $fields = ($e['fields'])();
        $data = [];
        try {
            // Réservation créée par l'admin : passe par le service (anti-surbooking)
            if ($key === 'bookings' && !$id) {
                $u = Auth::user();
                $r = Booking::book((int)input('session_id'), (int)input('user_id'), (int)input('user_id'), (string)input('method', 'onsite'));
                flash('success', $r['status'] === 'waitlist' ? "Session full — learner added to waitlist (#{$r['position']})." : 'Booking created (' . $r['status'] . ').');
                redirect('/admin/bookings');
            }
            foreach ($fields as $f) {
                [$name, $type] = $f;
                if (!empty($f['virtual'])) continue;
                if ($id && in_array($name, $e['create_only'] ?? [], true)) continue;
                $v = $_POST[$name] ?? null;
                switch ($type) {
                    case 'bool': $data[$name] = !empty($_POST[$name]) ? 1 : 0; break;
                    case 'password':
                        if (is_string($v) && $v !== '') {
                            if (mb_strlen($v) < 8) throw new \DomainException('Password must be at least 8 characters.');
                            $data['password_hash'] = password_hash($v, PASSWORD_DEFAULT);
                        }
                        break;
                    case 'image':
                        $file = handle_upload($name);
                        if ($file) $data[$name] = $file;
                        elseif (!empty($_POST[$name . '_remove'])) $data[$name] = null;
                        break;
                    case 'file':
                        $file = handle_upload($name, ['pdf', 'epub', 'zip', 'mp3'], 'storage/books');
                        if ($file) $data[$name] = $file;
                        elseif (!$id) throw new \DomainException('Please upload a file.');
                        break;
                    case 'number': $data[$name] = ($v === '' || $v === null) ? null : (int)$v; break;
                    case 'decimal': $data[$name] = ($v === '' || $v === null) ? null : round((float)str_replace(',', '.', (string)$v), 2); break;
                    case 'datetime': $data[$name] = $v ? date('Y-m-d H:i:s', strtotime((string)$v)) : null; break;
                    case 'date': $data[$name] = $v ? date('Y-m-d', strtotime((string)$v)) : null; break;
                    case 'email': $data[$name] = $v ? mb_strtolower(trim((string)$v)) : null; break;
                    case 'select': $data[$name] = ($v === '' || $v === null) ? null : (string)$v;
                        if ($data[$name] !== null && isset($f['options']) && !array_key_exists($data[$name], $f['options'])) throw new \DomainException("Invalid value for {$f[2]}.");
                        break;
                    default: $data[$name] = is_string($v) ? trim($v) : $v;
                }
                if (!empty($f['required']) && array_key_exists($name, $data) && ($data[$name] === null || $data[$name] === '')) throw new \DomainException("{$f[2]} is required.");
            }
            // Règles métier
            if ($key === 'sessions') {
                if (strtotime((string)$data['ends_at']) <= strtotime((string)$data['starts_at'])) throw new \DomainException('End must be after start.');
                $data['capacity'] = max(1, (int)($data['capacity'] ?? 5));
                if ($id) {
                    $seated = Booking::seatsTaken($id);
                    if ($data['capacity'] < $seated) throw new \DomainException("Capacity cannot be lower than the $seated learners already seated.");
                }
            }
            if ($key === 'programs') $data['slug'] = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$data['slug'])), '-');
            if ($key === 'users' && !empty($data['email']) && DB::val('SELECT id FROM users WHERE email = ? AND id <> ?', [$data['email'], $id])) throw new \DomainException('Email already used.');
            if ($key === 'users' && $id === (int)Auth::id() && ($data['role'] ?? 'admin') !== 'admin') throw new \DomainException('You cannot remove your own admin role.');

            if ($id) DB::update($e['table'], $data, 'id = ?', [$id]);
            else $id = DB::insert($e['table'], $data);
            // Si la capacité augmente, la liste d'attente avance
            if ($key === 'sessions') Booking::promoteWaitlist($id);
            flash('success', ucfirst($e['singular']) . ' saved.');
            redirect("/admin/$key");
        } catch (\DomainException | \RuntimeException $ex) {
            keep_old($_POST);
            flash('error', $ex->getMessage());
            back();
        } catch (\PDOException $ex) {
            keep_old($_POST);
            log_line('db.log', $ex->getMessage());
            flash('error', 'Could not save: ' . (str_contains($ex->getMessage(), 'Duplicate') ? 'this value already exists.' : 'database constraint.'));
            back();
        }
    }

    public function delete(string $key, string $id): void
    {
        $e = $this->entity($key);
        if ($key === 'users' && (int)$id === (int)Auth::id()) { flash('error', 'You cannot delete yourself.'); back(); }
        try {
            if ($key === 'bookings') { Booking::cancel((int)$id, true); flash('success', 'Booking cancelled.'); redirect('/admin/bookings'); }
            DB::q("DELETE FROM `{$e['table']}` WHERE id = ?", [(int)$id]);
            flash('success', ucfirst($e['singular']) . ' deleted.');
        } catch (\Throwable $ex) {
            flash('error', 'Cannot delete: it is still used elsewhere. Deactivate it instead.');
        }
        redirect("/admin/$key");
    }

    public function markPaid(string $id): void
    {
        Payment::markPaid((int)$id, 'manual_' . date('Ymd'));
        flash('success', 'Payment marked as paid.');
        back();
    }

    public function bookingStatus(string $id): void
    {
        $status = (string)input('status');
        $b = DB::one('SELECT * FROM bookings WHERE id = ?', [(int)$id]);
        if (!$b) abort(404);
        try {
            if ($status === 'cancelled') Booking::cancel((int)$id, true);
            elseif (in_array($status, ['attended', 'no_show', 'confirmed'], true)) {
                if (in_array($b['status'], ['waitlist', 'cancelled'], true)) {
                    // Remettre quelqu'un en place exige une place libre
                    DB::tx(function () use ($b, $status) {
                        $s = DB::one('SELECT capacity FROM sessions WHERE id = ? FOR UPDATE', [$b['session_id']]);
                        if (Booking::seatsTaken((int)$b['session_id']) >= (int)$s['capacity']) throw new \DomainException('Session is full. Increase capacity first.');
                        DB::update('bookings', ['status' => $status, 'waitlist_position' => null], 'id = ?', [$b['id']]);
                    });
                } else DB::update('bookings', ['status' => $status], 'id = ?', [$b['id']]);
            }
            flash('success', 'Booking updated.');
        } catch (\DomainException $ex) {
            flash('error', $ex->getMessage());
        }
        back();
    }

    /** Génération d'un planning récurrent */
    public function generateSessions(): void
    {
        $program = DB::one('SELECT * FROM programs WHERE id = ?', [(int)input('program_id')]);
        if (!$program) { flash('error', 'Choose a program.'); back(); }
        $days = array_map('intval', (array)($_POST['days'] ?? []));
        $time = preg_match('/^\d{2}:\d{2}$/', (string)input('time')) ? input('time') : '18:00';
        $weeks = max(1, min(26, (int)input('weeks', 4)));
        $cap = max(1, (int)input('capacity', $program['capacity']));
        $level = in_array(input('level'), CEFR, true) ? input('level') : null;
        $teacher = (int)input('teacher_id') ?: null;
        $n = 0;
        $start = strtotime('tomorrow');
        for ($d = 0; $d < $weeks * 7; $d++) {
            $day = strtotime("+$d day", $start);
            if (!in_array((int)date('N', $day), $days, true)) continue;
            $st = strtotime(date('Y-m-d', $day) . ' ' . $time);
            DB::insert('sessions', [
                'program_id' => $program['id'], 'teacher_id' => $teacher, 'level' => $level,
                'starts_at' => date('Y-m-d H:i:s', $st), 'ends_at' => date('Y-m-d H:i:s', $st + (int)$program['duration_min'] * 60),
                'capacity' => $cap, 'room' => input('room') ?: null, 'title' => input('title') ?: null,
            ]);
            $n++;
        }
        flash('success', "$n sessions created.");
        redirect('/admin/sessions');
    }

    public const SETTINGS = [
        'currency' => ['Currency (ISO)', 'EUR'],
        'contact_email' => ['Contact email', ''],
        'phone' => ['Phone', ''],
        'address' => ['Address', ''],
        'opening_hours' => ['Opening hours (English)', ''],
        'opening_hours_tr' => ['Opening hours (Turkish)', 'Pzt–Cum 9:00–21:30 · Cmt 10:00–18:00'],
        'instagram' => ['Instagram URL', ''],
        'linkedin' => ['LinkedIn URL', ''],
        'whatsapp' => ['WhatsApp number', ''],
        'booking_hold_minutes' => ['Seat hold during online payment (minutes)', '20'],
        'cancellation_hours' => ['Free cancellation until (hours before)', '12'],
        'allow_onsite_payment' => ['Allow “pay at the café” (1/0)', '1'],
        'hero_tagline' => ['Hero tagline', 'SPEAK ENGLISH. FOR REAL.'],
        'hero_subtitle' => ['Hero subtitle (English)', ''],
        'hero_subtitle_tr' => ['Hero subtitle (Turkish)', 'Anadili İngilizce olan öğretmenlerle, beş kişilik masalarda, güzel bir kahve eşliğinde gerçek İngilizce pratiği yaptığınız butik bir kafe.'],
        'stat_learners' => ['Stat: learners', '1200'],
        'stat_countries' => ['Stat: nationalities', '38'],
        'stat_sessions' => ['Stat: sessions hosted', '9400'],
        'trial_price' => ['Trial session price', '15'],
        'business_from_price' => ['Business: “from” price per employee/month', '149'],
    ];

    public function settings(): void
    {
        $vals = array_column(DB::all('SELECT `key`, `value` FROM settings'), 'value', 'key');
        View::render('admin/settings', ['title' => 'Settings', 'vals' => $vals], 'dashboard');
    }

    public function saveSettings(): void
    {
        foreach (self::SETTINGS as $k => [$label]) {
            $v = mb_substr(trim((string)($_POST[$k] ?? '')), 0, 2000);
            DB::q('INSERT INTO settings (`key`, `value`, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), label = VALUES(label)', [$k, $v, $label]);
        }
        flash('success', 'Settings saved.');
        redirect('/admin/settings');
    }
}
