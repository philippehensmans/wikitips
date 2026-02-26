<?php
/**
 * News - Créer un nouvel article
 */
require_once __DIR__ . '/config.php';

// Authentification requise
$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Nouvel article - ' . SITE_NAME;

$categoryModel = new Category();
$categories = $categoryModel->getAll();

// Vérifier si Bluesky est configuré
$bluesky = new BlueskyService();
$blueskyConfigured = $bluesky->isConfigured();
$blueskyAutoShare = defined('BLUESKY_AUTO_SHARE') && BLUESKY_AUTO_SHARE;

$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $mainPoints = trim($_POST['main_points'] ?? '');
    $humanRightsAnalysis = trim($_POST['human_rights_analysis'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sourceUrl = trim($_POST['source_url'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categoryIds = $_POST['categories'] ?? [];

    if (empty($title)) {
        $alert = ['type' => 'error', 'message' => 'Le titre est requis.'];
    } else {
        $ogImageField = trim($_POST['og_image'] ?? '');
        $countryField = trim($_POST['country'] ?? '');

        // Si pas d'image og:image manuelle, tenter de la récupérer depuis la source
        if (empty($ogImageField) && !empty($sourceUrl)) {
            $ogImageField = fetchOgImage($sourceUrl);
        }

        $articleModel = new Article();
        $articleId = $articleModel->create([
            'title' => $title,
            'summary' => $summary,
            'main_points' => $mainPoints,
            'human_rights_analysis' => $humanRightsAnalysis,
            'content' => $content,
            'source_url' => $sourceUrl,
            'status' => $status,
            'og_image' => $ogImageField ?: null,
            'country' => $countryField ?: null,
            'categories' => array_map('intval', $categoryIds)
        ]);

        $article = $articleModel->getById($articleId);
        $slug = $article['slug'];

        // Partage sur Bluesky si demandé
        $shareBluesky = isset($_POST['share_bluesky']) && $_POST['share_bluesky'] === '1';
        $blueskyParam = '';

        if ($shareBluesky && $blueskyConfigured && $status === 'published') {
            // Construire l'URL de l'article
            $articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
            $articleUrl .= $_SERVER['HTTP_HOST'];
            $articleUrl .= url('article.php?slug=' . urlencode($slug));

            $result = $bluesky->shareArticle($article, $articleUrl);

            if ($result['success']) {
                $blueskyParam = '&bluesky=success';
            } else {
                $blueskyParam = '&bluesky=error&error=' . urlencode($result['error'] ?? 'Erreur inconnue');
            }
        }

        header('Location: ' . url('article.php?slug=' . $slug . $blueskyParam));
        exit;
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Créer un nouvel article</h1>
</div>

<div class="editor-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Titre *</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="source_url">URL source</label>
            <input type="url" id="source_url" name="source_url" placeholder="https://..." value="<?= htmlspecialchars($_POST['source_url'] ?? '') ?>">
            <p class="help-text">L'URL de la source originale (optionnel). L'image de partage sera récupérée automatiquement.</p>
        </div>

        <div class="form-group">
            <label for="og_image">Image de partage (og:image)</label>
            <input type="url" id="og_image" name="og_image" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($_POST['og_image'] ?? '') ?>">
            <p class="help-text">Optionnel. Si vide, l'image sera récupérée automatiquement depuis l'URL source.</p>
        </div>

        <div class="form-group">
            <label for="country">Pays concerné</label>
            <input type="text" id="country" name="country" placeholder="Ex: France, Palestine, Ukraine..." value="<?= htmlspecialchars($_POST['country'] ?? '') ?>">
            <p class="help-text">Optionnel. Détecté automatiquement lors de l'import via l'analyse IA.</p>
        </div>

        <div class="form-group">
            <label for="main_points">Points principaux (HTML)</label>
            <textarea id="main_points" name="main_points" rows="6"><?= htmlspecialchars($_POST['main_points'] ?? '') ?></textarea>
            <p class="help-text">Utilisez des balises &lt;ul&gt;&lt;li&gt; pour la liste des points</p>
        </div>

        <div class="form-group">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" rows="4" class="tinymce-summary"><?= $_POST['summary'] ?? '' ?></textarea>
        </div>

        <div class="form-group">
            <label for="human_rights_analysis">Analyse des droits humains (HTML)</label>
            <textarea id="human_rights_analysis" name="human_rights_analysis" class="large"><?= htmlspecialchars($_POST['human_rights_analysis'] ?? '') ?></textarea>
            <p class="help-text">Analyse sous l'angle des droits humains, droit international humanitaire, etc.</p>
        </div>

        <div class="form-group">
            <label for="content">Contenu</label>
            <textarea id="content" name="content" class="tinymce-editor"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Catégories</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                            <?= in_array($cat['id'], $_POST['categories'] ?? []) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="draft" <?= ($_POST['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
        </div>

        <?php if ($blueskyConfigured): ?>
        <div class="form-group bluesky-option">
            <label class="checkbox-label">
                <input type="checkbox" name="share_bluesky" value="1" <?= ($blueskyAutoShare || isset($_POST['share_bluesky'])) ? 'checked' : '' ?>>
                🦋 Partager sur Bluesky à la publication
            </label>
            <p class="help-text">L'article sera automatiquement partagé sur Bluesky si le statut est "Publié".</p>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Créer l'article</button>
            <a href="<?= url() ?>" class="btn">Annuler</a>
        </div>
    </form>
</div>

<!-- TinyMCE (self-hosted) -->
<script src="<?= url('assets/js/tinymce/tinymce.min.js') ?>"></script>
<script>
// TinyMCE pour le résumé
tinymce.init({
    selector: '.tinymce-summary',
    language: 'fr_FR',
    language_url: '<?= url('assets/js/tinymce/langs/fr_FR.js') ?>',
    height: 300,
    menubar: false,
    plugins: [
        'autolink', 'lists', 'link', 'charmap', 'codesample',
        'searchreplace', 'visualblocks', 'code', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | codesample code | removeformat',
    block_formats: 'Paragraphe=p; Titre 1=h1; Titre 2=h2; Titre 3=h3; Préformaté=pre',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
    setup: function(editor) {
        editor.on('change', function() {
            tinymce.triggerSave();
        });
    }
});

// TinyMCE pour le contenu (configuration complète)
tinymce.init({
    selector: '.tinymce-editor',
    language: 'fr_FR',
    language_url: '<?= url('assets/js/tinymce/langs/fr_FR.js') ?>',
    height: 400,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'codesample', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image link | codesample code | removeformat | help',
    block_formats: 'Paragraphe=p; Titre 1=h1; Titre 2=h2; Titre 3=h3; Titre 4=h4; Préformaté=pre',
    images_upload_url: '<?= url('api/index.php?action=upload') ?>',
    images_upload_credentials: true,
    automatic_uploads: true,
    file_picker_types: 'image',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
    image_caption: true,
    image_advtab: true,
    setup: function(editor) {
        editor.on('change', function() {
            tinymce.triggerSave();
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
