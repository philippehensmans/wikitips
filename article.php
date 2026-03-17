<?php
/**
 * News - Affichage d'un article
 */
require_once __DIR__ . '/config.php';

$bluesky = new BlueskyService();
$blueskyConfigured = $bluesky->isConfigured();
$blueskyMessage = $_GET['bluesky'] ?? '';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url());
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
    <p>L'article demandé n'existe pas. <a href="<?= url() ?>">Retour à l'accueil</a></p>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

// Enregistrer la vue
$articleModel->recordView($article['id']);
$viewCount = $articleModel->getViewCount($article['id']);

$pageTitle = htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

// Construire l'URL de l'article pour le partage et Open Graph
$articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
$articleUrl .= $_SERVER['HTTP_HOST'];
$articleUrl .= url('article.php?slug=' . urlencode($article['slug']));

// Open Graph pour les réseaux sociaux (LinkedIn, Facebook, etc.)
$ogTitle = $article['title'];
$ogType = 'article';
$ogUrl = $articleUrl;
if (!empty($article['summary'])) {
    $ogDescription = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Image Open Graph pour les réseaux sociaux
// Priorité : og_image stocké > première image du contenu > première image du résumé
if (!empty($article['og_image'])) {
    $ogImage = $article['og_image'];
} else {
    $ogImage = extractFirstImage($article['content'] ?? '')
        ?? extractFirstImage($article['summary'] ?? '')
        ?? extractFirstImage($article['main_points'] ?? '');
}

// Message WhatsApp
$whatsappText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    // Supprimer les balises HTML et décoder les entités du résumé
    $summaryClean = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $summaryShort = mb_substr($summaryClean, 0, 150);
    if (mb_strlen($summaryClean) > 150) {
        $summaryShort .= '...';
    }
    $whatsappText .= $summaryShort . "\n\n";
}
$whatsappText .= "🔗 " . $articleUrl;
$whatsappUrl = 'https://wa.me/?text=' . rawurlencode($whatsappText);

// URL Facebook
$facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($articleUrl);

// Message Facebook (sera copié dans le presse-papiers)
$facebookText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryCleanFb = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $facebookText .= $summaryCleanFb . "\n\n";
}
$facebookText .= "🔗 " . $articleUrl;

// URL LinkedIn
$linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($articleUrl);

// Message LinkedIn (sera copié dans le presse-papiers)
$linkedinText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryCleanLi = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $linkedinText .= $summaryCleanLi . "\n\n";
}
$linkedinText .= "🔗 " . $articleUrl;

// URL X (Twitter)
$twitterText = $article['title'];
$twitterUrl = 'https://x.com/intent/post?text=' . rawurlencode($twitterText) . '&url=' . rawurlencode($articleUrl);

// Message Threads (sera copié dans le presse-papiers, Threads n'a pas d'API de partage web)
$threadsText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryCleanThreads = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $summaryShortThreads = mb_substr($summaryCleanThreads, 0, 200);
    if (mb_strlen($summaryCleanThreads) > 200) {
        $summaryShortThreads .= '...';
    }
    $threadsText .= $summaryShortThreads . "\n\n";
}
$threadsText .= "🔗 " . $articleUrl;
$threadsUrl = 'https://www.threads.net/intent/post?text=' . rawurlencode($threadsText);

ob_start();
?>

<?php if ($blueskyMessage === 'success'): ?>
<div class="success-message">
    ✓ Article partagé sur Bluesky avec succès !
</div>
<?php elseif ($blueskyMessage === 'error'): ?>
<div class="error-message">
    ✗ Erreur lors du partage sur Bluesky. <?= htmlspecialchars($_GET['error'] ?? '') ?>
</div>
<?php endif; ?>

<div class="article-header">
    <div class="article-actions">
        <a href="<?= url('edit.php?id=' . $article['id']) ?>">Modifier</a>
        <a href="<?= htmlspecialchars($whatsappUrl) ?>" class="btn-whatsapp" target="_blank" title="Partager sur WhatsApp">💬 WhatsApp</a>
        <a href="#" onclick="shareOnFacebook(); return false;" class="btn-facebook" title="Partager sur Facebook">📘 Facebook</a>
        <a href="#" onclick="shareOnLinkedin(); return false;" class="btn-linkedin" title="Partager sur LinkedIn">💼 LinkedIn</a>
        <a href="<?= htmlspecialchars($twitterUrl) ?>" class="btn-twitter" target="_blank" title="Partager sur X (Twitter)">𝕏 Twitter</a>
        <a href="<?= htmlspecialchars($threadsUrl) ?>" class="btn-threads" target="_blank" title="Partager sur Threads">🧵 Threads</a>
        <?php if ($blueskyConfigured): ?>
        <a href="<?= url('share-bluesky.php?id=' . $article['id']) ?>" class="btn-bluesky" title="Partager sur Bluesky">🦋 Bluesky</a>
        <?php endif; ?>
        <a href="#" onclick="confirmDelete(<?= $article['id'] ?>); return false;">Supprimer</a>
    </div>
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <div class="article-meta">
        <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
        <?php if (!empty($article['country'])): ?>
            &bull; <a href="<?= url('country.php?name=' . urlencode($article['country'])) ?>" class="country-tag"><?= htmlspecialchars($article['country']) ?></a>
        <?php endif; ?>
        &bull; <?= $viewCount ?> vue<?= $viewCount > 1 ? 's' : '' ?>
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
        <div class="infobox-row">
            <div class="infobox-label">Vues</div>
            <div class="infobox-value"><?= $viewCount ?></div>
        </div>
        <?php if (!empty($article['country'])): ?>
        <div class="infobox-row">
            <div class="infobox-label">Pays</div>
            <div class="infobox-value"><a href="<?= url('country.php?name=' . urlencode($article['country'])) ?>" class="country-tag"><?= htmlspecialchars($article['country']) ?></a></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($article['categories'])): ?>
        <div class="infobox-row">
            <div class="infobox-label">Catégories</div>
            <div class="infobox-value">
                <?php foreach ($article['categories'] as $cat): ?>
                    <a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a><br>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Préparer le texte pour la synthèse vocale (points principaux + résumé)
$ttsText = '';
if (!empty($article['main_points'])) {
    $ttsText .= "Points principaux. " . html_entity_decode(strip_tags($article['main_points']), ENT_QUOTES | ENT_HTML5, 'UTF-8') . " ";
}
if (!empty($article['summary'])) {
    $ttsText .= "Résumé. " . html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
$ttsText = trim(preg_replace('/\s+/', ' ', $ttsText));
?>

<?php if (!empty($ttsText)): ?>
<div class="tts-player" id="tts-player">
    <div class="tts-header">Écouter cet article</div>
    <div class="tts-controls">
        <button type="button" class="tts-btn" id="tts-play" onclick="ttsPlay()" title="Lire">&#9654; Lire</button>
        <button type="button" class="tts-btn" id="tts-pause" onclick="ttsPause()" style="display:none" title="Pause">&#10074;&#10074; Pause</button>
        <button type="button" class="tts-btn" id="tts-resume" onclick="ttsResume()" style="display:none" title="Reprendre">&#9654; Reprendre</button>
        <button type="button" class="tts-btn tts-btn-stop" id="tts-stop" onclick="ttsStop()" style="display:none" title="Arrêter">&#9632; Arrêter</button>
        <span class="tts-status" id="tts-status"></span>
    </div>
    <div class="tts-settings">
        <label for="tts-speed">Vitesse :</label>
        <select id="tts-speed" onchange="ttsUpdateRate()">
            <option value="0.75">0.75x</option>
            <option value="1" selected>1x</option>
            <option value="1.25">1.25x</option>
            <option value="1.5">1.5x</option>
        </select>
        <label for="tts-voice">Voix :</label>
        <select id="tts-voice"></select>
    </div>
</div>
<?php endif; ?>

<?php if ($article['main_points']): ?>
<div class="article-section">
    <h2>Points principaux</h2>
    <?= $article['main_points'] ?>
</div>
<?php endif; ?>

<?php if ($article['summary']): ?>
<div class="article-section">
    <h2>Résumé</h2>
    <?= $article['summary'] ?>
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
    <div class="article-content"><?= $article['content'] ?></div>
</div>
<?php endif; ?>

<?php if (!empty($article['categories'])): ?>
<div class="article-categories">
    <strong>Catégories :</strong>
    <?php foreach ($article['categories'] as $cat): ?>
        <a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>" class="category-tag"><?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Données pour le partage Facebook
const facebookText = <?= json_encode($facebookText) ?>;
const facebookUrl = <?= json_encode($facebookUrl) ?>;

// Données pour le partage LinkedIn
const linkedinText = <?= json_encode($linkedinText) ?>;
const linkedinUrl = <?= json_encode($linkedinUrl) ?>;

function shareOnFacebook() {
    // Copier le texte dans le presse-papiers
    navigator.clipboard.writeText(facebookText).then(() => {
        // Ouvrir la fenêtre de partage Facebook
        window.open(facebookUrl, '_blank', 'width=600,height=400');
        // Afficher un message
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication Facebook.');
    }).catch(err => {
        // Fallback pour les navigateurs sans clipboard API
        const textarea = document.createElement('textarea');
        textarea.value = facebookText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        window.open(facebookUrl, '_blank', 'width=600,height=400');
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication Facebook.');
    });
}

function shareOnLinkedin() {
    // Copier le texte dans le presse-papiers
    navigator.clipboard.writeText(linkedinText).then(() => {
        // Ouvrir la fenêtre de partage LinkedIn
        window.open(linkedinUrl, '_blank', 'width=600,height=500');
        // Afficher un message
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication LinkedIn.');
    }).catch(err => {
        // Fallback pour les navigateurs sans clipboard API
        const textarea = document.createElement('textarea');
        textarea.value = linkedinText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        window.open(linkedinUrl, '_blank', 'width=600,height=500');
        alert('📋 Texte copié dans le presse-papiers !\n\nCollez-le (Ctrl+V) dans votre publication LinkedIn.');
    });
}

// Text-to-Speech
const ttsText = <?= json_encode($ttsText ?? '') ?>;
let ttsUtterance = null;
let ttsPlaying = false;

function ttsPopulateVoices() {
    const select = document.getElementById('tts-voice');
    if (!select) return;
    const voices = speechSynthesis.getVoices();
    select.innerHTML = '';
    const frVoices = voices.filter(v => v.lang.startsWith('fr'));
    const voiceList = frVoices.length > 0 ? frVoices : voices;
    voiceList.forEach((voice, i) => {
        const opt = document.createElement('option');
        opt.value = i;
        opt.textContent = voice.name + ' (' + voice.lang + ')';
        opt.dataset.voiceName = voice.name;
        if (voice.default || (frVoices.length > 0 && i === 0)) opt.selected = true;
        select.appendChild(opt);
    });
    select.dataset.pool = frVoices.length > 0 ? 'fr' : 'all';
}

if (typeof speechSynthesis !== 'undefined') {
    speechSynthesis.onvoiceschanged = ttsPopulateVoices;
    ttsPopulateVoices();
} else {
    const player = document.getElementById('tts-player');
    if (player) player.style.display = 'none';
}

function ttsGetSelectedVoice() {
    const select = document.getElementById('tts-voice');
    if (!select || !select.value) return null;
    const voices = speechSynthesis.getVoices();
    const frVoices = voices.filter(v => v.lang.startsWith('fr'));
    const pool = select.dataset.pool === 'fr' ? frVoices : voices;
    return pool[parseInt(select.value)] || null;
}

function ttsPlay() {
    if (!ttsText) return;
    speechSynthesis.cancel();
    ttsUtterance = new SpeechSynthesisUtterance(ttsText);
    ttsUtterance.rate = parseFloat(document.getElementById('tts-speed').value);
    const voice = ttsGetSelectedVoice();
    if (voice) ttsUtterance.voice = voice;
    ttsUtterance.lang = 'fr-FR';

    ttsUtterance.onend = function() { ttsResetUI(); };
    ttsUtterance.onerror = function() { ttsResetUI(); };

    speechSynthesis.speak(ttsUtterance);
    ttsPlaying = true;
    document.getElementById('tts-play').style.display = 'none';
    document.getElementById('tts-resume').style.display = 'none';
    document.getElementById('tts-pause').style.display = '';
    document.getElementById('tts-stop').style.display = '';
    document.getElementById('tts-status').textContent = 'Lecture en cours...';
}

function ttsPause() {
    speechSynthesis.pause();
    document.getElementById('tts-pause').style.display = 'none';
    document.getElementById('tts-resume').style.display = '';
    document.getElementById('tts-status').textContent = 'En pause';
}

function ttsResume() {
    speechSynthesis.resume();
    document.getElementById('tts-resume').style.display = 'none';
    document.getElementById('tts-pause').style.display = '';
    document.getElementById('tts-status').textContent = 'Lecture en cours...';
}

function ttsStop() {
    speechSynthesis.cancel();
    ttsResetUI();
}

function ttsResetUI() {
    ttsPlaying = false;
    document.getElementById('tts-play').style.display = '';
    document.getElementById('tts-pause').style.display = 'none';
    document.getElementById('tts-resume').style.display = 'none';
    document.getElementById('tts-stop').style.display = 'none';
    document.getElementById('tts-status').textContent = '';
}

function ttsUpdateRate() {
    if (ttsPlaying) {
        // Relancer avec la nouvelle vitesse
        ttsPlay();
    }
}

function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        fetch('<?= url('api/index.php?action=articles') ?>' + '/' + id, { method: 'DELETE' })
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
