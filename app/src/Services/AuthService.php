<?php

// app/src/Services/AuthService.php
namespace App\Services;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use App\Services\UserService;

class AuthService 
{
    private Auth0 $auth0;
    private $userService;
    
    public function __construct() 
    {
        $config = require ROOT_PATH . '/app/config/auth0.php';

        $configuration = new SdkConfiguration([
            'domain' => $config['domain'],
            'clientId' => $config['client_id'],
            'clientSecret' => $config['client_secret'],
            'redirectUri' => $config['redirect_uri'],
            'cookieSecret' => $config['cookie_secret'],
            'scope' => ['openid', 'profile', 'email'],
            'cookieDomain' => 'geo.zefal.com',
            'cookieSecure' => true,
            'cookiePath' => '/',
            'responseType' => 'code'
        ]);
                
        $this->auth0 = new Auth0($configuration);
        $this->userService = new UserService();
    }

    public function loginWithSocial($provider) 
    {
        lecho("AuthService loginSocial");
        try {
            $this->auth0->clear();
            $url = $this->auth0->login(null, ['connection' => $provider, 'scope' => 'openid profile email']);
            return ['status' => 'redirect', 'url' => $url];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function handleCallback() 
    {
        try {
            lecho("HandleCallBack");
            $this->auth0->exchange();
            $userInfo = $this->auth0->getUser();
            lecho($userInfo);
            $user = $this->userService->findOrCreateUser($userInfo);
            lecho($user);

            if($user['status']=="success"){
                // Stocker le userid dans la session Auth0
                $this->auth0->setUser([
                    ...$userInfo,
                    'app_userid' => $user['user']['userid']
                ]);
                lecho("userid",$user['user']['userid']);
            }

            flushBuffer();
            // Rediriger vers l'app avec les données
            header('Location: /?login=success');
            exit();

        } catch (\Exception $e) {
            flushBuffer();
            header('Location: /?login=error&message=' . urlencode($e->getMessage()));
            exit();
        }
    }

    public function handleSession() 
    {
        $session = $this->auth0->getCredentials();
        if ($session && isset($session->user['app_userid'])) {
            // Récupérer les données utilisateur via UserService
            $user = $this->userService->get_user($session->user['app_userid']);
            if($user)
                return ['status' => 'success', 'user' => $user];
            else
                return ['status' => 'error', 'message' => "Unknown user"];
        }
        return ['status' => 'error', 'message' => 'No active session'];
    }

    public function handleLogout() 
    {
        // Nettoyer la session Auth0
        $this->auth0->clear();
        return true;
    }

}