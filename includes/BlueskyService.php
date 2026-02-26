<?php
/**
 * Service d'intégration Bluesky (AT Protocol)
 * Permet de publier automatiquement sur Bluesky
 */

class BlueskyService
{
    private string $apiUrl = 'https://bsky.social/xrpc';
    private string $identifier;
    private string $password;
    private ?string $accessJwt = null;
    private ?string $did = null;

    public function __construct()
    {
        $this->identifier = defined('BLUESKY_IDENTIFIER') ? BLUESKY_IDENTIFIER : '';
        $this->password = defined('BLUESKY_APP_PASSWORD') ? BLUESKY_APP_PASSWORD : '';
    }

    /**
     * Vérifie si Bluesky est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->identifier) && !empty($this->password);
    }

    /**
     * Authentification et obtention du token
     */
    private function authenticate(): bool
    {
        if ($this->accessJwt !== null) {
            return true;
        }

        if (!$this->isConfigured()) {
            return false;
        }

        $response = $this->request('com.atproto.server.createSession', [
            'identifier' => $this->identifier,
            'password' => $this->password
        ]);

        if (isset($response['accessJwt']) && isset($response['did'])) {
            $this->accessJwt = $response['accessJwt'];
            $this->did = $response['did'];
            return true;
        }

        return false;
    }

    /**
     * Publie un post sur Bluesky
     */
    public function createPost(string $text, ?string $url = null, ?string $title = null, ?string $description = null, ?string $thumbUrl = null): array
    {
        if (!$this->authenticate()) {
            return ['success' => false, 'error' => 'Authentification Bluesky échouée. Vérifiez vos identifiants.'];
        }

        // Construire le post
        $post = [
            '$type' => 'app.bsky.feed.post',
            'text' => $text,
            'createdAt' => gmdate('Y-m-d\TH:i:s\Z'),
            'langs' => ['fr']
        ];

        // Ajouter les facets pour les liens et hashtags
        $facets = $this->parseFacets($text);
        if (!empty($facets)) {
            $post['facets'] = $facets;
        }

        // Ajouter une carte de lien si URL fournie
        if ($url) {
            $embed = $this->createLinkEmbed($url, $title, $description, $thumbUrl);
            if ($embed) {
                $post['embed'] = $embed;
            }
        }

        $response = $this->request('com.atproto.repo.createRecord', [
            'repo' => $this->did,
            'collection' => 'app.bsky.feed.post',
            'record' => $post
        ], true);

        if (isset($response['uri'])) {
            // Construire l'URL du post
            $postId = basename($response['uri']);
            $handle = $this->identifier;
            $postUrl = "https://bsky.app/profile/{$handle}/post/{$postId}";

            return [
                'success' => true,
                'uri' => $response['uri'],
                'cid' => $response['cid'] ?? null,
                'url' => $postUrl
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? $response['error'] ?? 'Erreur inconnue'
        ];
    }

    /**
     * Upload une image comme blob sur Bluesky
     * Retourne le blob reference ou null en cas d'échec
     */
    private function uploadImageBlob(string $imageUrl): ?array
    {
        // Télécharger l'image
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; WikiTips/1.0)',
        ]);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if (!$imageData || $httpCode !== 200) {
            return null;
        }

        // Déterminer le type MIME
        $mimeType = $contentType ?: 'image/jpeg';
        // Nettoyer le type MIME (enlever charset etc.)
        if (strpos($mimeType, ';') !== false) {
            $mimeType = trim(explode(';', $mimeType)[0]);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            $mimeType = 'image/jpeg';
        }

        // Limiter à 1 Mo pour Bluesky
        if (strlen($imageData) > 1000000) {
            return null;
        }

        // Upload vers Bluesky
        $url = "{$this->apiUrl}/com.atproto.repo.uploadBlob";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $imageData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: {$mimeType}",
                "Authorization: Bearer {$this->accessJwt}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $decoded = json_decode($response, true);
        return $decoded['blob'] ?? null;
    }

    /**
     * Crée un embed de type lien externe avec vignette optionnelle
     */
    private function createLinkEmbed(string $url, ?string $title = null, ?string $description = null, ?string $thumbUrl = null): ?array
    {
        $external = [
            'uri' => $url,
            'title' => $title ?? $url,
            'description' => $description ?? ''
        ];

        // Ajouter la vignette si disponible
        if ($thumbUrl) {
            $blob = $this->uploadImageBlob($thumbUrl);
            if ($blob) {
                $external['thumb'] = $blob;
            }
        }

        return [
            '$type' => 'app.bsky.embed.external',
            'external' => $external
        ];
    }

    /**
     * Parse le texte pour extraire les facets (liens, mentions, hashtags)
     */
    private function parseFacets(string $text): array
    {
        $facets = [];

        // Détecter les URLs
        preg_match_all('#https?://[^\s<>\[\]]+#u', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $url = $match[0];
            $start = $this->getUtf8ByteOffset($text, $match[1]);
            $end = $start + strlen($url);

            $facets[] = [
                'index' => [
                    'byteStart' => $start,
                    'byteEnd' => $end
                ],
                'features' => [[
                    '$type' => 'app.bsky.richtext.facet#link',
                    'uri' => $url
                ]]
            ];
        }

        // Détecter les hashtags
        preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $index => $match) {
            $hashtag = $match[0];
            $tag = $matches[1][$index][0];
            $start = $this->getUtf8ByteOffset($text, $match[1]);
            $end = $start + strlen($hashtag);

            $facets[] = [
                'index' => [
                    'byteStart' => $start,
                    'byteEnd' => $end
                ],
                'features' => [[
                    '$type' => 'app.bsky.richtext.facet#tag',
                    'tag' => $tag
                ]]
            ];
        }

        return $facets;
    }

    /**
     * Convertit un offset de caractères en offset de bytes UTF-8
     */
    private function getUtf8ByteOffset(string $text, int $charOffset): int
    {
        $substring = mb_substr($text, 0, $charOffset, 'UTF-8');
        return strlen($substring);
    }

    /**
     * Génère le texte du post à partir d'un article
     */
    public function formatArticlePost(array $article, string $articleUrl): string
    {
        // Utiliser le texte Bluesky personnalisé s'il existe, sinon fallback sur le résumé
        $blueskyPost = $article['bluesky_post'] ?? '';

        if (!empty($blueskyPost)) {
            // Utiliser le texte accrocheur généré par Claude
            $text = $blueskyPost;
        } else {
            // Fallback: utiliser le titre + résumé tronqué (pour les anciens articles)
            $title = $article['title'] ?? 'Article';
            // Supprimer les balises HTML et décoder les entités du résumé
            $summary = html_entity_decode(strip_tags($article['summary'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $maxSummaryLength = 200;
            if (mb_strlen($summary) > $maxSummaryLength) {
                $summary = mb_substr($summary, 0, $maxSummaryLength - 3) . '...';
            }
            $text = "📰 {$title}";
            if (!empty($summary)) {
                $text .= "\n\n{$summary}";
            }
        }

        $text .= "\n\n#DroitsHumains #News";

        return $text;
    }

    /**
     * Publie un article sur Bluesky
     */
    public function shareArticle(array $article, string $articleUrl): array
    {
        $text = $this->formatArticlePost($article, $articleUrl);
        $title = $article['title'] ?? 'News';
        // Supprimer les balises HTML et décoder les entités de la description
        $description = html_entity_decode(strip_tags($article['summary'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (mb_strlen($description) > 150) {
            $description = mb_substr($description, 0, 147) . '...';
        }

        // Utiliser l'image og:image de l'article comme vignette
        $thumbUrl = $article['og_image'] ?? null;

        return $this->createPost($text, $articleUrl, $title, $description, $thumbUrl);
    }

    /**
     * Effectue une requête à l'API Bluesky
     */
    private function request(string $endpoint, array $data, bool $authenticated = false): array
    {
        $url = "{$this->apiUrl}/{$endpoint}";

        $headers = [
            'Content-Type: application/json'
        ];

        if ($authenticated && $this->accessJwt) {
            $headers[] = "Authorization: Bearer {$this->accessJwt}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "Erreur cURL: {$error}"];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => $decoded['message'] ?? $decoded['error'] ?? "Erreur HTTP {$httpCode}",
                'details' => $decoded
            ];
        }

        return $decoded ?? [];
    }
}
