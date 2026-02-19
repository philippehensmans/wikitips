<?php
/**
 * News - Gestion de la newsletter (Admin)
 */
require_once __DIR__ . '/config.php';

// Admin requis
$auth = new Auth();
$auth->requireAdmin();

$pageTitle = 'Newsletter / Abonnés - ' . SITE_NAME;
$alert = null;
$mailchimp = new MailchimpService();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_test') {
        // Envoyer un aperçu de la newsletter avec les articles de la semaine
        $days = (int)($_POST['days'] ?? 7);
        $db = Database::getInstance()->getPdo();
        $stmt = $db->prepare("
            SELECT * FROM articles
            WHERE status = 'published'
            AND created_at >= datetime('now', :days_ago)
            ORDER BY created_at DESC
        ");
        $stmt->execute(['days_ago' => "-{$days} days"]);
        $articles = $stmt->fetchAll();

        if (empty($articles)) {
            $alert = ['type' => 'info', 'message' => "Aucun article publié dans les {$days} derniers jours."];
        } else {
            // Charger les catégories
            $articleModel = new Article();
            $articlesWithCats = [];
            foreach ($articles as $article) {
                $full = $articleModel->getById($article['id']);
                if ($full) {
                    $articlesWithCats[] = $full;
                }
            }

            // Générer l'aperçu HTML
            $html = $mailchimp->buildNewsletterHtml($articlesWithCats);
            $previewPath = __DIR__ . '/data/newsletter-preview.html';
            file_put_contents($previewPath, $html);
            $alert = ['type' => 'success', 'message' => count($articlesWithCats) . " article(s) trouvés. Aperçu généré."];
        }
    } elseif ($action === 'send_now') {
        $days = (int)($_POST['days'] ?? 7);
        $db = Database::getInstance()->getPdo();
        $stmt = $db->prepare("
            SELECT * FROM articles
            WHERE status = 'published'
            AND created_at >= datetime('now', :days_ago)
            ORDER BY created_at DESC
        ");
        $stmt->execute(['days_ago' => "-{$days} days"]);
        $articles = $stmt->fetchAll();

        if (empty($articles)) {
            $alert = ['type' => 'info', 'message' => 'Aucun article à envoyer.'];
        } else {
            $articleModel = new Article();
            $articlesWithCats = [];
            foreach ($articles as $article) {
                $full = $articleModel->getById($article['id']);
                if ($full) {
                    $articlesWithCats[] = $full;
                }
            }

            $result = $mailchimp->sendWeeklyNewsletter($articlesWithCats);
            if ($result['success']) {
                $alert = ['type' => 'success', 'message' => 'Newsletter envoyée avec succès aux abonnés inscrits via le site !'];

                // Logger l'envoi
                $stmt = $db->prepare("INSERT INTO newsletter_logs (campaign_id, article_count, status) VALUES (?, ?, 'sent')");
                $stmt->execute([$result['campaign_id'] ?? null, count($articlesWithCats)]);
            } else {
                $alert = ['type' => 'error', 'message' => 'Erreur: ' . ($result['error'] ?? 'inconnue')];
            }
        }
    }
}

// Récupérer les stats et les abonnés
$stats = null;
$members = null;
if ($mailchimp->isConfigured()) {
    $stats = $mailchimp->getListStats();
    $members = $mailchimp->getMembers(100);
    $newsletterCount = $members['total_items'] ?? 0;
}

// Historique des envois
$db = Database::getInstance()->getPdo();
$logs = $db->query("SELECT * FROM newsletter_logs ORDER BY sent_at DESC LIMIT 20")->fetchAll();

ob_start();
?>

<div class="article-header">
    <h1>Newsletter / Abonnés</h1>
</div>

<?php if (!$mailchimp->isConfigured()): ?>
    <div class="alert alert-error">
        <strong>Mailchimp n'est pas configuré.</strong><br>
        Ajoutez <code>MAILCHIMP_API_KEY</code> et <code>MAILCHIMP_LIST_ID</code> dans
        <code>config.local.php</code> ou dans les variables d'environnement.
    </div>

    <div class="article-section">
        <h2>Configuration requise</h2>
        <div class="editor-container">
            <p>Ajoutez ces lignes dans <code>config.local.php</code> :</p>
            <pre style="background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto;">
&lt;?php
define('MAILCHIMP_API_KEY', 'votre-api-key-us21');
define('MAILCHIMP_LIST_ID', 'votre-audience-id');</pre>
            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                1. Connectez-vous sur <a href="https://mailchimp.com" target="_blank">mailchimp.com</a><br>
                2. API Key : Account &gt; Extras &gt; API keys<br>
                3. List/Audience ID : Audience &gt; Settings &gt; Audience name and defaults
            </p>
        </div>
    </div>
<?php else: ?>

    <!-- Statistiques -->
    <?php if ($stats && $stats['success']): ?>
    <div class="article-section">
        <h2>Statistiques de la liste</h2>
        <div class="infobox" style="float: none; width: 100%; max-width: 500px; margin: 0;">
            <div class="infobox-header"><?= htmlspecialchars($stats['name'] ?? 'Liste') ?></div>
            <div class="infobox-content">
                <div class="infobox-row">
                    <div class="infobox-label">Abonnés newsletter</div>
                    <div class="infobox-value"><strong><?= $newsletterCount ?></strong></div>
                </div>
                <div class="infobox-row">
                    <div class="infobox-label">Audience totale</div>
                    <div class="infobox-value"><?= $stats['member_count'] ?></div>
                </div>
                <div class="infobox-row">
                    <div class="infobox-label">Taux ouverture</div>
                    <div class="infobox-value"><?= round($stats['open_rate'] * 100, 1) ?>%</div>
                </div>
                <div class="infobox-row">
                    <div class="infobox-label">Taux clics</div>
                    <div class="infobox-value"><?= round($stats['click_rate'] * 100, 1) ?>%</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Envoi manuel -->
    <div class="article-section">
        <h2>Envoyer une newsletter</h2>
        <div class="editor-container">
            <form method="post" action="">
                <div class="form-group">
                    <label for="days">Période (nombre de jours)</label>
                    <input type="number" id="days" name="days" value="7" min="1" max="30" style="width: 100px;">
                    <span class="help-text">Articles publiés dans les N derniers jours.</span>
                </div>
                <div class="btn-group">
                    <button type="submit" name="action" value="send_test" class="btn">Aperçu</button>
                    <button type="submit" name="action" value="send_now" class="btn btn-primary"
                            onclick="return confirm('Envoyer la newsletter aux abonnés inscrits via le site ?');">
                        Envoyer maintenant
                    </button>
                </div>
            </form>

            <?php if (isset($alert) && $alert['type'] === 'success' && file_exists(__DIR__ . '/data/newsletter-preview.html')): ?>
                <div style="margin-top: 15px;">
                    <a href="<?= url('data/newsletter-preview.html') ?>" target="_blank" class="btn">
                        Voir l'aperçu dans un nouvel onglet
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historique des envois -->
    <div class="article-section">
        <h2>Historique des envois</h2>

        <?php if (empty($logs)): ?>
            <p>Aucune newsletter envoyée pour le moment.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f6f6f6; border-bottom: 2px solid #a2a9b1;">
                        <th style="padding: 10px; text-align: left;">Date</th>
                        <th style="padding: 10px; text-align: left;">Articles</th>
                        <th style="padding: 10px; text-align: left;">Statut</th>
                        <th style="padding: 10px; text-align: left;">Campaign ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($log['sent_at'])) ?></td>
                            <td style="padding: 10px;"><?= $log['article_count'] ?></td>
                            <td style="padding: 10px;">
                                <?php
                                $statusClass = match($log['status']) {
                                    'sent' => 'status-published',
                                    'error' => 'status-draft',
                                    default => ''
                                };
                                ?>
                                <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($log['status']) ?></span>
                            </td>
                            <td style="padding: 10px; font-size: 12px; color: #666;"><?= htmlspecialchars($log['campaign_id'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Liste des abonnés -->
    <div class="article-section">
        <h2>Abonnés actuels</h2>

        <?php if ($members && $members['success'] && !empty($members['members'])): ?>
            <p style="margin-bottom: 15px; font-size: 13px; color: #666;">
                <?= $members['total_items'] ?> abonné(s) à la newsletter (inscrits via le site).
            </p>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f6f6f6; border-bottom: 2px solid #a2a9b1;">
                        <th style="padding: 10px; text-align: left;">Email</th>
                        <th style="padding: 10px; text-align: left;">Nom</th>
                        <th style="padding: 10px; text-align: left;">Inscrit le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members['members'] as $member): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?= htmlspecialchars($member['email_address']) ?></td>
                            <td style="padding: 10px;">
                                <?= htmlspecialchars(trim(($member['merge_fields']['FNAME'] ?? '') . ' ' . ($member['merge_fields']['LNAME'] ?? ''))) ?>
                            </td>
                            <td style="padding: 10px;"><?= date('d/m/Y', strtotime($member['timestamp_signup'] ?? $member['timestamp_opt'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun abonné pour le moment. Partagez la page <a href="<?= url('newsletter.php') ?>">newsletter</a> !</p>
        <?php endif; ?>
    </div>

    <!-- Configuration cron -->
    <div class="article-section">
        <h2>Envoi automatique (cron)</h2>
        <div class="editor-container">
            <p>Pour envoyer automatiquement la newsletter chaque lundi à 9h, ajoutez cette ligne à votre crontab :</p>
            <pre style="background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px;">0 9 * * 1 php <?= realpath(__DIR__ . '/cron/send-newsletter.php') ?></pre>
            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                Options disponibles :<br>
                <code>--dry-run</code> : aperçu sans envoi<br>
                <code>--days=N</code> : couvrir N jours au lieu de 7<br>
                <code>--force</code> : envoyer même si déjà envoyé cette semaine
            </p>
        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
