<?php
/**
 * Point d'entrée HTTP pour les tâches cron (cron-job.org)
 *
 * URL : https://votre-site.be/news/cron.php?action=newsletter&token=VOTRE_TOKEN
 *
 * Actions disponibles :
 *   - newsletter : Envoie la newsletter hebdomadaire
 *
 * Paramètres :
 *   - token (requis) : Token secret (CRON_SECRET_TOKEN)
 *   - days           : Nombre de jours à couvrir (défaut: 7)
 *   - force          : Envoie même si déjà envoyée cette semaine
 */

require_once __DIR__ . '/config.php';

// Bloquer l'accès CLI (utiliser cron/send-newsletter.php pour le CLI)
if (php_sapi_name() === 'cli') {
    die("Utilisez cron/send-newsletter.php pour l'exécution en ligne de commande.\n");
}

header('Content-Type: text/plain; charset=utf-8');

// Vérifier le token secret
$token = $_GET['token'] ?? '';
if (CRON_SECRET_TOKEN === '' || !hash_equals(CRON_SECRET_TOKEN, $token)) {
    http_response_code(403);
    die('Accès refusé.');
}

// Router vers la bonne action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'newsletter':
        sendNewsletter();
        break;
    default:
        http_response_code(400);
        echo "Action inconnue. Actions disponibles : newsletter\n";
        break;
}

function sendNewsletter(): void
{
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
    $force = isset($_GET['force']);

    echo "=== Newsletter " . SITE_NAME . " ===\n";
    echo "Date: " . date('d/m/Y H:i') . "\n";
    echo "Période: {$days} derniers jours\n\n";

    // Vérifier la configuration Mailchimp
    $mailchimp = new MailchimpService();
    if (!$mailchimp->isConfigured()) {
        echo "ERREUR: Mailchimp n'est pas configuré.\n";
        return;
    }

    // Vérifier si une newsletter a déjà été envoyée cette semaine
    $db = Database::getInstance()->getPdo();
    if (!$force) {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM newsletter_logs
            WHERE sent_at > datetime('now', '-7 days')
            AND status = 'sent'
        ");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) {
            echo "Une newsletter a déjà été envoyée cette semaine.\n";
            echo "Ajoutez &force à l'URL pour envoyer quand même.\n";
            return;
        }
    }

    // Récupérer les articles publiés dans la période
    $stmt = $db->prepare("
        SELECT * FROM articles
        WHERE status = 'published'
        AND created_at >= datetime('now', :days_ago)
        ORDER BY created_at DESC
    ");
    $stmt->execute(['days_ago' => "-{$days} days"]);
    $articles = $stmt->fetchAll();

    if (empty($articles)) {
        echo "Aucun article publié dans les {$days} derniers jours.\n";

        $stmt = $db->prepare("INSERT INTO newsletter_logs (article_count, status) VALUES (0, 'skipped')");
        $stmt->execute();
        return;
    }

    // Charger les catégories pour chaque article
    $articleModel = new Article();
    $articlesWithCategories = [];
    foreach ($articles as $article) {
        $full = $articleModel->getById($article['id']);
        if ($full) {
            $articlesWithCategories[] = $full;
        }
    }

    echo "Articles trouvés: " . count($articlesWithCategories) . "\n";
    foreach ($articlesWithCategories as $i => $article) {
        $date = date('d/m/Y', strtotime($article['created_at']));
        echo "  " . ($i + 1) . ". [{$date}] {$article['title']}\n";
    }
    echo "\n";

    // Envoyer la newsletter
    echo "Envoi en cours via Mailchimp...\n";
    $result = $mailchimp->sendWeeklyNewsletter($articlesWithCategories);

    if ($result['success']) {
        echo "Newsletter envoyée avec succès !\n";
        echo "Campaign ID: " . ($result['campaign_id'] ?? 'N/A') . "\n";

        $stmt = $db->prepare("INSERT INTO newsletter_logs (campaign_id, article_count, status) VALUES (?, ?, 'sent')");
        $stmt->execute([$result['campaign_id'] ?? null, count($articlesWithCategories)]);
    } else {
        echo "ERREUR: " . ($result['error'] ?? 'Erreur inconnue') . "\n";

        $stmt = $db->prepare("INSERT INTO newsletter_logs (article_count, status) VALUES (?, 'error')");
        $stmt->execute([count($articlesWithCategories)]);
    }

    echo "\nTerminé.\n";
}
