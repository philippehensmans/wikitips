<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <header class="wiki-header">
        <div class="wiki-header-inner">
            <a href="/" class="wiki-logo">
                <div>
                    <h1><?= SITE_NAME ?></h1>
                    <div class="subtitle"><?= SITE_DESCRIPTION ?></div>
                </div>
            </a>
            <form class="wiki-search" action="/search.php" method="get">
                <input type="text" name="q" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit">Rechercher</button>
            </form>
        </div>
    </header>

    <nav class="wiki-nav">
        <a href="/">Accueil</a>
        <a href="/articles.php">Articles</a>
        <a href="/categories.php">Catégories</a>
        <a href="/new.php">Nouvel article</a>
        <a href="/import.php">Importer</a>
    </nav>

    <div class="wiki-container">
        <aside class="wiki-sidebar">
            <div class="sidebar-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="/">Page d'accueil</a></li>
                    <li><a href="/articles.php">Tous les articles</a></li>
                    <li><a href="/articles.php?status=draft">Brouillons</a></li>
                    <li><a href="/new.php">Créer un article</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Catégories</h3>
                <ul>
                    <?php
                    $categoryModel = new Category();
                    $categories = $categoryModel->getAll();
                    foreach ($categories as $cat): ?>
                        <li><a href="/category.php?slug=<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Outils</h3>
                <ul>
                    <li><a href="/import.php">Importer du contenu</a></li>
                    <li><a href="/api/health" target="_blank">État de l'API</a></li>
                </ul>
            </div>
        </aside>

        <main class="wiki-content">
            <?php if (isset($alert)): ?>
                <div class="alert alert-<?= $alert['type'] ?>">
                    <?= htmlspecialchars($alert['message']) ?>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>
    </div>

    <footer class="wiki-footer">
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Veille et analyse sous l'angle des droits humains.</p>
        <p>Les analyses sont générées avec l'aide de l'IA et doivent être vérifiées.</p>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
