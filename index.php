<?php
/**
 * WikiTips - Page d'accueil
 */
require_once __DIR__ . '/config.php';

$pageTitle = SITE_NAME . ' - Accueil';

$articleModel = new Article();
$recentArticles = $articleModel->getAll('published', 10);
$draftCount = count($articleModel->getAll('draft'));

ob_start();
?>

<div class="article-header">
    <h1>Bienvenue sur <?= SITE_NAME ?></h1>
</div>

<div class="article-section">
    <p>
        Ce wiki est dédié à la veille et à l'analyse d'informations sous l'angle des droits humains.
        Chaque article publié ici est analysé pour identifier les points d'attention concernant :
    </p>
    <ul>
        <li><strong>Les droits civils et politiques</strong> - libertés fondamentales, droit de vote, liberté d'expression...</li>
        <li><strong>Les droits économiques, sociaux et culturels</strong> - droit au travail, à la santé, à l'éducation...</li>
        <li><strong>Le droit international humanitaire</strong> - Conventions de Genève, protection des civils en conflit armé...</li>
    </ul>
</div>

<?php if ($draftCount > 0): ?>
<div class="alert alert-info">
    Vous avez <strong><?= $draftCount ?></strong> article(s) en brouillon.
    <a href="<?= url('articles.php?status=draft') ?>">Voir les brouillons</a>
</div>
<?php endif; ?>

<div class="article-section">
    <h2>Articles récents</h2>

    <?php if (empty($recentArticles)): ?>
        <p>Aucun article publié pour le moment.</p>
        <p><a href="<?= url('new.php') ?>" class="btn btn-primary">Créer votre premier article</a></p>
    <?php else: ?>
        <ul class="article-list">
            <?php foreach ($recentArticles as $article): ?>
                <li class="article-list-item">
                    <h3><a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                    <?php if ($article['summary']): ?>
                        <p class="summary"><?= htmlspecialchars(substr($article['summary'], 0, 200)) ?>...</p>
                    <?php endif; ?>
                    <div class="meta">
                        Publié le <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
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
</div>

<div class="article-section">
    <h2>Comment utiliser ce wiki</h2>

    <h3>Méthode 1 : Extension Chrome</h3>
    <p>
        Installez l'extension Chrome pour capturer rapidement du contenu depuis n'importe quelle page web.
        L'extension enverra automatiquement le contenu à l'API qui l'analysera via Claude AI.
    </p>

    <h3>Méthode 2 : Import manuel</h3>
    <p>
        Utilisez la page <a href="<?= url('import.php') ?>">Importer</a> pour coller du texte ou une URL.
        Le contenu sera analysé et un brouillon d'article sera créé.
    </p>

    <h3>Méthode 3 : Création directe</h3>
    <p>
        Créez un article directement via la page <a href="<?= url('new.php') ?>">Nouvel article</a>.
        Vous pouvez rédiger manuellement le contenu et l'analyse.
    </p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
