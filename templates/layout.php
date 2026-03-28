<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>

    <!-- Meta description pour SEO/GEO -->
    <?php if (!empty($ogDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars(mb_substr(strip_tags(html_entity_decode($ogDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 160)) ?>">
    <?php else: ?>
    <meta name="description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php if (!empty($ogUrl)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($ogUrl) ?>">
    <?php else: ?>
    <link rel="canonical" href="<?= htmlspecialchars(SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <?php endif; ?>

    <!-- Open Graph / LinkedIn / Facebook -->
    <meta property="og:type" content="<?= $ogType ?? 'website' ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle ?? $pageTitle ?? SITE_NAME) ?>">
    <?php if (!empty($ogDescription)): ?>
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <?php endif; ?>
    <?php if (!empty($ogUrl)): ?>
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">
    <?php endif; ?>
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="<?= !empty($ogImage) ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle ?? $pageTitle ?? SITE_NAME) ?>">
    <?php if (!empty($ogDescription)): ?>
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <?php endif; ?>
    <?php if (!empty($ogImage)): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>

    <!-- JSON-LD Organization (base) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": <?= json_encode(SITE_NAME) ?>,
        "url": <?= json_encode(SITE_URL . '/') ?>,
        "description": <?= json_encode(SITE_DESCRIPTION) ?>
    }
    </script>

    <!-- JSON-LD structuré spécifique à la page -->
    <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json">
    <?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>

    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= time() ?>">
    <link rel="icon" href="<?= url('assets/images/favicon.ico') ?>" type="image/x-icon">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(SITE_NAME) ?>" href="<?= url('feed.php') ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= url('sitemap.php') ?>">
    <?php if (!empty(MATOMO_TRACKER_URL) && !empty(MATOMO_JS_URL) && !empty(MATOMO_SITE_ID)): ?>
    <!-- Matomo -->
    <script>
      (function() {
        function initTracking() {
          var _paq = window._paq = window._paq || [];
          <?php if (!empty(MATOMO_COOKIE_DOMAIN)): ?>
          _paq.push(["setCookieDomain", <?= json_encode(MATOMO_COOKIE_DOMAIN) ?>]);
          <?php endif; ?>
          _paq.push(['trackPageView']);
          _paq.push(['enableLinkTracking']);
          _paq.push(['alwaysUseSendBeacon']);
          _paq.push(['setTrackerUrl', <?= json_encode(MATOMO_TRACKER_URL) ?>]);
          _paq.push(['setSiteId', <?= json_encode(MATOMO_SITE_ID) ?>]);
          var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
          g.async=true; g.src=<?= json_encode(MATOMO_JS_URL) ?>; s.parentNode.insertBefore(g,s);
        }
        if (document.prerendering) {
          document.addEventListener('prerenderingchange', initTracking, {once: true});
        } else {
          initTracking();
        }
      })();
    </script>
    <noscript><img referrerpolicy="no-referrer-when-downgrade" src="<?= htmlspecialchars(MATOMO_TRACKER_URL) ?>?idsite=<?= htmlspecialchars(MATOMO_SITE_ID) ?>&amp;rec=1" style="border:0;position:absolute" alt="" /></noscript>
    <!-- End Matomo Code -->
    <?php endif; ?>
</head>
<body>
    <?php
    $auth = new Auth();
    $isLoggedIn = $auth->isLoggedIn();
    $currentUser = $isLoggedIn ? $auth->getUser() : null;
    ?>

    <header class="wiki-header">
        <div class="wiki-header-inner">
            <a href="<?= url() ?>" class="wiki-logo">
                <div>
                    <h1><?= SITE_NAME ?></h1>
                    <div class="subtitle"><?= SITE_DESCRIPTION ?></div>
                </div>
            </a>

            <!-- Bouton menu hamburger pour mobile -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menu" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <div class="wiki-header-right" id="headerRight">
                <form class="wiki-search" action="<?= url('search.php') ?>" method="get">
                    <input type="text" name="q" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit">Rechercher</button>
                </form>
                <div class="user-menu">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= url('profile.php') ?>"><?= htmlspecialchars($currentUser['username']) ?></a>
                        <a href="<?= url('logout.php') ?>">Déconnexion</a>
                    <?php else: ?>
                        <a href="<?= url('login.php') ?>">Connexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <nav class="wiki-nav">
        <div class="wiki-nav-inner">
            <a href="<?= url() ?>">Accueil</a>
            <a href="<?= url('articles.php') ?>">Articles</a>
            <a href="<?= url('categories.php') ?>">Catégories</a>
            <?php if ($isLoggedIn): ?>
                <a href="<?= url('new.php') ?>">Nouvel article</a>
                <a href="<?= url('import.php') ?>">Importer</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="wiki-container">
        <!-- Overlay pour fermer le menu mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="wiki-sidebar" id="wikSidebar">
            <div class="sidebar-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="<?= url() ?>">Page d'accueil</a></li>
                    <li><a href="<?= url('articles.php') ?>">Tous les articles</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="<?= url('articles.php?status=draft') ?>">Brouillons</a></li>
                        <li><a href="<?= url('new.php') ?>">Créer un article</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Catégories</h3>
                <ul>
                    <?php
                    $categoryModel = new Category();
                    $categories = $categoryModel->getAll();
                    foreach ($categories as $cat): ?>
                        <li><a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="sidebar-section">
                <h3>Outils</h3>
                <ul>
                    <li><a href="<?= url('import.php') ?>">Importer du contenu</a></li>
                    <li><a href="<?= url('api/index.php?action=health') ?>" target="_blank">État de l'API</a></li>
                    <?php if ($auth->isAdmin()): ?>
                        <li><a href="<?= url('users.php') ?>">Gérer les utilisateurs</a></li>
                        <li><a href="<?= url('subscribers.php') ?>">Newsletter / Abonnés</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="sidebar-section">
                <h3>Newsletter</h3>
                <ul>
                    <li><a href="<?= url('newsletter.php') ?>">S'inscrire</a></li>
                    <li><a href="<?= url('feed.php') ?>">Flux RSS</a></li>
                </ul>
            </div>

            <?php if (!$isLoggedIn): ?>
            <div class="sidebar-section">
                <h3>Compte</h3>
                <ul>
                    <li><a href="<?= url('login.php') ?>">Se connecter</a></li>
                </ul>
            </div>
            <?php endif; ?>
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

    <script src="<?= url('assets/js/app.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
