<?php
/**
 * Root Index - Landing Page
 *
 * Deploy this file to /home/hensmans19824/www/index.php
 * This replaces the broken index.php that requires shared-auth
 *
 * Options:
 * 1. Redirect directly to wikitips (recommended)
 * 2. Show a simple landing page with links
 */

// Option 1: Direct redirect to wikitips (uncomment to use)
// header('Location: /wikitips/');
// exit;

// Option 2: Safe include of shared-auth with error handling
$sharedAuthPath = __DIR__ . '/shared-auth/config.php';
if (file_exists($sharedAuthPath)) {
    require_once $sharedAuthPath;
} else {
    // Graceful fallback if shared-auth doesn't exist
    error_reporting(0);
    ini_set('display_errors', 0);
    date_default_timezone_set('Europe/Brussels');
}

// Set proper charset
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>k1m.be - Accueil</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            margin: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        h1 { color: #333; margin-bottom: 20px; font-size: 2em; }
        p { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }
        a.btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
        }
        a.btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        a.btn.secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        a.btn.secondary:hover {
            background: #cbd5e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue sur k1m.be</h1>
        <p>Portail d'accès aux différentes applications et ressources.</p>
        <div class="links">
            <a href="/wikitips/" class="btn">WikiTips - Droits Humains</a>
            <a href="/blog/" class="btn secondary">Blog</a>
        </div>
    </div>
</body>
</html>
