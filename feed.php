<?php
/**
 * Flux RSS 2.0 - News Droits Humains
 */
require_once __DIR__ . '/config.php';

$articleModel = new Article();
$articles = $articleModel->getAll('published', 30);

header('Content-Type: application/rss+xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
    <title><?= htmlspecialchars(SITE_NAME) ?></title>
    <link><?= htmlspecialchars(SITE_URL) ?></link>
    <description><?= htmlspecialchars(SITE_DESCRIPTION) ?></description>
    <language>fr</language>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <atom:link href="<?= htmlspecialchars(SITE_URL . url('feed.php')) ?>" rel="self" type="application/rss+xml"/>
<?php foreach ($articles as $article): ?>
    <item>
        <title><?= htmlspecialchars($article['title']) ?></title>
        <link><?= htmlspecialchars(SITE_URL . url('article.php') . '?slug=' . urlencode($article['slug'])) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars(SITE_URL . url('article.php') . '?slug=' . urlencode($article['slug'])) ?></guid>
        <pubDate><?= date('r', strtotime($article['created_at'])) ?></pubDate>
<?php if (!empty($article['summary'])): ?>
        <description><?= htmlspecialchars($article['summary']) ?></description>
<?php endif; ?>
<?php if (!empty($article['categories'])): ?>
<?php foreach ($article['categories'] as $cat): ?>
        <category><?= htmlspecialchars($cat['name']) ?></category>
<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($article['source_url'])): ?>
        <dc:source><?= htmlspecialchars($article['source_url']) ?></dc:source>
<?php endif; ?>
    </item>
<?php endforeach; ?>
</channel>
</rss>
