<?php
/**
 * Shared Authentication Configuration
 *
 * This file provides shared authentication configuration for multiple applications.
 * It should be deployed to /home/hensmans19824/www/shared-auth/config.php on the server.
 */

// Prevent direct access
if (!defined('SHARED_AUTH_LOADED')) {
    define('SHARED_AUTH_LOADED', true);
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);

    // Use secure cookies if on HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
}

// Default timezone
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Brussels');
}

// Error handling for production
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

if (!DEBUG_MODE) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Database path (can be overridden by individual applications)
if (!defined('SHARED_DB_PATH')) {
    define('SHARED_DB_PATH', __DIR__ . '/../data/shared.db');
}

// Site configuration defaults
if (!defined('SITE_CHARSET')) {
    define('SITE_CHARSET', 'UTF-8');
}

// Security: Set default charset header
if (!headers_sent()) {
    header('Content-Type: text/html; charset=' . SITE_CHARSET);
}

/**
 * Simple shared authentication check
 * Returns true if user is logged in via shared auth session
 */
function shared_auth_check(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['shared_auth_user_id']);
}

/**
 * Get shared auth user info
 */
function shared_auth_user(): ?array {
    if (!shared_auth_check()) {
        return null;
    }
    return [
        'id' => $_SESSION['shared_auth_user_id'] ?? null,
        'username' => $_SESSION['shared_auth_username'] ?? null,
        'role' => $_SESSION['shared_auth_role'] ?? 'user'
    ];
}
