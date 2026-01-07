<?php
/**
 * WikiTips - Affichage d'un article
 */
require_once __DIR__ . '/config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /');
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
    <p>L'article demandé n'existe pas. <a href="/">Retour à l'accueil</a></p>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

$pageTitle = htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

ob_start();
?>

<div class="article-header">
    <div class="article-actions">
        <a href="/edit.php?id=<?= $article['id'] ?>">Modifier</a>
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
                    <a href="/category.php?slug=<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a><br>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($article['summary']): ?>
<div class="article-section">
    <h2>Résumé</h2>
    <p><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
</div>
<?php endif; ?>

<?php if ($article['main_points']): ?>
<div class="article-section">
    <h2>Points principaux</h2>
    <?= $article['main_points'] ?>
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
    <?= nl2br(htmlspecialchars($article['content'])) ?>
</div>
<?php endif; ?>

<?php if (!empty($article['categories'])): ?>
<div class="article-categories">
    <strong>Catégories :</strong>
    <?php foreach ($article['categories'] as $cat): ?>
        <a href="/category.php?slug=<?= htmlspecialchars($cat['slug']) ?>" class="category-tag"><?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        fetch('/api/articles/' + id, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/articles.php';
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
