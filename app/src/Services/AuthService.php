<?php

// app/src/Services/AuthService.php
namespace App\Services;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

class AuthService 
{
    private Auth0 $auth0;
    
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
    }

    public function loginWithCredentials($email, $password) 
    {
        try {
            return $this->auth0->authentication()->login(
                $email,
                $password,
                'Username-Password-Authentication',
                ['scope' => 'openid profile email']
            );
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function loginWithSocial($provider) 
    {
        lecho("AuthService loginSocial");
        try {
            $this->auth0->clear();
            $url = $this->auth0->login(null, ['connection' => $provider]);
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
            lecho("UserInfo:", $userInfo);


        } catch (\Exception $e) {
            lecho("Auth0 error:", $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function handleCallbackO($params) 
    {
        try {
            lecho("HandleCallBack");
            $tokens = $this->auth0->exchange($params['code']);
            lecho("Tokens:", $tokens);
            
            if ($tokens) {
                $userInfo = $this->auth0->authentication()->userInfo($tokens['access_token']);
                lecho("UserInfo:", $userInfo);
                return ['status' => 'success', 'userdata' => $userInfo];
            }
            
        } catch (\Exception $e) {
            lecho("Auth0 error:", $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function syncUser($auth0User) 
    {
        lecho("synUser");
        lecho($auth0User);
        // Synchroniser avec votre table users
        $email = $auth0User['email'];
        $name = $auth0User['name'];
        // ... autres champs
        
        // Retourner le format attendu par votre front
        return [
            'status' => 'success',
            'userdata' => [
                'userid' => '$userId',
                'username' => $name,
                // ... autres champs
            ]
        ];
    }

    public function getSession()
    {
        return $this->auth0->getCredentials();
    }

}
