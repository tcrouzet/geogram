<?php
// /auth/google-callback
session_start();
require_once 'vendor/autoload.php'; // Si tu utilises la bibliothèque Google API PHP client

$client = new Google_Client();
$client->setClientId('YOUR_GOOGLE_CLIENT_ID');
$client->setClientSecret('YOUR_GOOGLE_CLIENT_SECRET');
$client->setRedirectUri('YOUR_REDIRECT_URI'); // L'URL de /auth/google-callback
$client->addScope('email');
$client->addScope('profile');

// Récupérer le code d'autorisation
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    // Vérifier s'il y a une erreur
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Obtenir les informations de l'utilisateur
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        // Récupérer l'email et le nom de l'utilisateur
        $email = $userInfo->email;
        $name = $userInfo->name;

        // Gérer la connexion ou la création de l'utilisateur en base de données
        // ...

        // Redirection après connexion réussie
        header('Location: /dashboard');
        exit();
    }
}

?>