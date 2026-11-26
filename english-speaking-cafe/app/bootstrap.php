<?php
/**
 * Amorçage de l'application : config, session, autoload, helpers.
 * Aucun Composer requis : 100 % compatible hébergement mutualisé cPanel.
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);
define('STORAGE', ROOT . '/storage');

$configFile = ROOT . '/config/config.php';
if (!is_file($configFile)) {
    // Pas encore installé → redirection vers l'assistant d'installation
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    header('Location: ' . $base . '/install.php');
    exit;
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set($GLOBALS['config']['app']['timezone'] ?? 'Europe/Paris');

$debug = (bool)($GLOBALS['config']['app']['debug'] ?? false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', STORAGE . '/logs/php-error.log');

// Autoload simple : App\Core\Foo → app/core/Foo.php
spl_autoload_register(function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) return;
    $parts = explode('\\', substr($class, 4));
    $file = APP . '/' . strtolower($parts[0]) . '/' . implode('/', array_slice($parts, 1)) . '.php';
    if (is_file($file)) require $file;
});

require APP . '/core/helpers.php';

// Session sécurisée
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_name('xycafe_sid');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

// En-têtes de sécurité
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
