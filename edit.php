<?php
/**
 * News - Modifier un article
 */
require_once __DIR__ . '/config.php';

// Authentification requise
$auth = new Auth();
$auth->requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . url('articles.php'));
    exit;
}

$articleModel = new Article();
$article = $articleModel->getById($id);

if (!$article) {
    header('Location: ' . url('articles.php'));
    exit;
}

$pageTitle = 'Modifier : ' . htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

$categoryModel = new Category();
$categories = $categoryModel->getAll();
$articleCategoryIds = array_column($article['categories'], 'id');

$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $mainPoints = trim($_POST['main_points'] ?? '');
    $humanRightsAnalysis = trim($_POST['human_rights_analysis'] ?? '');
    $contentField = trim($_POST['content'] ?? '');
    $sourceUrl = trim($_POST['source_url'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categoryIds = $_POST['categories'] ?? [];

    if (empty($title)) {
        $alert = ['type' => 'error', 'message' => 'Le titre est requis.'];
    } else {
        $ogImageField = trim($_POST['og_image'] ?? '');

        $articleModel->update($id, [
            'title' => $title,
            'summary' => $summary,
            'main_points' => $mainPoints,
            'human_rights_analysis' => $humanRightsAnalysis,
            'content' => $contentField,
            'source_url' => $sourceUrl,
            'status' => $status,
            'og_image' => $ogImageField ?: null,
            'categories' => array_map('intval', $categoryIds)
        ]);

        $article = $articleModel->getById($id);
        $articleCategoryIds = array_column($article['categories'], 'id');
        $alert = ['type' => 'success', 'message' => 'Article mis à jour avec succès.'];
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Modifier : <?= htmlspecialchars($article['title']) ?></h1>
    <div class="article-meta">
        <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>">Voir l'article</a>
    </div>
</div>

<div class="editor-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Titre *</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($article['title']) ?>">
        </div>

        <div class="form-group">
            <label for="source_url">URL source</label>
            <input type="url" id="source_url" name="source_url" placeholder="https://..." value="<?= htmlspecialchars($article['source_url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="og_image">Image de partage (og:image)</label>
            <input type="url" id="og_image" name="og_image" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($article['og_image'] ?? '') ?>">
            <p class="help-text">URL de l'image affichée lors du partage sur Facebook, LinkedIn, WhatsApp, etc. Récupérée automatiquement depuis la source lors de l'import.</p>
            <?php if (!empty($article['og_image'])): ?>
            <div style="margin-top: 8px;">
                <img src="<?= htmlspecialchars($article['og_image']) ?>" alt="Aperçu og:image" style="max-width: 300px; max-height: 200px; border: 1px solid #a2a9b1; border-radius: 4px;">
            </div>
            <?php endif; ?>
            <?php if (!empty($article['source_url']) && empty($article['og_image'])): ?>
            <button type="button" class="btn" style="margin-top: 8px;" onclick="fetchOgImageFromSource()">Récupérer depuis la source</button>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="main_points">Points principaux (HTML)</label>
            <textarea id="main_points" name="main_points" rows="6"><?= htmlspecialchars($article['main_points'] ?? '') ?></textarea>
            <p class="help-text">Utilisez des balises &lt;ul&gt;&lt;li&gt; pour la liste des points</p>
        </div>

        <div class="form-group">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" rows="4" class="tinymce-summary"><?= $article['summary'] ?? '' ?></textarea>
        </div>

        <div class="form-group">
            <label for="human_rights_analysis">Analyse des droits humains (HTML)</label>
            <textarea id="human_rights_analysis" name="human_rights_analysis" class="large"><?= htmlspecialchars($article['human_rights_analysis'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="content">Contenu</label>
            <textarea id="content" name="content" class="tinymce-editor"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Catégories</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                            <?= in_array($cat['id'], $articleCategoryIds) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>" class="btn">Annuler</a>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Supprimer</button>
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

function fetchOgImageFromSource() {
    var sourceUrl = document.getElementById('source_url').value;
    if (!sourceUrl) {
        alert('Veuillez renseigner l\'URL source d\'abord.');
        return;
    }
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Recherche en cours...';

    fetch('<?= url('api/index.php?action=fetch-og-image') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: sourceUrl })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.textContent = 'Récupérer depuis la source';
        if (data.success && data.og_image) {
            document.getElementById('og_image').value = data.og_image;
            alert('Image trouvée et ajoutée !');
        } else {
            alert('Aucune image og:image trouvée sur cette page.');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = 'Récupérer depuis la source';
        alert('Erreur lors de la récupération.');
    });
}

function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        fetch('<?= url('api/index.php?action=articles') ?>/<?= $id ?>', { method: 'DELETE' })
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
