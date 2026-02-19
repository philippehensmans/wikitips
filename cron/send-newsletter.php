#!/usr/bin/env php
<?php
/**
 * Script cron pour l'envoi de la newsletter hebdomadaire
 *
 * Envoie un récapitulatif des articles publiés dans les 7 derniers jours
 * via Mailchimp à tous les abonnés de la liste.
 *
 * Configuration cron recommandée (tous les lundis à 9h) :
 *   0 9 * * 1 php /chemin/vers/wikitips/cron/send-newsletter.php
 *
 * Options :
 *   --dry-run    Affiche le contenu sans envoyer
 *   --days=N     Nombre de jours à couvrir (défaut: 7)
 *   --force      Envoie même si une newsletter a déjà été envoyée cette semaine
 */

// Exécution CLI uniquement
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Ce script ne peut être exécuté qu\'en ligne de commande.');
}

require_once __DIR__ . '/../config.php';

// Parser les options
$options = getopt('', ['dry-run', 'days:', 'force']);
$dryRun = isset($options['dry-run']);
$days = isset($options['days']) ? (int)$options['days'] : 7;
$force = isset($options['force']);

echo "=== Newsletter " . SITE_NAME . " ===\n";
echo "Date: " . date('d/m/Y H:i') . "\n";
echo "Période: {$days} derniers jours\n";
if ($dryRun) {
    echo "MODE: Dry run (pas d'envoi)\n";
}
echo "\n";

// Vérifier la configuration Mailchimp
$mailchimp = new MailchimpService();
if (!$mailchimp->isConfigured()) {
    echo "ERREUR: Mailchimp n'est pas configuré.\n";
    echo "Définissez MAILCHIMP_API_KEY et MAILCHIMP_LIST_ID dans config.php ou config.local.php\n";
    exit(1);
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
        echo "Utilisez --force pour envoyer quand même.\n";
        exit(0);
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
    echo "Pas de newsletter à envoyer.\n";

    // Logger quand même
    $stmt = $db->prepare("INSERT INTO newsletter_logs (article_count, status) VALUES (0, 'skipped')");
    $stmt->execute();

    exit(0);
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

if ($dryRun) {
    // Afficher un aperçu du HTML
    $html = $mailchimp->buildNewsletterHtml($articlesWithCategories);
    $previewPath = __DIR__ . '/../data/newsletter-preview.html';
    file_put_contents($previewPath, $html);
    echo "Aperçu HTML sauvegardé dans: {$previewPath}\n";
    echo "Ouvrez ce fichier dans un navigateur pour prévisualiser.\n";
    exit(0);
}

// Envoyer la newsletter
echo "Envoi en cours via Mailchimp...\n";
$result = $mailchimp->sendWeeklyNewsletter($articlesWithCategories);

if ($result['success']) {
    echo "Newsletter envoyée avec succès !\n";
    echo "Campaign ID: " . ($result['campaign_id'] ?? 'N/A') . "\n";

    // Logger l'envoi
    $stmt = $db->prepare("INSERT INTO newsletter_logs (campaign_id, article_count, status) VALUES (?, ?, 'sent')");
    $stmt->execute([$result['campaign_id'] ?? null, count($articlesWithCategories)]);
} else {
    echo "ERREUR: " . ($result['error'] ?? 'Erreur inconnue') . "\n";

    // Logger l'erreur
    $stmt = $db->prepare("INSERT INTO newsletter_logs (article_count, status) VALUES (?, 'error')");
    $stmt->execute([count($articlesWithCategories)]);

    exit(1);
}

echo "\nTerminé.\n";
