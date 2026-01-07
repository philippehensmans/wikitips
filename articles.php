<?php
/**
 * WikiTips - Liste des articles
 */
require_once __DIR__ . '/config.php';

$status = $_GET['status'] ?? null;
$pageTitle = 'Articles' . ($status === 'draft' ? ' - Brouillons' : '') . ' - ' . SITE_NAME;

$articleModel = new Article();
$articles = $articleModel->getAll($status);

ob_start();
?>

<div class="article-header">
    <h1><?= $status === 'draft' ? 'Brouillons' : 'Tous les articles' ?></h1>
</div>

<div class="article-section">
    <p>
        <a href="/articles.php" class="btn <?= !$status ? 'btn-primary' : '' ?>">Tous</a>
        <a href="/articles.php?status=published" class="btn <?= $status === 'published' ? 'btn-primary' : '' ?>">Publiés</a>
        <a href="/articles.php?status=draft" class="btn <?= $status === 'draft' ? 'btn-primary' : '' ?>">Brouillons</a>
        <a href="/new.php" class="btn">+ Nouvel article</a>
    </p>
</div>

<?php if (empty($articles)): ?>
    <div class="alert alert-info">
        Aucun article trouvé.
        <a href="/new.php">Créer un article</a> ou <a href="/import.php">importer du contenu</a>.
    </div>
<?php else: ?>
    <ul class="article-list">
        <?php foreach ($articles as $article): ?>
            <li class="article-list-item">
                <h3>
                    <a href="/article.php?slug=<?= htmlspecialchars($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a>
                    <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
                </h3>
                <?php if ($article['summary']): ?>
                    <p class="summary"><?= htmlspecialchars(substr($article['summary'], 0, 250)) ?>...</p>
                <?php endif; ?>
                <div class="meta">
                    <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
                    <?php if (!empty($article['categories'])): ?>
                        |
                        <?php foreach ($article['categories'] as $cat): ?>
                            <span class="category-tag"><?= htmlspecialchars($cat['name']) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    |
                    <a href="/edit.php?id=<?= $article['id'] ?>">Modifier</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
