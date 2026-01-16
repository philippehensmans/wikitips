<?php
/**
 * API REST pour WikiTips
 * Gestion des articles et analyse via Claude
 */

require_once __DIR__ . '/../config.php';

// Headers CORS et JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Router simple - supporte mod_rewrite ET accès direct
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Supprimer les préfixes possibles
$path = preg_replace('#^.*/api(/index\.php)?#', '', $path);
$path = trim($path, '/');

// Aussi supporter ?action=xxx pour les serveurs sans mod_rewrite
if (empty($path) && isset($_GET['action'])) {
    $path = $_GET['action'];
}

$segments = $path ? explode('/', $path) : [];
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer le body JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $response = handleRequest($method, $segments, $input);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Gérer les requêtes
 */
function handleRequest(string $method, array $segments, array $input): array {
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? null;

    switch ($resource) {
        case 'articles':
            return handleArticles($method, $id, $input);

        case 'categories':
            return handleCategories($method, $id);

        case 'analyze':
            return handleAnalyze($method, $input);

        case 'generate-review':
            return handleGenerateReview($method, $id);

        case 'upload':
            return handleUpload($method);

        case 'health':
            return ['status' => 'ok', 'timestamp' => date('c')];

        default:
            http_response_code(404);
            return ['error' => true, 'message' => 'Endpoint non trouvé'];
    }
}

/**
 * Gérer les articles
 */
function handleArticles(string $method, ?string $id, array $input): array {
    $article = new Article();

    switch ($method) {
        case 'GET':
            if ($id) {
                $result = is_numeric($id) ? $article->getById((int)$id) : $article->getBySlug($id);
                if (!$result) {
                    http_response_code(404);
                    return ['error' => true, 'message' => 'Article non trouvé'];
                }
                return ['success' => true, 'data' => $result];
            }

            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? null;

            if ($search) {
                $results = $article->search($search);
            } else {
                $results = $article->getAll($status);
            }

            return ['success' => true, 'data' => $results];

        case 'POST':
            if (empty($input['title'])) {
                http_response_code(400);
                return ['error' => true, 'message' => 'Le titre est requis'];
            }

            $articleId = $article->create($input);
            $newArticle = $article->getById($articleId);

            http_response_code(201);
            return ['success' => true, 'data' => $newArticle];

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                return ['error' => true, 'message' => 'ID requis'];
            }

            $existing = $article->getById((int)$id);
            if (!$existing) {
                http_response_code(404);
                return ['error' => true, 'message' => 'Article non trouvé'];
            }

            $article->update((int)$id, $input);
            $updated = $article->getById((int)$id);

            return ['success' => true, 'data' => $updated];

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                return ['error' => true, 'message' => 'ID requis'];
            }

            $article->delete((int)$id);
            return ['success' => true, 'message' => 'Article supprimé'];

        default:
            http_response_code(405);
            return ['error' => true, 'message' => 'Méthode non autorisée'];
    }
}

/**
 * Gérer les catégories
 */
function handleCategories(string $method, ?string $id): array {
    if ($method !== 'GET') {
        http_response_code(405);
        return ['error' => true, 'message' => 'Méthode non autorisée'];
    }

    $category = new Category();

    if ($id) {
        $result = is_numeric($id) ? $category->getById((int)$id) : $category->getBySlug($id);
        if (!$result) {
            http_response_code(404);
            return ['error' => true, 'message' => 'Catégorie non trouvée'];
        }

        $result['articles'] = $category->getArticles($result['id']);
        return ['success' => true, 'data' => $result];
    }

    return ['success' => true, 'data' => $category->getAll()];
}

/**
 * Analyser du contenu via Claude
 */
function handleAnalyze(string $method, array $input): array {
    if ($method !== 'POST') {
        http_response_code(405);
        return ['error' => true, 'message' => 'Méthode non autorisée'];
    }

    // Vérifier la clé API (pour l'extension)
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($apiKey !== API_SECRET_KEY && API_SECRET_KEY !== 'change_this_secret_key_in_production') {
        http_response_code(401);
        return ['error' => true, 'message' => 'Clé API invalide'];
    }

    if (empty($input['content'])) {
        http_response_code(400);
        return ['error' => true, 'message' => 'Contenu requis'];
    }

    $claude = new ClaudeService();
    $result = $claude->analyzeContent(
        $input['content'],
        $input['source_url'] ?? ''
    );

    if (isset($result['error'])) {
        http_response_code(500);
        return ['error' => true, 'message' => $result['error']];
    }

    // Optionnellement créer l'article directement
    if (!empty($input['create_article']) && $input['create_article'] === true) {
        $category = new Category();
        $categoryIds = [];

        if (!empty($result['suggested_categories'])) {
            $cats = $category->getBySlugs($result['suggested_categories']);
            $categoryIds = array_column($cats, 'id');
        }

        $article = new Article();
        $articleId = $article->create([
            'title' => $result['title'],
            'source_url' => $input['source_url'] ?? null,
            'source_content' => $input['content'],
            'summary' => $result['summary'],
            'bluesky_post' => $result['bluesky_post'] ?? null,
            'main_points' => $result['main_points'],
            'human_rights_analysis' => $result['human_rights_analysis'],
            'categories' => $categoryIds,
            'status' => 'draft'
        ]);

        $result['article_id'] = $articleId;
        $result['article_created'] = true;
    }

    return ['success' => true, 'data' => $result];
}

/**
 * Générer une recension (article PHH) à partir d'un article existant
 */
function handleGenerateReview(string $method, ?string $id): array {
    if ($method !== 'POST') {
        http_response_code(405);
        return ['error' => true, 'message' => 'Méthode non autorisée'];
    }

    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        return ['error' => true, 'message' => 'ID d\'article requis'];
    }

    // Vérifier que la clé API Claude est configurée
    if (CLAUDE_API_KEY === 'YOUR_API_KEY_HERE') {
        http_response_code(500);
        return ['error' => true, 'message' => 'La clé API Claude n\'est pas configurée'];
    }

    $article = new Article();
    $articleData = $article->getById((int)$id);

    if (!$articleData) {
        http_response_code(404);
        return ['error' => true, 'message' => 'Article non trouvé'];
    }

    $claude = new ClaudeService();
    $result = $claude->generateReview($articleData);

    if (isset($result['error'])) {
        http_response_code(500);
        return ['error' => true, 'message' => $result['error']];
    }

    // Sauvegarder la recension en base de données
    $article->update((int)$id, [
        'review_phh' => json_encode($result)
    ]);

    return ['success' => true, 'data' => $result];
}

/**
 * Gérer l'upload d'images (pour TinyMCE)
 */
function handleUpload(string $method): array {
    if ($method !== 'POST') {
        http_response_code(405);
        return ['error' => true, 'message' => 'Méthode non autorisée'];
    }

    // Vérifier l'authentification (session)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        return ['error' => true, 'message' => 'Authentification requise'];
    }

    // Vérifier qu'un fichier a été uploadé
    if (empty($_FILES['file'])) {
        http_response_code(400);
        return ['error' => true, 'message' => 'Aucun fichier uploadé'];
    }

    $file = $_FILES['file'];

    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        return ['error' => true, 'message' => 'Erreur lors de l\'upload: ' . $file['error']];
    }

    // Vérifier le type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        return ['error' => true, 'message' => 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP'];
    }

    // Vérifier la taille (max 5 Mo)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        return ['error' => true, 'message' => 'Fichier trop volumineux (max 5 Mo)'];
    }

    // Générer un nom de fichier unique
    switch ($mimeType) {
        case 'image/jpeg':
            $extension = 'jpg';
            break;
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
        default:
            $extension = 'jpg';
    }

    $filename = date('Y-m-d_His_') . bin2hex(random_bytes(8)) . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/';
    $uploadPath = $uploadDir . $filename;

    // Créer le dossier si nécessaire
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        http_response_code(500);
        return ['error' => true, 'message' => 'Erreur lors de la sauvegarde du fichier'];
    }

    // Retourner l'URL de l'image (format TinyMCE)
    $imageUrl = url('uploads/' . $filename);

    return ['location' => $imageUrl];
}
