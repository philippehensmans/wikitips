<?php
/**
 * Service d'intégration avec l'API Claude
 * Analyse du contenu sous l'angle des droits humains
 */
class ClaudeService {

    private $apiKey;
    private $apiUrl;
    private $model;

    public function __construct() {
        $this->apiKey = CLAUDE_API_KEY;
        $this->apiUrl = CLAUDE_API_URL;
        $this->model = CLAUDE_MODEL;
    }

    /**
     * Analyser un contenu web et générer un article structuré
     */
    public function analyzeContent(string $content, string $sourceUrl = ''): array {
        $prompt = $this->buildAnalysisPrompt($content, $sourceUrl);

        $response = $this->callApi($prompt);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        return $this->parseResponse($response);
    }

    /**
     * Construire le prompt d'analyse
     */
    private function buildAnalysisPrompt(string $content, string $sourceUrl): string {
        return <<<PROMPT
Tu es un expert en droits humains, droits civils et politiques, droits économiques, sociaux et culturels, ainsi qu'en droit international humanitaire. Analyse le contenu suivant et fournis une réponse structurée en JSON.

SOURCE: $sourceUrl

CONTENU À ANALYSER:
$content

---

Réponds UNIQUEMENT avec un objet JSON valide (sans markdown, sans ```json) contenant exactement cette structure:

{
    "title": "Titre proposé pour l'article (concis et informatif)",
    "summary": "Résumé détaillé et approfondi du contenu en 400-500 mots, STRUCTURÉ EN PLUSIEURS PARAGRAPHES (4-5 paragraphes séparés par des doubles retours à la ligne). Chaque paragraphe doit aborder un aspect différent: 1) le contexte historique et actuel, 2) les faits principaux avec des détails significatifs, 3) les acteurs impliqués et leurs positions, 4) les implications pour les droits humains, 5) les perspectives d'évolution. Le résumé doit être suffisamment complet et nuancé pour qu'un lecteur comprenne pleinement le sujet sans lire l'article original.",
    "bluesky_post": "Texte accrocheur pour Bluesky (max 250 caractères, sans hashtags). Doit donner envie de lire l'article en posant une question percutante, en révélant un fait marquant, ou en soulignant l'urgence du sujet. Ne pas simplement résumer, mais interpeller le lecteur.",
    "main_points": [
        "Point principal 1",
        "Point principal 2",
        "Point principal 3",
        "Point principal 4",
        "Point principal 5"
    ],
    "human_rights_analysis": {
        "civil_political_rights": {
            "relevant": true/false,
            "points": ["Point d'attention 1", "Point d'attention 2"],
            "concerns": ["Préoccupation éventuelle"]
        },
        "economic_social_cultural_rights": {
            "relevant": true/false,
            "points": ["Point d'attention 1"],
            "concerns": ["Préoccupation éventuelle"]
        },
        "international_humanitarian_law": {
            "relevant": true/false,
            "points": ["Point d'attention 1"],
            "concerns": ["Préoccupation éventuelle"]
        },
        "overall_assessment": "Évaluation globale sous l'angle des droits humains (2-3 phrases)",
        "recommendations": ["Recommandation 1", "Recommandation 2"]
    },
    "suggested_categories": ["droits-civils-politiques", "non-discrimination"]
}

Les catégories disponibles sont: droits-civils-politiques, droits-economiques-sociaux, droits-culturels, droit-humanitaire, droits-refugies, droits-enfants, droits-femmes, non-discrimination

Assure-toi que le JSON est valide et complet.
PROMPT;
    }

    /**
     * Appeler l'API Claude
     */
    private function callApi(string $prompt): array {
        $data = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'Erreur cURL: ' . $error];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            return ['error' => 'Erreur API (' . $httpCode . '): ' . ($errorData['error']['message'] ?? $response)];
        }

        $result = json_decode($response, true);

        if (!isset($result['content'][0]['text'])) {
            return ['error' => 'Réponse API invalide'];
        }

        return ['text' => $result['content'][0]['text']];
    }

    /**
     * Parser la réponse de Claude
     */
    private function parseResponse(array $response): array {
        if (!isset($response['text'])) {
            return ['error' => 'Réponse vide'];
        }

        $text = trim($response['text']);

        // Nettoyer le JSON si nécessaire
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Erreur de parsing JSON: ' . json_last_error_msg(), 'raw' => $text];
        }

        // Formater les points principaux en HTML
        $mainPointsHtml = '<ul>';
        foreach ($data['main_points'] ?? [] as $point) {
            $mainPointsHtml .= '<li>' . htmlspecialchars($point) . '</li>';
        }
        $mainPointsHtml .= '</ul>';

        // Formater l'analyse des droits humains en HTML
        $analysisHtml = $this->formatHumanRightsAnalysis($data['human_rights_analysis'] ?? []);

        return [
            'title' => $data['title'] ?? 'Sans titre',
            'summary' => $this->formatSummaryAsHtml($data['summary'] ?? ''),
            'bluesky_post' => $data['bluesky_post'] ?? '',
            'main_points' => $mainPointsHtml,
            'main_points_raw' => $data['main_points'] ?? [],
            'human_rights_analysis' => $analysisHtml,
            'human_rights_analysis_raw' => $data['human_rights_analysis'] ?? [],
            'suggested_categories' => $data['suggested_categories'] ?? []
        ];
    }

    /**
     * Formater l'analyse des droits humains en HTML
     */
    private function formatHumanRightsAnalysis(array $analysis): string {
        $html = '<div class="human-rights-analysis">';

        // Droits civils et politiques
        if (!empty($analysis['civil_political_rights']['relevant'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Droits civils et politiques</h4>';
            $html .= $this->formatAnalysisPoints($analysis['civil_political_rights']);
            $html .= '</div>';
        }

        // Droits économiques, sociaux et culturels
        if (!empty($analysis['economic_social_cultural_rights']['relevant'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Droits économiques, sociaux et culturels</h4>';
            $html .= $this->formatAnalysisPoints($analysis['economic_social_cultural_rights']);
            $html .= '</div>';
        }

        // Droit international humanitaire
        if (!empty($analysis['international_humanitarian_law']['relevant'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Droit international humanitaire</h4>';
            $html .= $this->formatAnalysisPoints($analysis['international_humanitarian_law']);
            $html .= '</div>';
        }

        // Évaluation globale
        if (!empty($analysis['overall_assessment'])) {
            $html .= '<div class="analysis-section overall">';
            $html .= '<h4>Évaluation globale</h4>';
            $html .= '<p>' . htmlspecialchars($analysis['overall_assessment']) . '</p>';
            $html .= '</div>';
        }

        // Recommandations
        if (!empty($analysis['recommendations'])) {
            $html .= '<div class="analysis-section recommendations">';
            $html .= '<h4>Recommandations</h4>';
            $html .= '<ul>';
            foreach ($analysis['recommendations'] as $rec) {
                $html .= '<li>' . htmlspecialchars($rec) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Formater les points d'une section d'analyse
     */
    private function formatAnalysisPoints(array $section): string {
        $html = '';

        if (!empty($section['points'])) {
            $html .= '<div class="points"><strong>Points d\'attention:</strong><ul>';
            foreach ($section['points'] as $point) {
                $html .= '<li>' . htmlspecialchars($point) . '</li>';
            }
            $html .= '</ul></div>';
        }

        if (!empty($section['concerns'])) {
            $html .= '<div class="concerns"><strong>Préoccupations:</strong><ul>';
            foreach ($section['concerns'] as $concern) {
                $html .= '<li class="concern">' . htmlspecialchars($concern) . '</li>';
            }
            $html .= '</ul></div>';
        }

        return $html;
    }

    /**
     * Formater le résumé en HTML avec des paragraphes
     */
    private function formatSummaryAsHtml(string $summary): string {
        if (empty($summary)) {
            return '';
        }

        // Séparer par doubles retours à la ligne (paragraphes)
        $paragraphs = preg_split('/\n\s*\n/', trim($summary));

        $html = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                // Convertir les retours simples en <br> et échapper le HTML
                $content = nl2br(htmlspecialchars($paragraph));
                $html .= '<p>' . $content . '</p>';
            }
        }

        return $html ?: '<p>' . htmlspecialchars($summary) . '</p>';
    }

    /**
     * Générer une recension (article PHH) à partir d'un article existant
     * Format: 4000 signes (espaces non compris), titre, chapô, intertitres, hashtags
     */
    public function generateReview(array $article): array {
        $prompt = $this->buildReviewPrompt($article);

        $response = $this->callApi($prompt);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        return $this->parseReviewResponse($response);
    }

    /**
     * Construire le prompt pour la génération de recension
     */
    private function buildReviewPrompt(array $article): string {
        $title = $article['title'] ?? '';
        $summary = $article['summary'] ?? '';
        $mainPoints = strip_tags($article['main_points'] ?? '');
        $humanRightsAnalysis = strip_tags($article['human_rights_analysis'] ?? '');
        $sourceUrl = $article['source_url'] ?? '';
        $content = $article['content'] ?? '';

        $sourceReference = '';
        if (!empty($sourceUrl)) {
            $sourceReference = "\n\nIMPORTANT: L'article fait référence à cette source: $sourceUrl - Tu dois mentionner cette référence dans ton texte de manière naturelle.";
        }

        return <<<PROMPT
Tu es un rédacteur expert pour une publication sur les droits humains (PHH - Publication sur les droits Humains et Humanitaires). Tu dois rédiger une RECENSION à partir des informations suivantes.

TITRE DE L'ARTICLE: $title

RÉSUMÉ: $summary

POINTS PRINCIPAUX: $mainPoints

ANALYSE DROITS HUMAINS: $humanRightsAnalysis

CONTENU ADDITIONNEL: $content
$sourceReference

---

CONSIGNES STRICTES:

1. LONGUEUR: Exactement 4000 signes (caractères) ESPACES NON COMPRIS. Compte précisément les caractères sans les espaces.

2. STRUCTURE OBLIGATOIRE:
   - Un TITRE accrocheur et informatif (différent du titre original)
   - Un CHAPÔ (introduction de 2-3 phrases qui accroche le lecteur)
   - 3-4 INTERTITRES pour structurer le texte
   - Des paragraphes fluides et bien articulés sous chaque intertitre
   - 5-7 HASHTAGS pertinents à la fin

3. STYLE:
   - Ton journalistique engagé mais factuel
   - Vocabulaire accessible mais précis
   - Phrases percutantes
   - Mise en contexte des enjeux droits humains

4. RÉFÉRENCE SOURCE: Si une URL source est fournie, l'intégrer naturellement dans le texte (ex: "comme le rapporte [nom du média]" ou "selon l'article publié sur [site]")

Réponds UNIQUEMENT avec un objet JSON valide (sans markdown, sans ```json) contenant exactement cette structure:

{
    "titre": "Le titre de la recension",
    "chapo": "Le chapô accrocheur de 2-3 phrases",
    "sections": [
        {
            "intertitre": "Premier intertitre",
            "contenu": "Contenu du premier paragraphe..."
        },
        {
            "intertitre": "Deuxième intertitre",
            "contenu": "Contenu du deuxième paragraphe..."
        },
        {
            "intertitre": "Troisième intertitre",
            "contenu": "Contenu du troisième paragraphe..."
        }
    ],
    "hashtags": ["#DroitsHumains", "#Justice", "#Humanitaire", "..."],
    "nombre_signes_hors_espaces": 4000
}

Assure-toi que le JSON est valide et que le nombre total de signes (hors espaces) est d'environ 4000.
PROMPT;
    }

    /**
     * Parser la réponse de génération de recension
     */
    private function parseReviewResponse(array $response): array {
        if (!isset($response['text'])) {
            return ['error' => 'Réponse vide'];
        }

        $text = trim($response['text']);

        // Nettoyer le JSON si nécessaire
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Erreur de parsing JSON: ' . json_last_error_msg(), 'raw' => $text];
        }

        // Formater en HTML pour l'affichage
        $html = '<div class="review-content">';

        // Chapô
        $html .= '<p class="review-chapo"><strong>' . htmlspecialchars($data['chapo'] ?? '') . '</strong></p>';

        // Sections avec intertitres
        foreach ($data['sections'] ?? [] as $section) {
            $html .= '<h3 class="review-intertitre">' . htmlspecialchars($section['intertitre'] ?? '') . '</h3>';
            $html .= '<p>' . htmlspecialchars($section['contenu'] ?? '') . '</p>';
        }

        // Hashtags
        if (!empty($data['hashtags'])) {
            $html .= '<p class="review-hashtags">' . htmlspecialchars(implode(' ', $data['hashtags'])) . '</p>';
        }

        $html .= '</div>';

        // Texte brut pour copier-coller
        $plainText = $data['titre'] . "\n\n";
        $plainText .= $data['chapo'] . "\n\n";
        foreach ($data['sections'] ?? [] as $section) {
            $plainText .= "## " . $section['intertitre'] . "\n\n";
            $plainText .= $section['contenu'] . "\n\n";
        }
        if (!empty($data['hashtags'])) {
            $plainText .= implode(' ', $data['hashtags']);
        }

        return [
            'titre' => $data['titre'] ?? 'Sans titre',
            'chapo' => $data['chapo'] ?? '',
            'sections' => $data['sections'] ?? [],
            'hashtags' => $data['hashtags'] ?? [],
            'nombre_signes' => $data['nombre_signes_hors_espaces'] ?? 0,
            'html' => $html,
            'plain_text' => $plainText
        ];
    }
}
