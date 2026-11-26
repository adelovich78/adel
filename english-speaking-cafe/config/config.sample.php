<?php
/**
 * Copier ce fichier en config.php (ou utiliser /install.php qui le génère).
 * Les identifiants MySQL se créent dans cPanel > Bases de données MySQL.
 */
return [
    'app' => [
        'name' => 'XY Academy — English Speaking Café',
        'url' => '',                 // ex : https://www.mondomaine.com (vide = auto)
        'timezone' => 'Europe/Istanbul',
        'locale' => 'tr',            // langue par défaut : tr (turc) ou en
        'debug' => false,
        'payments_demo' => true,     // simulateur de paiement tant que Stripe n'est pas configuré
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'cpaneluser_cafe',
        'user' => 'cpaneluser_cafe',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from' => 'hello@mondomaine.com',
        'from_name' => 'XY Academy Speaking Café',
    ],
    'stripe' => [
        'secret_key' => '',          // sk_live_... (Stripe > Developers > API keys)
        'webhook_secret' => '',      // whsec_... (endpoint : /stripe/webhook)
    ],
];
