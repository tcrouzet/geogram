<?php
require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\Facebook;

session_start();

$provider = new Facebook([
    'clientId'     => 'VOTRE_CLIENT_ID',
    'clientSecret' => 'VOTRE_CLIENT_SECRET',
    'redirectUri'  => 'http://localhost/auth/facebook-callback',
]);

if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;

} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    $user = $provider->getResourceOwner($token);

    $_SESSION['user_id'] = $user->getId();
    header('Location: /dashboard');
}
?>