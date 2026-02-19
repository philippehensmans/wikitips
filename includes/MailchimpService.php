<?php
/**
 * Service d'intégration Mailchimp
 * Gère les abonnés et l'envoi de newsletters hebdomadaires
 */

class MailchimpService
{
    private string $apiKey;
    private string $listId;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = defined('MAILCHIMP_API_KEY') ? MAILCHIMP_API_KEY : '';
        $this->listId = defined('MAILCHIMP_LIST_ID') ? MAILCHIMP_LIST_ID : '';

        // Extraire le datacenter de la clé API (ex: us21)
        $dc = '';
        if (!empty($this->apiKey) && str_contains($this->apiKey, '-')) {
            $dc = substr($this->apiKey, strrpos($this->apiKey, '-') + 1);
        }
        $this->apiUrl = "https://{$dc}.api.mailchimp.com/3.0";
    }

    /**
     * Vérifie si Mailchimp est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->listId);
    }

    /**
     * Ajouter un abonné à la liste Mailchimp
     */
    public function subscribe(string $email, string $firstName = '', string $lastName = ''): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mailchimp n\'est pas configuré.'];
        }

        $subscriberHash = md5(strtolower(trim($email)));

        $data = [
            'email_address' => $email,
            'status' => 'pending', // Double opt-in
            'merge_fields' => []
        ];

        if (!empty($firstName)) {
            $data['merge_fields']['FNAME'] = $firstName;
        }
        if (!empty($lastName)) {
            $data['merge_fields']['LNAME'] = $lastName;
        }

        // Utiliser PUT pour créer ou mettre à jour (upsert)
        $response = $this->request(
            "lists/{$this->listId}/members/{$subscriberHash}",
            $data,
            'PUT'
        );

        if (isset($response['id'])) {
            return [
                'success' => true,
                'status' => $response['status'],
                'message' => $response['status'] === 'pending'
                    ? 'Un email de confirmation vous a été envoyé.'
                    : 'Vous êtes inscrit à la newsletter.'
            ];
        }

        return [
            'success' => false,
            'error' => $response['detail'] ?? $response['title'] ?? 'Erreur inconnue'
        ];
    }

    /**
     * Désabonner un membre
     */
    public function unsubscribe(string $email): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mailchimp n\'est pas configuré.'];
        }

        $subscriberHash = md5(strtolower(trim($email)));

        $response = $this->request(
            "lists/{$this->listId}/members/{$subscriberHash}",
            ['status' => 'unsubscribed'],
            'PATCH'
        );

        if (isset($response['id']) && $response['status'] === 'unsubscribed') {
            return ['success' => true, 'message' => 'Vous avez été désabonné.'];
        }

        return [
            'success' => false,
            'error' => $response['detail'] ?? 'Erreur lors du désabonnement'
        ];
    }

    /**
     * Récupérer les statistiques de la liste
     */
    public function getListStats(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mailchimp n\'est pas configuré.'];
        }

        $response = $this->request("lists/{$this->listId}", null, 'GET');

        if (isset($response['id'])) {
            return [
                'success' => true,
                'name' => $response['name'],
                'member_count' => $response['stats']['member_count'] ?? 0,
                'unsubscribe_count' => $response['stats']['unsubscribe_count'] ?? 0,
                'open_rate' => $response['stats']['open_rate'] ?? 0,
                'click_rate' => $response['stats']['click_rate'] ?? 0
            ];
        }

        return ['success' => false, 'error' => $response['detail'] ?? 'Erreur'];
    }

    /**
     * Récupérer la liste des abonnés
     */
    public function getMembers(int $count = 50, int $offset = 0): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mailchimp n\'est pas configuré.'];
        }

        $response = $this->request(
            "lists/{$this->listId}/members?count={$count}&offset={$offset}&status=subscribed",
            null,
            'GET'
        );

        if (isset($response['members'])) {
            return [
                'success' => true,
                'members' => $response['members'],
                'total_items' => $response['total_items'] ?? 0
            ];
        }

        return ['success' => false, 'error' => $response['detail'] ?? 'Erreur'];
    }

    /**
     * Créer et envoyer une campagne newsletter
     */
    public function sendWeeklyNewsletter(array $articles): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mailchimp n\'est pas configuré.'];
        }

        if (empty($articles)) {
            return ['success' => false, 'error' => 'Aucun article à envoyer.'];
        }

        // 1. Créer la campagne
        $campaign = $this->createCampaign($articles);
        if (!$campaign['success']) {
            return $campaign;
        }

        $campaignId = $campaign['campaign_id'];

        // 2. Définir le contenu HTML
        $html = $this->buildNewsletterHtml($articles);
        $contentResult = $this->setCampaignContent($campaignId, $html);
        if (!$contentResult['success']) {
            return $contentResult;
        }

        // 3. Envoyer la campagne
        $sendResult = $this->sendCampaign($campaignId);

        return $sendResult;
    }

    /**
     * Créer une campagne Mailchimp
     */
    private function createCampaign(array $articles): array
    {
        $articleCount = count($articles);
        $weekStart = date('d/m', strtotime('-7 days'));
        $weekEnd = date('d/m/Y');

        $subject = SITE_NAME . " - {$articleCount} article(s) cette semaine ({$weekStart} - {$weekEnd})";

        $data = [
            'type' => 'regular',
            'recipients' => [
                'list_id' => $this->listId
            ],
            'settings' => [
                'subject_line' => $subject,
                'from_name' => defined('MAILCHIMP_FROM_NAME') ? MAILCHIMP_FROM_NAME : SITE_NAME,
                'reply_to' => $this->getListEmail(),
                'title' => "Newsletter hebdomadaire - " . date('d/m/Y')
            ]
        ];

        $response = $this->request('campaigns', $data, 'POST');

        if (isset($response['id'])) {
            return ['success' => true, 'campaign_id' => $response['id']];
        }

        return [
            'success' => false,
            'error' => 'Erreur création campagne: ' . ($response['detail'] ?? $response['title'] ?? 'inconnue')
        ];
    }

    /**
     * Définir le contenu HTML d'une campagne
     */
    private function setCampaignContent(string $campaignId, string $html): array
    {
        $response = $this->request(
            "campaigns/{$campaignId}/content",
            ['html' => $html],
            'PUT'
        );

        if (isset($response['html'])) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'error' => 'Erreur contenu campagne: ' . ($response['detail'] ?? 'inconnue')
        ];
    }

    /**
     * Envoyer une campagne
     */
    private function sendCampaign(string $campaignId): array
    {
        $response = $this->request("campaigns/{$campaignId}/actions/send", null, 'POST');

        // L'API retourne 204 No Content en cas de succès (corps vide)
        if (empty($response) || !isset($response['status'])) {
            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'message' => 'Newsletter envoyée avec succès.'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur envoi: ' . ($response['detail'] ?? $response['title'] ?? 'inconnue')
        ];
    }

    /**
     * Construire le HTML de la newsletter
     */
    public function buildNewsletterHtml(array $articles): string
    {
        $siteName = SITE_NAME;
        $siteUrl = SITE_URL;
        $weekStart = date('d/m', strtotime('-7 days'));
        $weekEnd = date('d/m/Y');
        $articleCount = count($articles);

        $articlesHtml = '';
        foreach ($articles as $article) {
            $title = htmlspecialchars($article['title']);
            $articleUrl = $siteUrl . '/article.php?slug=' . htmlspecialchars($article['slug']);

            // Résumé court (200 caractères max)
            $summary = '';
            if (!empty($article['summary'])) {
                $summaryClean = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $summary = mb_substr($summaryClean, 0, 250);
                if (mb_strlen($summaryClean) > 250) {
                    $summary .= '...';
                }
            }

            // Catégories
            $categories = '';
            if (!empty($article['categories'])) {
                $catNames = array_map(fn($c) => htmlspecialchars($c['name']), $article['categories']);
                $categories = implode(' | ', $catNames);
            }

            $date = date('d/m/Y', strtotime($article['created_at']));

            $articlesHtml .= <<<HTML
            <tr>
                <td style="padding: 20px 0; border-bottom: 1px solid #e0e0e0;">
                    <h3 style="margin: 0 0 8px 0; font-size: 18px;">
                        <a href="{$articleUrl}" style="color: #3366cc; text-decoration: none;">{$title}</a>
                    </h3>
                    <p style="margin: 0 0 8px 0; color: #555; font-size: 14px; line-height: 1.5;">{$summary}</p>
                    <p style="margin: 0; font-size: 12px; color: #888;">
                        {$date}
                        {$categories}
                    </p>
                </td>
            </tr>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$siteName} - Newsletter</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Georgia, 'Times New Roman', serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f6f6f6;">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 4px;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #3366cc; padding: 25px 30px; border-radius: 4px 4px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: normal;">
                                {$siteName}
                            </h1>
                            <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.85); font-size: 13px;">
                                Veille et analyse sous l'angle des droits humains
                            </p>
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td style="padding: 25px 30px 15px 30px;">
                            <p style="margin: 0; font-size: 15px; color: #333; line-height: 1.6;">
                                Voici les <strong>{$articleCount} article(s)</strong> publiés entre le {$weekStart} et le {$weekEnd}.
                            </p>
                        </td>
                    </tr>

                    <!-- Articles -->
                    <tr>
                        <td style="padding: 0 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                {$articlesHtml}
                            </table>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td style="padding: 25px 30px;" align="center">
                            <a href="{$siteUrl}" style="display: inline-block; background-color: #3366cc; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                Voir tous les articles
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; border-top: 1px solid #e0e0e0; border-radius: 0 0 4px 4px;">
                            <p style="margin: 0; font-size: 12px; color: #888; text-align: center;">
                                {$siteName} - Les analyses sont g&eacute;n&eacute;r&eacute;es avec l'aide de l'IA et doivent &ecirc;tre v&eacute;rifi&eacute;es.
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #aaa; text-align: center;">
                                <a href="*|UNSUB|*" style="color: #888;">Se d&eacute;sabonner</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Récupérer l'email de réponse de la liste
     */
    private function getListEmail(): string
    {
        $response = $this->request("lists/{$this->listId}", null, 'GET');
        return $response['campaign_defaults']['from_email'] ?? 'noreply@example.com';
    }

    /**
     * Effectue une requête à l'API Mailchimp
     */
    private function request(string $endpoint, ?array $data, string $method = 'POST'): array
    {
        $url = "{$this->apiUrl}/{$endpoint}";

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => "anystring:{$this->apiKey}",
            CURLOPT_TIMEOUT => 30
        ];

        switch (strtoupper($method)) {
            case 'GET':
                $options[CURLOPT_HTTPGET] = true;
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                if ($data !== null) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
                break;
            case 'PATCH':
                $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "Erreur cURL: {$error}"];
        }

        // 204 No Content = succès sans corps (ex: envoi de campagne)
        if ($httpCode === 204) {
            return [];
        }

        return json_decode($response, true) ?? [];
    }
}
