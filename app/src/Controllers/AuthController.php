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
    public function loginEmail()
    {
        try {
            lecho("AuthController loginEmail");
            return $this->authService->loginWithEmail();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // Login social
    public function loginToken()
    {
        try {
            lecho("AuthController loginToken");
            return $this->authService->loginWithToken();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // Login QR
    public function loginQR()
    {
        try {
            lecho("AuthController loginGR");
            return $this->authService->loginWithQR();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // Login social
    public function loginSocial()
    {
        try {
            lecho("AuthController loginSocial");
            return $this->authService->loginWithSocial();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function callback() 
    {
        try {
            lecho("AuthController Callback");
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
