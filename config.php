<?php
/**
 * WikiTips - Configuration
 * Application de publication d'articles avec analyse des droits humains
 */

// Mode debug (à désactiver en production)
define('DEBUG_MODE', true);

// Configuration de la base de données SQLite
define('DB_PATH', __DIR__ . '/data/wikitips.db');

// Configuration de l'API Claude
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: 'YOUR_API_KEY_HERE');
define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');
define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');

// Configuration du site
define('SITE_NAME', 'WikiTips - Droits Humains');
define('SITE_DESCRIPTION', 'Veille et analyse sous l\'angle des droits humains');
define('SITE_URL', 'http://localhost:8080');

// Configuration de sécurité
define('API_SECRET_KEY', getenv('API_SECRET_KEY') ?: 'change_this_secret_key_in_production');

// Fuseau horaire
date_default_timezone_set('Europe/Brussels');

// Gestion des erreurs
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoloader simple
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
