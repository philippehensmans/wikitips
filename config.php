<?php
/**
 * News - Configuration
 * Application de publication d'articles avec analyse des droits humains
 */

// Charger la configuration locale si elle existe (non versionnée)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Mode debug (à désactiver en production)
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// Configuration de la base de données SQLite
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/data/news.db');
}

// Configuration de l'API Claude
if (!defined('CLAUDE_API_KEY')) {
    define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: 'YOUR_API_KEY_HERE');
}
if (!defined('CLAUDE_API_URL')) {
    define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');
}
if (!defined('CLAUDE_MODEL')) {
    define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
}

// Configuration du site
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'News - Droits Humains');
}
if (!defined('SITE_DESCRIPTION')) {
    define('SITE_DESCRIPTION', 'Veille et analyse sous l\'angle des droits humains');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost:8080');
}

// Configuration de sécurité
if (!defined('API_SECRET_KEY')) {
    define('API_SECRET_KEY', getenv('API_SECRET_KEY') ?: 'change_this_secret_key_in_production');
}

// Configuration Bluesky (AT Protocol)
// Créez un "App Password" sur https://bsky.app/settings/app-passwords
if (!defined('BLUESKY_IDENTIFIER')) {
    define('BLUESKY_IDENTIFIER', ''); // Votre handle (ex: user.bsky.social) ou email
}
if (!defined('BLUESKY_APP_PASSWORD')) {
    define('BLUESKY_APP_PASSWORD', ''); // App Password (pas votre mot de passe principal)
}
if (!defined('BLUESKY_AUTO_SHARE')) {
    define('BLUESKY_AUTO_SHARE', false); // Partage automatique à la création d'article
}

// Configuration Mailchimp
// Créez un compte sur https://mailchimp.com et obtenez une API key
// L'API key se trouve dans Account > Extras > API keys
if (!defined('MAILCHIMP_API_KEY')) {
    define('MAILCHIMP_API_KEY', getenv('MAILCHIMP_API_KEY') ?: '');
}
if (!defined('MAILCHIMP_LIST_ID')) {
    define('MAILCHIMP_LIST_ID', getenv('MAILCHIMP_LIST_ID') ?: ''); // Audience/List ID
}
if (!defined('MAILCHIMP_FROM_NAME')) {
    define('MAILCHIMP_FROM_NAME', SITE_NAME);
}
if (!defined('NEWSLETTER_DAY')) {
    define('NEWSLETTER_DAY', 'monday'); // Jour d'envoi de la newsletter hebdomadaire
}

// Token secret pour les appels cron via HTTP (ex: cron-job.org)
// Définissez un token aléatoire dans config.local.php
if (!defined('CRON_SECRET_TOKEN')) {
    define('CRON_SECRET_TOKEN', getenv('CRON_SECRET_TOKEN') ?: '');
}

// Ancien chemin de base (pour redirection automatique)
// Si l'application était précédemment accessible sous /wikitips/,
// les requêtes seront redirigées vers /news/
if (!defined('OLD_BASE_PATH')) {
    define('OLD_BASE_PATH', '/wikitips');
}

// Redirection automatique de l'ancien chemin vers le nouveau
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (defined('OLD_BASE_PATH') && OLD_BASE_PATH !== '' && preg_match('#^' . preg_quote(OLD_BASE_PATH, '#') . '(/.*)?$#i', $requestUri, $matches)) {
    $newBasePath = defined('BASE_PATH') ? BASE_PATH : '/news';
    $newPath = $newBasePath . ($matches[1] ?? '/');
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $newPath);
    exit;
}

// Chemin de base (auto-détecté ou défini manuellement)
// Ex: si installé dans /news/, définir BASE_PATH = '/news'
if (!defined('BASE_PATH')) {
    // Auto-détection du chemin de base
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = rtrim(dirname($scriptName), '/');
    // Éviter les doubles slashes pour la racine
    define('BASE_PATH', $basePath === '/' ? '' : $basePath);
}

/**
 * Génère une URL avec le chemin de base
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

// Démarrer la session tôt pour éviter "headers already sent"
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

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
