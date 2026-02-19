<?php
/**
 * News - Inscription à la newsletter
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Newsletter - ' . SITE_NAME;
$mailchimp = new MailchimpService();
$alert = null;

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'subscribe') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');

        if (!$email) {
            $alert = ['type' => 'error', 'message' => 'Veuillez entrer une adresse email valide.'];
        } elseif (!$mailchimp->isConfigured()) {
            $alert = ['type' => 'error', 'message' => 'Le service de newsletter n\'est pas encore configuré.'];
        } else {
            $result = $mailchimp->subscribe($email, $firstName, $lastName);
            if ($result['success']) {
                $alert = ['type' => 'success', 'message' => $result['message']];
            } else {
                $alert = ['type' => 'error', 'message' => $result['error']];
            }
        }
    } elseif ($_POST['action'] === 'unsubscribe') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $alert = ['type' => 'error', 'message' => 'Veuillez entrer une adresse email valide.'];
        } elseif (!$mailchimp->isConfigured()) {
            $alert = ['type' => 'error', 'message' => 'Le service de newsletter n\'est pas encore configuré.'];
        } else {
            $result = $mailchimp->unsubscribe($email);
            if ($result['success']) {
                $alert = ['type' => 'success', 'message' => $result['message']];
            } else {
                $alert = ['type' => 'error', 'message' => $result['error']];
            }
        }
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Newsletter hebdomadaire</h1>
</div>

<div class="article-section">
    <p>Recevez chaque semaine un résumé des articles publiés sur <?= SITE_NAME ?>,
    avec les liens et un court résumé de chaque article analysé sous l'angle des droits humains.</p>
</div>

<?php if (!$mailchimp->isConfigured()): ?>
    <div class="alert alert-info">
        La newsletter n'est pas encore configurée. L'administrateur doit renseigner les clés Mailchimp dans la configuration.
    </div>
<?php else: ?>

    <!-- Formulaire d'inscription -->
    <div class="article-section">
        <h2>S'inscrire</h2>
        <div class="editor-container">
            <form method="post" action="">
                <input type="hidden" name="action" value="subscribe">
                <div class="form-group">
                    <label for="email">Adresse email *</label>
                    <input type="email" id="email" name="email" required placeholder="votre@email.com">
                </div>
                <div class="form-group">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Votre prénom">
                </div>
                <div class="form-group">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Votre nom">
                </div>
                <p class="help-text" style="font-size: 12px; color: #666; margin-bottom: 15px;">
                    Un email de confirmation vous sera envoyé (double opt-in).
                    Vous pourrez vous désabonner à tout moment.
                </p>
                <button type="submit" class="btn btn-primary">S'inscrire à la newsletter</button>
            </form>
        </div>
    </div>

    <!-- Formulaire de désabonnement -->
    <div class="article-section">
        <h2>Se désabonner</h2>
        <div class="editor-container">
            <form method="post" action="">
                <input type="hidden" name="action" value="unsubscribe">
                <div class="form-group">
                    <label for="unsub_email">Adresse email</label>
                    <input type="email" id="unsub_email" name="email" required placeholder="votre@email.com">
                </div>
                <button type="submit" class="btn btn-danger">Se désabonner</button>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
