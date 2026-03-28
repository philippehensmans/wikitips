<?php
/**
 * Sitemap XML dynamique pour SEO/GEO
 * Génère un sitemap conforme au protocole sitemaps.org
 */
require_once __DIR__ . '/config.php';

$articleModel = new Article();
$articles = $articleModel->getAll('published', 1000);

$categoryModel = new Category();
$categories = $categoryModel->getAll();

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">

    <!-- Page d'accueil -->
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url()) ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Page articles -->
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url('articles.php')) ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Page catégories -->
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url('categories.php')) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Catégories individuelles -->
<?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url('category.php?slug=' . urlencode($cat['slug']))) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>

    <!-- Articles publiés -->
<?php foreach ($articles as $article): ?>
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url('article.php?slug=' . urlencode($article['slug']))) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($article['updated_at'] ?? $article['created_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
        <news:news>
            <news:publication>
                <news:name><?= htmlspecialchars(SITE_NAME) ?></news:name>
                <news:language>fr</news:language>
            </news:publication>
            <news:publication_date><?= date('Y-m-d', strtotime($article['created_at'])) ?></news:publication_date>
            <news:title><?= htmlspecialchars($article['title']) ?></news:title>
<?php if (!empty($article['categories'])): ?>
            <news:keywords><?= htmlspecialchars(implode(', ', array_map(fn($c) => $c['name'], $article['categories']))) ?></news:keywords>
<?php endif; ?>
        </news:news>
    </url>
<?php endforeach; ?>

    <!-- Flux RSS -->
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url('feed.php')) ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Newsletter -->
    <url>
        <loc><?= htmlspecialchars(SITE_URL . url('newsletter.php')) ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>
</urlset>
