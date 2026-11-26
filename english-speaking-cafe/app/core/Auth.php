<?php
/** Authentification + contrôle des rôles */
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['uid'])) {
            self::$user = DB::one('SELECT * FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']]);
            if (!self::$user) unset($_SESSION['uid']);
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int)self::user()['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function is(string ...$roles): bool
    {
        $u = self::user();
        return $u && in_array($u['role'], $roles, true);
    }

    public static function attempt(string $email, string $password): bool
    {
        // Anti brute-force simple (par session)
        $_SESSION['_login_attempts'] = ($_SESSION['_login_attempts'] ?? 0);
        if ($_SESSION['_login_attempts'] >= 8 && (time() - ($_SESSION['_login_last'] ?? 0)) < 300) return false;

        $u = DB::one('SELECT * FROM users WHERE email = ? AND is_active = 1', [mb_strtolower(trim($email))]);
        if (!$u || !$u['password_hash'] || !password_verify($password, $u['password_hash'])) {
            $_SESSION['_login_attempts']++;
            $_SESSION['_login_last'] = time();
            return false;
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
        }
        self::login((int)$u['id']);
        return true;
    }

    public static function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $id;
        $_SESSION['_login_attempts'] = 0;
        self::$user = null;
        DB::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }

    /** Exige une connexion (et éventuellement un rôle) */
    public static function require(string ...$roles): array
    {
        $u = self::user();
        if (!$u) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            flash('info', __('Please sign in to continue.'));
            redirect('/login');
        }
        if ($roles && !in_array($u['role'], $roles, true)) abort(403, __('You do not have access to this area.'));
        return $u;
    }

    /** Le compte connecté peut-il agir pour cet utilisateur ? (soi / enfant / salarié) */
    public static function canActFor(int $userId, ?array $actor = null): bool
    {
        $u = $actor ?? self::user();
        if (!$u) return false;
        if ((int)$u['id'] === $userId || $u['role'] === 'admin') return true;
        $t = DB::one('SELECT parent_id, company_id FROM users WHERE id = ?', [$userId]);
        if (!$t) return false;
        if ($u['role'] === 'parent' && (int)$t['parent_id'] === (int)$u['id']) return true;
        if ($u['role'] === 'corporate' && $u['company_id'] && (int)$t['company_id'] === (int)$u['company_id']) return true;
        return false;
    }

    public static function teacherProfile(): ?array
    {
        $u = self::user();
        return $u ? DB::one('SELECT * FROM teachers WHERE user_id = ?', [$u['id']]) : null;
    }
}
