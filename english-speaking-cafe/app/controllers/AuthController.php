<?php
/** Connexion, inscription, mot de passe oublié */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, View, Auth, Mailer};

final class AuthController
{
    /** Mémorise une redirection interne (?next=/book?...) */
    private function rememberNext(): void
    {
        $next = (string)input('next', '');
        if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) $_SESSION['_intended'] = $next;
    }

    public function loginForm(): void
    {
        $this->rememberNext();
        if (Auth::check()) redirect('/dashboard');
        View::render('auth/login', ['title' => 'Sign in'], 'auth');
    }

    public function login(): void
    {
        if (Auth::attempt((string)input('email'), (string)($_POST['password'] ?? ''))) {
            $to = $_SESSION['_intended'] ?? null;
            unset($_SESSION['_intended']);
            // On ne suit que les chemins internes
            if ($to && str_starts_with($to, '/') && !str_starts_with($to, '//')) { header('Location: ' . $to); exit; }
            redirect('/dashboard');
        }
        keep_old(['email' => input('email')]);
        flash('error', 'Email or password is incorrect.');
        redirect('/login');
    }

    public function registerForm(): void
    {
        $this->rememberNext();
        if (Auth::check()) redirect('/dashboard');
        View::render('auth/register', ['title' => 'Create your account'], 'auth');
    }

    public function register(): void
    {
        $role = in_array(input('role'), ['student', 'parent', 'corporate'], true) ? input('role') : 'student';
        $d = [
            'first_name' => mb_substr((string)input('first_name'), 0, 80),
            'last_name' => mb_substr((string)input('last_name'), 0, 80),
            'email' => mb_strtolower((string)input('email')),
            'phone' => mb_substr((string)input('phone'), 0, 40) ?: null,
            'cefr_level' => in_array(input('cefr_level'), CEFR, true) ? input('cefr_level') : 'A1',
            'goal' => array_key_exists((string)input('goal'), GOALS) ? input('goal') : null,
            'role' => $role,
        ];
        $pass = (string)($_POST['password'] ?? '');
        $errors = [];
        if ($d['first_name'] === '') $errors[] = 'First name is required.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (mb_strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (DB::val('SELECT id FROM users WHERE email = ?', [$d['email']])) $errors[] = 'An account already exists with this email.';
        if (empty($_POST['terms'])) $errors[] = 'Please accept the terms.';
        if ($errors) { keep_old($_POST); flash('error', implode(' ', $errors)); redirect('/register'); }

        if ($role === 'corporate') {
            $company = trim((string)input('company')) ?: ($d['last_name'] . ' Company');
            $d['company_id'] = DB::insert('companies', ['name' => mb_substr($company, 0, 160), 'contact_email' => $d['email']]);
        }
        $d['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        $id = DB::insert('users', $d);
        // Point de départ de la progression
        DB::insert('progress', ['user_id' => $id, 'cefr_level' => $d['cefr_level'], 'speaking' => 20 + cefr_index($d['cefr_level']) * 14, 'listening' => 22 + cefr_index($d['cefr_level']) * 14, 'pronunciation' => 18 + cefr_index($d['cefr_level']) * 13, 'vocabulary' => 20 + cefr_index($d['cefr_level']) * 14, 'confidence' => 15 + cefr_index($d['cefr_level']) * 12, 'source' => 'self', 'notes' => 'Self-assessed at sign-up']);
        Auth::login($id);
        clear_old();
        Mailer::send($d['email'], 'Welcome to the café ☕', '<p>Hi ' . e($d['first_name']) . ', welcome to XY Academy Speaking Café!</p><p>Your seat at the table is waiting. <a href="' . e(full_url('book')) . '">Book your first session</a>.</p>');
        flash('success', 'Welcome to the café, ' . $d['first_name'] . '!');
        $to = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);
        if ($to && str_starts_with($to, '/') && !str_starts_with($to, '//')) { header('Location: ' . $to); exit; }
        redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/');
    }

    public function forgotForm(): void
    {
        View::render('auth/forgot', ['title' => 'Reset password'], 'auth');
    }

    public function forgot(): void
    {
        $u = DB::one('SELECT * FROM users WHERE email = ? AND is_active = 1', [mb_strtolower((string)input('email'))]);
        if ($u) {
            $token = bin2hex(random_bytes(24));
            DB::update('users', ['reset_token' => hash('sha256', $token), 'reset_expires' => date('Y-m-d H:i:s', time() + 3600)], 'id = ?', [$u['id']]);
            Mailer::send($u['email'], 'Reset your password', '<p>Click to choose a new password (valid 1 hour):</p><p><a href="' . e(full_url('reset/' . $token)) . '">Reset my password</a></p>');
        }
        flash('success', 'If an account exists for this email, a reset link has been sent.');
        redirect('/login');
    }

    private function userByToken(string $token): ?array
    {
        return DB::one('SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()', [hash('sha256', $token)]);
    }

    public function resetForm(string $token): void
    {
        if (!$this->userByToken($token)) { flash('error', 'This link has expired.'); redirect('/forgot'); }
        View::render('auth/reset', ['title' => 'New password', 'token' => $token], 'auth');
    }

    public function reset(string $token): void
    {
        $u = $this->userByToken($token);
        if (!$u) { flash('error', 'This link has expired.'); redirect('/forgot'); }
        $pass = (string)($_POST['password'] ?? '');
        if (mb_strlen($pass) < 8) { flash('error', 'Password must be at least 8 characters.'); redirect('/reset/' . $token); }
        DB::update('users', ['password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'reset_token' => null, 'reset_expires' => null], 'id = ?', [$u['id']]);
        Auth::login((int)$u['id']);
        flash('success', 'Password updated.');
        redirect('/dashboard');
    }
}
