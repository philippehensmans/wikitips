<?php
/**
 * WikiTips - Affichage d'un article
 */
require_once __DIR__ . '/config.php';

$bluesky = new BlueskyService();
$blueskyConfigured = $bluesky->isConfigured();
$blueskyMessage = $_GET['bluesky'] ?? '';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url());
    exit;
}

$articleModel = new Article();
$article = $articleModel->getBySlug($slug);

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Article non trouvé';
    ob_start();
    ?>
    <div class="article-header">
        <h1>Article non trouvé</h1>
    </div>
    <p>L'article demandé n'existe pas. <a href="<?= url() ?>">Retour à l'accueil</a></p>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

$pageTitle = htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

// Construire l'URL de l'article pour le partage et Open Graph
$articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
$articleUrl .= $_SERVER['HTTP_HOST'];
$articleUrl .= url('article.php?slug=' . urlencode($article['slug']));

// Open Graph pour les réseaux sociaux (LinkedIn, Facebook, etc.)
$ogTitle = $article['title'];
$ogType = 'article';
$ogUrl = $articleUrl;
if (!empty($article['summary'])) {
    $ogDescription = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Message WhatsApp
$whatsappText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    // Supprimer les balises HTML et décoder les entités du résumé
    $summaryClean = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $summaryShort = mb_substr($summaryClean, 0, 150);
    if (mb_strlen($summaryClean) > 150) {
        $summaryShort .= '...';
    }
    $whatsappText .= $summaryShort . "\n\n";
}
$whatsappText .= "🔗 " . $articleUrl;
$whatsappUrl = 'https://wa.me/?text=' . rawurlencode($whatsappText);

// URL Facebook
$facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($articleUrl);

// Message Facebook (sera copié dans le presse-papiers)
$facebookText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryCleanFb = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $summaryShortFb = mb_substr($summaryCleanFb, 0, 200);
    if (mb_strlen($summaryCleanFb) > 200) {
        $summaryShortFb .= '...';
    }
    $facebookText .= $summaryShortFb . "\n\n";
}
$facebookText .= "🔗 " . $articleUrl;

// URL LinkedIn
$linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($articleUrl);

// Message LinkedIn (sera copié dans le presse-papiers)
$linkedinText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryCleanLi = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $summaryShortLi = mb_substr($summaryCleanLi, 0, 250);
    if (mb_strlen($summaryCleanLi) > 250) {
        $summaryShortLi .= '...';
    }
    $linkedinText .= $summaryShortLi . "\n\n";
}
$linkedinText .= "🔗 " . $articleUrl;

ob_start();
?>

<?php if ($blueskyMessage === 'success'): ?>
<div class="success-message">
    ✓ Article partagé sur Bluesky avec succès !
</div>
<?php elseif ($blueskyMessage === 'error'): ?>
<div class="error-message">
    ✗ Erreur lors du partage sur Bluesky. <?= htmlspecialchars($_GET['error'] ?? '') ?>
</div>
<?php endif; ?>

<div class="article-header">
    <div class="article-actions">
        <a href="<?= url('edit.php?id=' . $article['id']) ?>">Modifier</a>
        <a href="<?= htmlspecialchars($whatsappUrl) ?>" class="btn-whatsapp" target="_blank" title="Partager sur WhatsApp">💬 WhatsApp</a>
        <a href="#" onclick="shareOnFacebook(); return false;" class="btn-facebook" title="Partager sur Facebook">📘 Facebook</a>
        <a href="#" onclick="shareOnLinkedin(); return false;" class="btn-linkedin" title="Partager sur LinkedIn">💼 LinkedIn</a>
        <?php if ($blueskyConfigured): ?>
        <a href="<?= url('share-bluesky.php?id=' . $article['id']) ?>" class="btn-bluesky" title="Partager sur Bluesky">🦋 Bluesky</a>
        <?php endif; ?>
        <a href="#" onclick="confirmDelete(<?= $article['id'] ?>); return false;">Supprimer</a>
    </div>
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <div class="article-meta">
        <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
        &bull; Créé le <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
        <?php if ($article['updated_at'] !== $article['created_at']): ?>
            &bull; Modifié le <?= date('d/m/Y à H:i', strtotime($article['updated_at'])) ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($article['source_url']): ?>
<div class="source-box">
    <strong>Source :</strong> <a href="<?= htmlspecialchars($article['source_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($article['source_url']) ?></a>
</div>
<?php endif; ?>

<div class="infobox">
    <div class="infobox-header">Informations</div>
    <div class="infobox-content">
        <div class="infobox-row">
            <div class="infobox-label">Statut</div>
            <div class="infobox-value"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></div>
        </div>
        <div class="infobox-row">
            <div class="infobox-label">Date</div>
            <div class="infobox-value"><?= date('d/m/Y', strtotime($article['created_at'])) ?></div>
        </div>
        <?php if (!empty($article['categories'])): ?>
        <div class="infobox-row">
            <div class="infobox-label">Catégories</div>
            <div class="infobox-value">
                <?php foreach ($article['categories'] as $cat): ?>
                    <a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a><br>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($article['main_points']): ?>
<div class="article-section">
    <h2>Points principaux</h2>
    <?= $article['main_points'] ?>
</div>
<?php endif; ?>

<?php if ($article['summary']): ?>
<div class="article-section">
    <h2>Résumé</h2>
    <?= $article['summary'] ?>
</div>
<?php endif; ?>

<?php
// Génération automatique de la recension si elle n'existe pas
$reviewData = null;
$reviewError = null;

if (empty($article['review_phh'])) {
    // Vérifier que Claude API est configuré
    if (CLAUDE_API_KEY !== 'YOUR_API_KEY_HERE') {
        $claude = new ClaudeService();
        $result = $claude->generateReview($article);

        if (isset($result['error'])) {
            $reviewError = $result['error'];
        } else {
            // Stocker la recension en base de données
            $reviewData = $result;
            $articleModel->update($article['id'], [
                'review_phh' => json_encode($result)
            ]);
        }
    }
} else {
    // Charger la recension existante
    $reviewData = json_decode($article['review_phh'], true);
}

if ($reviewData): ?>
<div class="article-section">
    <h2>Recension</h2>
    <div class="review-header">
        <h3><?= htmlspecialchars($reviewData['titre'] ?? 'Sans titre') ?></h3>
        <div class="review-meta">
            <span>~<?= $reviewData['nombre_signes'] ?? 4000 ?> signes (hors espaces)</span>
            <button type="button" class="btn btn-small" onclick="copyReviewToClipboard()">📋 Copier</button>
        </div>
    </div>
    <div class="review-content">
        <p class="review-chapo"><strong><?= htmlspecialchars($reviewData['chapo'] ?? '') ?></strong></p>
        <?php foreach ($reviewData['sections'] ?? [] as $section): ?>
        <h3 class="review-intertitre"><?= htmlspecialchars($section['intertitre'] ?? '') ?></h3>
        <p><?= htmlspecialchars($section['contenu'] ?? '') ?></p>
        <?php endforeach; ?>
        <?php if (!empty($article['source_url'])): ?>
        <p class="review-source"><strong>Source :</strong> <a href="<?= htmlspecialchars($article['source_url']) ?>" target="_blank"><?= htmlspecialchars($article['source_url']) ?></a></p>
        <?php endif; ?>
        <?php if (!empty($reviewData['hashtags'])): ?>
        <p class="review-hashtags"><?= htmlspecialchars(implode(' ', $reviewData['hashtags'])) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($reviewError): ?>
<div class="article-section">
    <h2>Recension</h2>
    <div class="error-message">
        ✗ Erreur lors de la génération : <?= htmlspecialchars($reviewError) ?>
    </div>
</div>
<?php endif; ?>

<?php if ($article['human_rights_analysis']): ?>
<div class="human-rights-box">
    <h3>Analyse sous l'angle des droits humains</h3>
    <?= $article['human_rights_analysis'] ?>
</div>
<?php endif; ?>

<?php if ($article['content']): ?>
<div class="article-section">
    <h2>Contenu</h2>
    <div class="article-content"><?= $article['content'] ?></div>
</div>
<?php endif; ?>

<?php if (!empty($article['categories'])): ?>
<div class="article-categories">
    <strong>Catégories :</strong>
    <?php foreach ($article['categories'] as $cat): ?>
        <a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>" class="category-tag"><?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Données pour la copie de la recension
const reviewPlainText = <?= json_encode($reviewData ? ($reviewData['plain_text'] ?? '') : '') ?>;

// Données pour le partage Facebook
const facebookText = <?= json_encode($facebookText) ?>;
const facebookUrl = <?= json_encode($facebookUrl) ?>;

// Données pour le partage LinkedIn
const linkedinText = <?= json_encode($linkedinText) ?>;
const linkedinUrl = <?= json_encode($linkedinUrl) ?>;

function shareOnFacebook() {
    // Copier le texte dans le presse-papiers
    navigator.clipboard.writeText(facebookText).then(() => {
        // Ouvrir la fenêtre de partage Facebook
        window.open(facebookUrl, '_blank', 'width=600,height=400');
        // Afficher un message
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication Facebook.');
    }).catch(err => {
        // Fallback pour les navigateurs sans clipboard API
        const textarea = document.createElement('textarea');
        textarea.value = facebookText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        window.open(facebookUrl, '_blank', 'width=600,height=400');
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication Facebook.');
    });
}

function shareOnLinkedin() {
    // Copier le texte dans le presse-papiers
    navigator.clipboard.writeText(linkedinText).then(() => {
        // Ouvrir la fenêtre de partage LinkedIn
        window.open(linkedinUrl, '_blank', 'width=600,height=500');
        // Afficher un message
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication LinkedIn.');
    }).catch(err => {
        // Fallback pour les navigateurs sans clipboard API
        const textarea = document.createElement('textarea');
        textarea.value = linkedinText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        window.open(linkedinUrl, '_blank', 'width=600,height=500');
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication LinkedIn.');
    });
}

function copyReviewToClipboard() {
    navigator.clipboard.writeText(reviewPlainText).then(() => {
        alert('Recension copiée dans le presse-papiers !');
    }).catch(err => {
        // Fallback pour les navigateurs sans clipboard API
        const textarea = document.createElement('textarea');
        textarea.value = reviewPlainText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Recension copiée dans le presse-papiers !');
    });
}

function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        fetch('<?= url('api/index.php?action=articles') ?>' + '/' + id, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= url('articles.php') ?>';
                } else {
                    alert('Erreur lors de la suppression');
                }
            });
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
