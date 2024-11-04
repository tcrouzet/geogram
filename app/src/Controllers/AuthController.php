<?php
namespace App\Controllers;

use App\Services\AuthService;

class AuthController 
{
    private $authService;
    private $error = false;

    public function __construct() 
    {
        try {
            $this->authService = new AuthService();
        } catch (\Exception $e) {
            $this->error = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    // Login social
    public function loginSocial()
    {
        lecho("Controler loginSocial");
        $provider = $_POST['provider'] ?? '';
        if (!$provider) {
            return ['status' => 'error', 'message' => 'Provider required'];
        }
        return $this->authService->loginWithSocial($provider);
    }

    public function callback() 
    {
        try {
            lecho("Callback");
            //lecho("GET params:", $_GET);
            //return $this->authService->handleCallback($_GET);
            return $this->authService->handleCallback();
            
        } catch (\Exception $e) {
            //lecho("Callback error:", $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getSession() 
    {
        try {
            return $this->authService->handleSession();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function logout() 
    {
        try {
            $this->authService->handleLogout();
            return ['status' => 'success'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }    

    public function getError() 
    {
        return $this->error;
    }

}
