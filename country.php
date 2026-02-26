<?php
/**
 * News - Articles par pays
 */
require_once __DIR__ . '/config.php';

$country = $_GET['name'] ?? '';
$articleModel = new Article();

// Si aucun pays spécifié, afficher la liste de tous les pays
if (!$country) {
    $countries = $articleModel->getAllCountries();
    $pageTitle = 'Articles par pays - ' . SITE_NAME;

    ob_start();
    ?>

    <div class="article-header">
        <h1>Articles par pays</h1>
    </div>

    <?php if (empty($countries)): ?>
        <div class="alert alert-info">
            Aucun pays détecté pour le moment.
        </div>
    <?php else: ?>
        <div class="country-list">
            <?php foreach ($countries as $c): ?>
                <a href="<?= url('country.php?name=' . urlencode($c['country'])) ?>" class="country-tag country-tag-large">
                    <?= htmlspecialchars($c['country']) ?>
                    <span class="country-count">(<?= $c['article_count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

// Afficher les articles d'un pays
$articles = $articleModel->getByCountry($country);
$pageTitle = htmlspecialchars($country) . ' - Articles - ' . SITE_NAME;

ob_start();
?>

<div class="article-header">
    <h1>Articles : <?= htmlspecialchars($country) ?></h1>
    <p><a href="<?= url('country.php') ?>">&larr; Tous les pays</a></p>
</div>

<?php if (empty($articles)): ?>
    <div class="alert alert-info">
        Aucun article trouvé pour ce pays.
    </div>
<?php else: ?>
    <p><?= count($articles) ?> article(s) concernant <?= htmlspecialchars($country) ?></p>
    <ul class="article-list">
        <?php foreach ($articles as $article): ?>
            <li class="article-list-item">
                <h3>
                    <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a>
                    <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
                </h3>
                <?php if ($article['summary']): ?>
                    <?php
                    $summaryClean = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $summaryDisplay = mb_substr($summaryClean, 0, 300);
                    if (mb_strlen($summaryClean) > 300) $summaryDisplay .= '...';
                    ?>
                    <p class="summary"><?= htmlspecialchars($summaryDisplay) ?></p>
                <?php endif; ?>
                <div class="meta">
                    <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
                    <?php if (!empty($article['categories'])): ?>
                        |
                        <?php foreach ($article['categories'] as $cat): ?>
                            <span class="category-tag"><?= htmlspecialchars($cat['name']) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
