<?php
/**
 * Assistant d'installation (à lancer une seule fois : https://votredomaine.com/install.php)
 * 1. Teste la connexion MySQL  2. Crée les tables  3. Écrit config/config.php
 * 4. Charge les données  5. Crée le compte admin  6. Se verrouille (storage/installed.lock)
 */
declare(strict_types=1);

define('ROOT', __DIR__);
define('APP', ROOT . '/app');
define('STORAGE', ROOT . '/storage');
$lock = STORAGE . '/installed.lock';

session_start();
if (empty($_SESSION['_install_csrf'])) $_SESSION['_install_csrf'] = bin2hex(random_bytes(16));

$errors = [];
$done = false;
$checks = [
    'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'cURL (Stripe)' => extension_loaded('curl'),
    'fileinfo' => extension_loaded('fileinfo'),
    'config/ writable' => is_writable(ROOT . '/config'),
    'storage/ writable' => is_writable(STORAGE),
    'uploads/ writable' => is_writable(ROOT . '/uploads'),
];

if (is_file($lock)) {
    http_response_code(403);
    $errors[] = 'Already installed. Delete storage/installed.lock to run the installer again (this will NOT erase data unless you choose so).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_file($lock)) {
    if (!hash_equals($_SESSION['_install_csrf'], (string)($_POST['_csrf'] ?? ''))) $errors[] = 'Session expired, reload the page.';
    $f = fn($k, $d = '') => trim((string)($_POST[$k] ?? $d));
    $cfg = [
        'app' => ['name' => 'XY Academy — English Speaking Café', 'url' => rtrim($f('url'), '/'), 'timezone' => $f('timezone', 'Europe/Paris'), 'debug' => false, 'payments_demo' => !empty($_POST['payments_demo'])],
        'db' => ['host' => $f('db_host', 'localhost'), 'port' => (int)$f('db_port', '3306'), 'name' => $f('db_name'), 'user' => $f('db_user'), 'pass' => (string)($_POST['db_pass'] ?? ''), 'charset' => 'utf8mb4'],
        'mail' => ['from' => $f('mail_from'), 'from_name' => 'XY Academy Speaking Café'],
        'stripe' => ['secret_key' => $f('stripe_secret'), 'webhook_secret' => $f('stripe_webhook')],
    ];
    if (!in_array($cfg['app']['timezone'], timezone_identifiers_list(), true)) $errors[] = 'Invalid timezone.';
    if (!filter_var($f('admin_email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Admin email is invalid.';
    if (mb_strlen((string)($_POST['admin_pass'] ?? '')) < 10) $errors[] = 'Admin password must be at least 10 characters.';
    if (!$errors) {
        try {
            $GLOBALS['config'] = $cfg;
            date_default_timezone_set($cfg['app']['timezone']);
            require APP . '/core/helpers.php';
            require APP . '/core/DB.php';
            require APP . '/core/Booking.php';
            require APP . '/controllers/AdminController.php';
            require ROOT . '/database/seed.php';
            $pdo = App\Core\DB::pdo();

            if (!empty($_POST['reset'])) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                foreach (['ai_jobs','online_lessons','api_tokens','contact_messages','settings','book_downloads','books','progress','event_registrations','events','bookings','payments','memberships','membership_plans','sessions','programs','teachers','users','companies'] as $t) $pdo->exec("DROP TABLE IF EXISTS `$t`");
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            }
            // Création des tables
            $sql = (string)file_get_contents(ROOT . '/database/schema.sql');
            $sql = preg_replace('/^--.*$/m', '', $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) $pdo->exec($stmt);

            if ((int)App\Core\DB::val('SELECT COUNT(*) FROM programs') === 0) {
                seed_database(!empty($_POST['demo']), strtoupper($f('currency', 'EUR')) ?: 'EUR');
            }
            $email = mb_strtolower($f('admin_email'));
            $existing = App\Core\DB::val('SELECT id FROM users WHERE email = ?', [$email]);
            $hash = password_hash((string)$_POST['admin_pass'], PASSWORD_DEFAULT);
            if ($existing) App\Core\DB::update('users', ['role' => 'admin', 'password_hash' => $hash, 'is_active' => 1], 'id = ?', [$existing]);
            else App\Core\DB::insert('users', ['role' => 'admin', 'first_name' => $f('admin_name', 'Admin') ?: 'Admin', 'last_name' => '', 'email' => $email, 'password_hash' => $hash, 'cefr_level' => 'C2']);

            $export = "<?php\n// Généré par install.php le " . date('Y-m-d H:i') . "\nreturn " . var_export($cfg, true) . ";\n";
            if (file_put_contents(ROOT . '/config/config.php', $export) === false) throw new RuntimeException('Cannot write config/config.php (check permissions 755).');
            @chmod(ROOT . '/config/config.php', 0640);
            file_put_contents($lock, date('c'));
            $done = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$p = fn($k, $d = '') => $h($_POST[$k] ?? $d);
$guessUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Install — XY Academy Speaking Café</title>
<link rel="icon" href="assets/logo/favicon.svg">
<style>
@font-face{font-family:"Playfair Display";src:url(assets/fonts/playfair.woff2) format("woff2");font-weight:400 900}
@font-face{font-family:"DM Sans";src:url(assets/fonts/dmsans.woff2) format("woff2");font-weight:100 1000}
:root{--cream:#f7f3ea;--esp:#1f1a17;--green:#0f4a37;--amber:#c0923a;--line:#e4dccd}
*{box-sizing:border-box}body{margin:0;font-family:"DM Sans",system-ui,sans-serif;background:var(--cream);color:var(--esp)}
.install_wrap{max-width:760px;margin:0 auto;padding:48px 16px 80px}
.install_logo{width:220px;display:block;margin:0 auto 24px}
h1{font-family:"Playfair Display",Georgia,serif;font-weight:600;font-size:2.4rem;text-align:center;margin:0 0 8px;color:var(--green)}
.install_sub{text-align:center;color:#6f655b;margin:0 0 32px}
.install_card{background:#fff;border:1px solid var(--line);border-radius:20px;padding:28px;margin-bottom:20px}
h2{font-family:"Playfair Display",Georgia,serif;font-size:1.25rem;margin:0 0 16px}
.install_grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:640px){.install_grid{grid-template-columns:1fr}}
label{display:block;font-size:.82rem;font-weight:600;margin-bottom:6px}
input,select{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:12px;font:inherit;background:#fffdf8}
input:focus{outline:2px solid var(--amber);border-color:transparent}
.install_check{display:flex;align-items:center;gap:10px;font-weight:500;font-size:.92rem;margin-top:10px}
.install_check input{width:auto}
.install_checks{display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.9rem}
.ok{color:var(--green)}.ko{color:#b3261e;font-weight:700}
.install_error{background:#fbe9e7;color:#8c1d18;border-radius:12px;padding:14px 16px;margin-bottom:16px}
.install_success{background:#e8f1ec;color:var(--green);border-radius:16px;padding:24px}
button,.btn{display:inline-block;background:var(--green);color:var(--cream);border:0;border-radius:999px;padding:16px 28px;font:600 1rem "DM Sans",sans-serif;cursor:pointer;text-decoration:none}
.help{font-size:.78rem;color:#7d7266;margin-top:4px}
code{background:#f1ebdf;padding:2px 6px;border-radius:6px}
</style>
</head>
<body>
<div class="install_wrap">
  <img class="install_logo" src="assets/logo/logo-horizontal-light.svg" alt="XY Academy Speaking Café">
  <h1>Installation</h1>
  <p class="install_sub">Create the database tables, your admin account and the starter content — in one click.</p>

  <?php foreach ($errors as $e): ?><div class="install_error"><?= $h($e) ?></div><?php endforeach; ?>

  <?php if ($done): ?>
    <div class="install_success">
      <h2>☕ Installation complete!</h2>
      <p>Your café is open. For security, <b>delete <code>install.php</code></b> from cPanel → File Manager (it is already locked).</p>
      <?php if (!empty($_POST['demo'])): ?><p>Demo accounts (password <code>demo1234</code>): student@demo.cafe · parent@demo.cafe · hr@demo.cafe · olivia@xyacademy.cafe (teacher). Delete them from Admin → Users before going live.</p><?php endif; ?>
      <p><a class="btn" href="./login">Sign in as admin →</a></p>
    </div>
  <?php elseif (!is_file($lock)): ?>
  <div class="install_card">
    <h2>Server check</h2>
    <div class="install_checks">
      <?php foreach ($checks as $k => $ok): ?><div class="<?= $ok ? 'ok' : 'ko' ?>"><?= $ok ? '✓' : '✗' ?> <?= $h($k) ?></div><?php endforeach; ?>
    </div>
  </div>
  <form method="post" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= $h($_SESSION['_install_csrf']) ?>">
    <div class="install_card">
      <h2>1 · MySQL database</h2>
      <p class="help">Create it in cPanel → <b>MySQL® Database Wizard</b> (database + user with ALL PRIVILEGES).</p>
      <div class="install_grid">
        <div><label>Host</label><input name="db_host" value="<?= $p('db_host', 'localhost') ?>" required></div>
        <div><label>Port</label><input name="db_port" value="<?= $p('db_port', '3306') ?>"></div>
        <div><label>Database name</label><input name="db_name" value="<?= $p('db_name') ?>" placeholder="cpuser_cafe" required></div>
        <div><label>Database user</label><input name="db_user" value="<?= $p('db_user') ?>" placeholder="cpuser_cafe" required></div>
        <div><label>Password</label><input type="password" name="db_pass"></div>
      </div>
      <label class="install_check"><input type="checkbox" name="reset" value="1"> Drop existing tables first (fresh install — erases data)</label>
    </div>
    <div class="install_card">
      <h2>2 · Website</h2>
      <div class="install_grid">
        <div><label>Site URL</label><input name="url" value="<?= $p('url', $guessUrl) ?>"></div>
        <div><label>Timezone</label><input name="timezone" value="<?= $p('timezone', 'Europe/Paris') ?>"></div>
        <div><label>Currency (ISO)</label><input name="currency" value="<?= $p('currency', 'EUR') ?>" maxlength="3"></div>
        <div><label>Sender email</label><input name="mail_from" value="<?= $p('mail_from', 'hello@' . ($_SERVER['HTTP_HOST'] ?? 'example.com')) ?>"><div class="help">Create this mailbox in cPanel → Email Accounts</div></div>
      </div>
      <label class="install_check"><input type="checkbox" name="demo" value="1" <?= ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($_POST['demo'])) ? 'checked' : '' ?>> Load demo accounts & bookings</label>
    </div>
    <div class="install_card">
      <h2>3 · Payments (Stripe, optional)</h2>
      <div class="install_grid">
        <div><label>Secret key</label><input name="stripe_secret" value="<?= $p('stripe_secret') ?>" placeholder="sk_live_…"></div>
        <div><label>Webhook secret</label><input name="stripe_webhook" value="<?= $p('stripe_webhook') ?>" placeholder="whsec_…"><div class="help">Endpoint: <code>/stripe/webhook</code>, event <code>checkout.session.completed</code></div></div>
      </div>
      <label class="install_check"><input type="checkbox" name="payments_demo" value="1" checked> Payment simulator while Stripe is not configured</label>
    </div>
    <div class="install_card">
      <h2>4 · Admin account</h2>
      <div class="install_grid">
        <div><label>Name</label><input name="admin_name" value="<?= $p('admin_name', 'Admin') ?>"></div>
        <div><label>Email</label><input type="email" name="admin_email" value="<?= $p('admin_email') ?>" required></div>
        <div><label>Password (10+ chars)</label><input type="password" name="admin_pass" minlength="10" required></div>
      </div>
    </div>
    <button type="submit">Install the café →</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
