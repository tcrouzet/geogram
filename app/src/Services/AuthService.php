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
    private $error = false;
    private $user = null;
    
    public function __construct($user=null) 
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
        $this->user = $user;
        $this->userService = new UserService($user);
    }

    public function loginWithSocial($provider) 
    {
        lecho("AuthService loginSocial");
        try {
            $this->auth0->clear();

            // Créer un state avec les paramètres
            $state = [
                'link' => $_GET['link'] ?? null,
                'telegram' => $_GET['telegram'] ?? null
            ];

            $url = $this->auth0->login(null, [
                'connection' => $provider,
                'scope' => 'openid profile email',
                'state' => base64_encode(json_encode($state))
            ]);
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

            if (isset($_GET['state'])) {
                $params = json_decode(base64_decode($_GET['state']), true);
                $userInfo['link'] = $params['link'] ?? null;
                $userInfo['telegram'] = $params['telegram'] ?? null;
            }

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

    public function handleTelegram()
    {
        lecho("Telegram Auth Data:", $_GET);
        try {            
            $this->userService->updaTelegramUser($_GET);
            flushBuffer();
            header('Location: /user?telegram=success');
            exit;
            
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            lecho("Telegram Auth Error:", $e->getMessage());
            flushBuffer();
            header('Location: /user?telegram=error');
            exit;
        }
    }

    public function getError() {
        return $this->error;
    }
    
}