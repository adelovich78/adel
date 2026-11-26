<?php
/** Fonctions utilitaires globales */
declare(strict_types=1);

use App\Core\DB;

const CEFR = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
const CEFR_LABELS = [
    'A1' => 'Beginner', 'A2' => 'Elementary', 'B1' => 'Intermediate',
    'B2' => 'Upper-intermediate', 'C1' => 'Advanced', 'C2' => 'Mastery',
];
const GOALS = [
    'conversation' => ['Everyday conversation', 'Speak naturally with anyone'],
    'career'       => ['Career & business', 'Meetings, interviews, presentations'],
    'travel'       => ['Travel & life abroad', 'Feel at home anywhere'],
    'exams'        => ['Exams & university', 'IELTS, TOEFL, Cambridge'],
    'pronunciation'=> ['Pronunciation & accent', 'Sound clear and confident'],
    'school'       => ['School support', 'For kids & teens'],
];
const ROLES = ['student', 'parent', 'teacher', 'corporate', 'admin'];

function config(string $path, $default = null)
{
    $v = $GLOBALS['config'];
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

/** Échappement HTML */
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Chemin de base (gère l'installation en sous-dossier) */
function base_path(): string
{
    static $b = null;
    if ($b === null) {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($b === '.' ) $b = '';
    }
    return $b;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string)filemtime($file), -6) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function full_url(string $path = ''): string
{
    $cfg = rtrim((string)config('app.url', ''), '/');
    if ($cfg) return $cfg . '/' . ltrim($path, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

function redirect(string $path): never
{
    $to = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $to);
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    // On ne redirige que vers le même hôte
    if ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
        header('Location: ' . $ref);
        exit;
    }
    redirect('/');
}

function flash(string $type, ?string $msg = null)
{
    if ($msg === null) {
        $m = $_SESSION['_flash'][$type] ?? null;
        unset($_SESSION['_flash'][$type]);
        return $m;
    }
    $_SESSION['_flash'][$type] = $msg;
    return null;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function keep_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['_csrf']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function money($amount, ?string $currency = null): string
{
    $currency = $currency ?: setting('currency', 'EUR');
    $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'MAD' => 'MAD ', 'DZD' => 'DA ', 'CHF' => 'CHF '];
    $n = (float)$amount;
    $str = fmod($n, 1.0) === 0.0 ? number_format($n, 0, '.', ' ') : number_format($n, 2, '.', ' ');
    $s = $symbols[$currency] ?? ($currency . ' ');
    return in_array($currency, ['EUR'], true) ? $str . ' ' . $s : $s . $str;
}

/** Réglages éditables par l'admin (table settings, mis en cache par requête) */
function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (DB::all('SELECT `key`, `value` FROM settings') as $r) $cache[$r['key']] = $r['value'];
        } catch (\Throwable $e) { /* base indisponible */ }
    }
    return ($cache[$key] ?? '') !== '' ? $cache[$key] : $default;
}

function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function json_response($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $code = 404, string $message = ''): never
{
    http_response_code($code);
    \App\Core\View::render('pages/error', ['code' => $code, 'message' => $message, 'title' => (string)$code]);
    exit;
}

function cefr_index(?string $l): int
{
    $i = array_search($l, CEFR, true);
    return $i === false ? 0 : (int)$i;
}

function fmt_date(?string $dt, string $fmt = 'D j M'): string
{
    return $dt ? date($fmt, strtotime($dt)) : '';
}

function fmt_time(?string $dt): string
{
    return $dt ? date('H:i', strtotime($dt)) : '';
}

function initials(string $name): string
{
    $p = preg_split('/\s+/', trim($name));
    return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
}

function active_nav(string $path): string
{
    $cur = '/' . trim(\App\Core\Router::currentPath(), '/');
    $path = '/' . trim($path, '/');
    return ($cur === $path || ($path !== '/' && str_starts_with($cur, $path))) ? ' w--current' : '';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}

function status_badge(string $s): string
{
    $map = [
        'confirmed' => 'is-green', 'paid' => 'is-green', 'active' => 'is-green', 'attended' => 'is-green', 'scheduled' => 'is-green',
        'pending' => 'is-amber', 'waitlist' => 'is-amber', 'queued' => 'is-amber',
        'cancelled' => 'is-red', 'failed' => 'is-red', 'no_show' => 'is-red', 'expired' => 'is-red', 'refunded' => 'is-red',
    ];
    return '<span class="badge ' . ($map[$s] ?? '') . '">' . e(str_replace('_', ' ', $s)) . '</span>';
}

function category_label(string $c): string
{
    return [
        'group' => 'Small group', 'one_to_one' => '1-to-1', 'table' => 'Conversation table',
        'business' => 'Business', 'pronunciation' => 'Pronunciation', 'kids' => 'Kids', 'teens' => 'Teens',
    ][$c] ?? ucfirst($c);
}

function event_label(string $t): string
{
    return [
        'quiz' => 'Quiz night', 'debate' => 'Debate club', 'movie' => 'Movie night', 'networking' => 'Networking',
        'book_club' => 'Book club', 'karaoke' => 'Karaoke', 'other' => 'Special',
    ][$t] ?? ucfirst($t);
}

/** Upload d'image sécurisé vers /uploads */
function handle_upload(string $field, array $allowed = ['jpg', 'jpeg', 'png', 'webp'], string $dir = 'uploads'): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
    if ($f['size'] > 40 * 1024 * 1024) throw new RuntimeException('File too large.');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) throw new RuntimeException('File type not allowed.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $okMimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    if (isset($okMimes[$ext]) && $okMimes[$ext] !== $mime) throw new RuntimeException('Invalid file content.');
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $target = ROOT . '/' . trim($dir, '/');
    if (!is_dir($target)) mkdir($target, 0755, true);
    if (!move_uploaded_file($f['tmp_name'], $target . '/' . $name)) throw new RuntimeException('Could not store file.');
    return $name;
}

function media_url(?string $file, string $fallback = ''): string
{
    if (!$file) return $fallback;
    if (preg_match('#^(https?:)?//#', $file)) return $file;
    if (str_starts_with($file, 'assets/')) return asset(substr($file, 7));
    return url('uploads/' . $file);
}

function log_line(string $file, string $msg): void
{
    @file_put_contents(STORAGE . '/logs/' . $file, '[' . date('c') . '] ' . $msg . PHP_EOL, FILE_APPEND);
}

/** Logo inline (couleurs pilotées par variables CSS → s'adapte au thème clair/sombre) */
function logo(string $variant = 'horizontal', string $class = ''): string
{
    $mark = '<g class="logo_mark">'
        . '<path class="logo_steam logo_accent-stroke" d="M74 86c-14-12 4-22-6-36s4-24 12-30" stroke-width="7" stroke-linecap="round"/>'
        . '<path class="logo_steam logo_accent-stroke" d="M90 88c-10-12 6-20-2-32s6-20 10-24" stroke-width="5" stroke-linecap="round" opacity=".85"/>'
        . '<path class="logo_accent logo_bubble" d="M136 22c20 0 36 11 36 26s-16 26-36 26c-4 0-8 0-11-1l-15 11 4-15c-9-5-14-12-14-21 0-15 16-26 36-26z"/>'
        . '<circle class="logo_dots" cx="122" cy="48" r="4.6"/><circle class="logo_dots" cx="136" cy="48" r="4.6"/><circle class="logo_dots" cx="150" cy="48" r="4.6"/>'
        . '<path class="logo_primary" d="M46 98h108c0 30-16 50-40 56l-6 20-10-19c-30-3-52-26-52-57z"/>'
        . '<ellipse class="logo_primary" cx="100" cy="98" rx="54" ry="9"/><ellipse class="logo_accent" cx="100" cy="98" rx="44" ry="5.5"/>'
        . '<path class="logo_primary-stroke" d="M152 106c16-6 26 2 24 14s-14 18-30 20" stroke-width="9" stroke-linecap="round"/>'
        . '<path class="logo_accent-stroke" d="M22 168c26-14 56-16 78-2 22-14 52-12 78 2" stroke-width="7" stroke-linecap="round"/>'
        . '<path class="logo_primary-stroke" d="M16 182c30-14 62-15 84 0 22-15 54-14 84 0" stroke-width="7" stroke-linecap="round"/>'
        . '</g>';
    $cls = 'logo_svg ' . $class;
    return match ($variant) {
        'mark' => '<svg class="' . e($cls) . '" viewBox="0 0 200 200" role="img" aria-label="XY Academy">' . $mark . '</svg>',
        'mark-xy' => '<svg class="' . e($cls) . '" viewBox="0 0 400 400" role="img" aria-label="XY Academy"><g transform="translate(85 20) scale(1.15)">' . $mark . '</g><text class="logo_word" x="200" y="350" text-anchor="middle" font-size="92">XY</text></svg>',
        'full' => '<svg class="' . e($cls) . '" viewBox="0 0 600 560" role="img" aria-label="XY Academy Speaking Café"><g transform="translate(175 20) scale(1.25)">' . $mark . '</g><text class="logo_word" x="300" y="380" text-anchor="middle" font-size="64" letter-spacing="2">XY ACADEMY</text><text class="logo_sub" x="300" y="430" text-anchor="middle" font-size="27" letter-spacing="9">SPEAKING CAFÉ</text><rect class="logo_accent" x="276" y="462" width="48" height="2"/><text class="logo_word" style="font-family:var(--font-body);font-weight:600" x="300" y="505" text-anchor="middle" font-size="17" letter-spacing="5">ENGAGE  |  LEARN  |  CONNECT</text></svg>',
        default => '<svg class="' . e($cls) . '" viewBox="0 0 520 120" role="img" aria-label="XY Academy Speaking Café"><g transform="translate(0 4) scale(.56)">' . $mark . '</g><text class="logo_word" x="128" y="66" font-size="44" letter-spacing="1">XY ACADEMY</text><text class="logo_sub" x="130" y="96" font-size="17" letter-spacing="6.4">SPEAKING CAFÉ</text></svg>',
    };
}

/** Icônes SVG (trait 1.6, style linéaire) */
function icon(string $name, string $class = ''): string
{
    $p = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'coffee' => '<path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><path d="M6 2v2M10 2v2M14 2v2"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'mic' => '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v3"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>',
        'rocket' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'star' => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>',
        'sparkles' => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'layers' => '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
        'ticket' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'euro' => '<path d="M4 10h12M4 14h9M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'play' => '<path d="m6 3 14 9-14 9z"/>',
        'film' => '<rect x="2" y="2" width="20" height="20" rx="2"/><path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5"/>',
        'music' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'scale' => '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1ZM2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1ZM7 21h10M12 3v18M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
        'quiz' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/>',
        'handshake' => '<path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3M3 4h8"/>',
        'menu' => '<path class="menu-icon_line is-top" d="M4 8h16"/><path class="menu-icon_line is-bottom" d="M4 16h16"/>',
        'waveform' => '<path d="M2 10v3M6 6v11M10 3v18M14 8v7M18 5v13M22 10v3"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    ][$name] ?? '';
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function program_icon(string $category): string
{
    return icon(['group' => 'users', 'one_to_one' => 'user', 'table' => 'coffee', 'business' => 'briefcase', 'pronunciation' => 'waveform', 'kids' => 'smile', 'teens' => 'rocket'][$category] ?? 'message');
}

function event_icon(string $type): string
{
    return icon(['quiz' => 'quiz', 'debate' => 'scale', 'movie' => 'film', 'networking' => 'handshake', 'book_club' => 'book', 'karaoke' => 'music'][$type] ?? 'sparkles');
}
