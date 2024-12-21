<?php

// app/src/Services/AuthService.php
namespace App\Services;

use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Services\UserService;

class AuthService 
{
    private $provider = null;
    private $userService;
    private $error = false;
    private $user = null;
    private $cookies_time = 60 * 60 * 24 * 30 * 6;
    
    public function __construct($user=null) {
        $this->user = $user;
        $this->userService = new UserService($user);
    }

    private function set_provider($providerName){
        switch ($providerName) {
            case 'google-oauth2':
                $config = require ROOT_PATH . '/app/config/google.php';
                $this->provider = new Google($config);
                break;
            default:
                throw new \Exception('No valid auth provider found');
        }
        lecho("AuthService providerName:", $providerName);
    }

    public function loginWithEmail() {
        try {
            $email = $_POST['email'] ?? null;
            lecho("AuthService loginWithEmail:", $email);

            if(empty($email)){
                return ['status' => 'error', 'message' => 'No email'];
            }

            // Créer un state avec les paramètres
            $state = [
                'link' => urldecode($_POST['link'] ?? ''),
                'telegram' => $_POST['telegram'] ?? '',
            ];

            // $user = $this->userService->get_user($email);
            $userInfo["email"]=$email;
            $userInfo['link'] = $state['link'];
            $userInfo['telegram'] = $state['telegram'];
            $r = $this->userService->findOrCreateUser($userInfo);
            if( $r['status']=="success" ){
                $user = $r['user'];
                $token = $this->userService->set_user_token($user['userid']);
                $state =base64_encode(json_encode($state));
                if($this->sendEmail($email, $token, $state)){
                    return ['status' => 'redirect', 'url' => '/login?waiting=1'];
                }else{
                    return ['status' => 'error', 'message' => 'No log in mail send'];
                }

            }

            return ['status' => 'error', 'message' => 'Invalid credentials'];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function loginWithToken() {
        try {
            $token = $_POST['token'] ?? null;
            lecho("AuthService loginWithToken:", $token);

            if(empty($token)){
                return ['status' => 'error', 'message' => 'No token'];
            }

            if(!$this->userService->is_token_valid($token)){
                return ['status' => 'error', 'message' => 'Expired token'];
            }

            $user = $this->userService->get_user($token);
            if($user){
                // Stocker l'ID utilisateur dans un cookie ou session
                setcookie('user_session', json_encode([
                    'app_userid' => $user['userid']
                ]), time() + ($this->cookies_time), '/', '', true, true);
                
                return ['status' => 'success', 'user' => $user];
            } else {
                return ['status' => 'error', 'message' => "Unknown token"];
            }
  
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function loginWithSocial() {
        lecho("AuthService loginSocial");
        try {

            $providerName = $_POST['provider'] ?? '';
            $this->set_provider($providerName);

            // Créer un state avec les paramètres
            $state = [
                'link' => urldecode($_POST['link'] ?? ''),
                'telegram' => $_POST['telegram'] ?? '',
                'providerName' => $providerName
            ];
            lecho($state);

            // Générer l'URL d'autorisation
            $authorizationUrl = $this->provider->getAuthorizationUrl([
                'scope' => ['openid', 'profile', 'email'],
                'state' => base64_encode(json_encode($state))
            ]);
    
            // Sauvegarder l'état pour vérification ultérieure
            setcookie('oauth2state', $this->provider->getState(), time() + ($this->cookies_time), '/', '', true, true);
    
            return ['status' => 'redirect', 'url' => $authorizationUrl];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function handleCallback() {
        try {
            lecho("HandleCallBack");

            if (!isset($_GET['state'])) {
                throw new \Exception('State not found');
            }

            $params = json_decode(base64_decode($_GET['state']), true);
            lecho($params);
            $providerName = $params['providerName'] ?? '';
            $this->set_provider($providerName);
        
            // Obtenir l'access token
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);
    
            // Obtenir les informations utilisateur
            $userInfo = $this->provider->getResourceOwner($token)->toArray();
            lecho($userInfo);
    
            // Ajouter les valeurs supplémentaires du state si disponibles
            if (isset($_GET['state'])) {
                $params = json_decode(base64_decode($_GET['state']), true);
                $userInfo['link'] = $params['link'] ?? null;
                $userInfo['telegram'] = $params['telegram'] ?? null;
            }
    
            // Trouver ou créer l'utilisateur
            $user = $this->userService->findOrCreateUser($userInfo);
            lecho($user);
    
            if ($user['status'] == "success") {
                // Stocker le userid dans un cookie ou session
                setcookie('user_session', json_encode([
                    ...$userInfo,
                    'app_userid' => $user['user']['userid']
                ]), time() + ($this->cookies_time), '/', '', true, true);
                lecho("userid", $user['user']['userid']);
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

    public function handleSession() {
        lecho("AuthService handleSession");
    
        // Vérifier si le cookie de session utilisateur existe
        if (isset($_COOKIE['user_session'])) {
            $sessionData = json_decode($_COOKIE['user_session'], true);
    
            // Vérifier si le cookie contient un app_userid
            if (isset($sessionData['app_userid'])) {
                // Récupérer les données utilisateur via UserService
                $user = $this->userService->get_user($sessionData['app_userid']);
                if ($user) {
                    return ['status' => 'success', 'user' => $user];
                } else {
                    return ['status' => 'error', 'message' => "Unknown user"];
                }
            }
        }
    
        return ['status' => 'error', 'message' => 'No active session'];
    }
    

    public function handleLogout() {
        lecho("logOut");
        // Supprimer le cookie de session
        if (isset($_COOKIE['oauth2state'])) {
            unset($_COOKIE['oauth2state']);
            setcookie('oauth2state', '', time() - 3600, '/', '', true, true);
        }

        // Supprimer d'autres cookies de session si nécessaire
        if (isset($_COOKIE['user_session'])) {
            unset($_COOKIE['user_session']);
            setcookie('user_session', '', time() - 3600, '/', '', true, true);
        }
        return true;
    }

    public function handleTelegram(){
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

    public function sendEmail($userEmail,$token,$state=""){

        $mail = new PHPMailer(true);
        try {
            $config = require ROOT_PATH . '/app/config/gmail.php';

            // Paramètres du serveur
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $config['Username'];
            $mail->Password = $config['Password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinataire
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['Useremail'], $config['UserRealName']);
            $mail->addAddress($userEmail);

            // Contenu de l'email
            $mail->isHTML(true);
            $mail->Subject = GEONAME.' logging';
            $mail->Body    = 'Clic on this link : <a href="'.BASE_URL.'/?login=token&token=' . $token . '&state=' . $state . '">log in</a>';

            $mail->send();
            lecho('log in mail send to',$userEmail);
            return true;
        } catch (Exception $e) {
            lecho("Send mail error {$mail->ErrorInfo}");
            return false;
        }

    }

}

