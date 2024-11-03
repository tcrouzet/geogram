<?php
namespace App\Controllers;

use App\Services\Database;
use App\Services\FilesManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthControllerMaison {
    private $db;
    private $FilesManager;
    private $error = false;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->FilesManager = new FilesManager();
    }

    public function getError() {
        return $this->error;
    }
    
    public function login(){
        lecho("get_login");
    
        $email = $_POST['email'] ?? '';
        if(!empty($email)){
            $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
        }else{
            $isEmailValid = false;
        }
        if(!$isEmailValid){
            return ['status' => 'error', 'message' => 'Invalid email'];
        }
    
        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            return ['status' => 'error', 'message' => 'Invalid password'];
        }
    
        if ($user = $this->get_user($email)) {
            if (password_verify($password, $user['userpsw'])) {
    
                $user['usertoken'] = $this->saveToken($user['userid']);
                unset($user['userpsw']);
                return ['status' => 'success', 'userdata' => $user];
            } else {
                return ['status' => 'error', 'message' => 'Wrong password'];
            }
        }
        return ['status' => 'not_found', 'message' => $email.' is not a user, do you want to sign in?', 'email' => $email, 'password' => $password];
    }

    public function get_user($param) {
    
        $isEmail = strpos($param, '@') !== false;
    
        if ($isEmail) {
            $query = "SELECT * FROM users u LEFT JOIN routes r ON u.userroute = r.routeid WHERE u.useremail = ?";
        } else {
            $query = "SELECT * FROM users u LEFT JOIN routes r ON u.userroute = r.routeid WHERE u.userid = ?";
        }
    
        $stmt = $this->db->prepare($query);
    
        if ($isEmail) {
            $stmt->bind_param("s", $param);
        } else {
            $stmt->bind_param("i", $param);
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user['photopath'] = $this->FilesManager->user_photo_web($user);
            return $user;
        } else {
            return false;
        }
    }

    public function generateToken($userid) {
        $payload = [
            'iss' => GEO_DOMAIN,           // Émetteur
            'aud' => GEO_DOMAIN,           // Audience
            'iat' => time(),               // Temps d'émission
            'exp' => time() + 86400*90,    // Expiration (90 jours)
            'sub' => $userid               // Sujet (ID utilisateur)
        ];
    
        $secretKey = JWT_SECRET;
        return JWT::encode($payload, $secretKey, 'HS256');
    }
    
    public function validateToken($jwt) {
        try {
            //lecho("validate",$jwt);
            $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            lecho($e);
            return false;
        }
    }

    public function saveToken($userid) {
        $token = $this->generateToken($userid);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400*90);
        $deviceInfo = $this->getDeviceInfo();

        $stmt = $this->db->prepare(
            "INSERT INTO user_tokens (userid, token, device_info, expires_at) 
                VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isss", $userid, $token, $deviceInfo, $expiresAt);
        
        if ($stmt->execute()) {
            // Nettoyer les tokens expirés
            $this->cleanExpiredTokens($userid);
            return $token;
        }
        
        return false;
    }

    private function getDeviceInfo(): string {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return json_encode([
            'user_agent' => $userAgent,
            'ip' => $ip,
            'timestamp' => time()
        ]);
    }

    public function testToken($userid) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if(empty($authHeader)) return false;
        list($jwt) = sscanf($authHeader, 'Bearer %s');
        
        // Vérifier le token dans la table user_tokens
        $stmt = $this->db->prepare(
            "SELECT * FROM user_tokens 
                WHERE userid = ? AND token = ? AND expires_at > NOW()"
        );
        $stmt->bind_param("is", $userid, $jwt);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $tokenData = $result->fetch_assoc();
            $decoded = $this->validateToken($jwt);
            
            if ($decoded) {
                // Mettre à jour last_used
                $this->updateTokenUsage($tokenData['token_id']);
                return true;
            }
        }
        
        return false;
    }

    private function updateTokenUsage($tokenId) {
        $stmt = $this->db->prepare(
            "UPDATE user_tokens SET last_used = CURRENT_TIMESTAMP 
                WHERE token_id = ?"
        );
        $stmt->bind_param("i", $tokenId);
        $stmt->execute();
    }

    private function cleanExpiredTokens($userid) {
        // Supprimer les tokens expirés
        $stmt = $this->db->prepare(
            "DELETE FROM user_tokens 
                WHERE userid = ? AND expires_at < NOW()"
        );
        $stmt->bind_param("i", $userid);
        $stmt->execute();
    }

    public function logout($userid, $currentToken = null) {
        if ($currentToken) {
            // Révoquer uniquement le token courant
            $stmt = $this->db->prepare(
                "DELETE FROM user_tokens 
                    WHERE userid = ? AND token = ?"
            );
            $stmt->bind_param("is", $userid, $currentToken);
        } else {
            // Révoquer tous les tokens de l'utilisateur
            $stmt = $this->db->prepare(
                "DELETE FROM user_tokens WHERE userid = ?"
            );
            $stmt->bind_param("i", $userid);
        }
        return $stmt->execute();
    }

    public function listUserSessions($userid) {
        $stmt = $this->db->prepare(
            "SELECT token_id, device_info, last_used, created_at 
                FROM user_tokens 
                WHERE userid = ? AND expires_at > NOW() 
                ORDER BY last_used DESC"
        );
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}