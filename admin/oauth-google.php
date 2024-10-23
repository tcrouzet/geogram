<?php
require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

session_start();

$provider = new Google([
    'clientId'     => 'VOTRE_CLIENT_ID',
    'clientSecret' => 'VOTRE_CLIENT_SECRET',
    'redirectUri'  => 'http://localhost/auth/google-callback',
]);

if (!isset($_GET['code'])) {
    // Si aucun code OAuth, rediriger vers Google
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;

// Vérifier l'état OAuth
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {
    // Obtenir le token et l'utilisateur
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Récupérer les informations de l'utilisateur
    $user = $provider->getResourceOwner($token);

    // Ici, vous pouvez gérer l'utilisateur (enregistrement ou connexion)
    $_SESSION['user_id'] = $user->getId();
    header('Location: /dashboard');
}
?>