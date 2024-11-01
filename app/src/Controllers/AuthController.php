<?php
namespace App\Controllers;

use App\Services\Database;
use App\Services\FilesManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthController {
    private $db;
    private $FilesManager;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->FilesManager = new FilesManager();
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
    
                if (!$this->testToken($user['userid'])){
                    $user['usertoken'] = $this->saveToken($user['userid']);
                }
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
    
    public function saveToken($userid){
    
        $token = $this->generateToken($userid);
    
        $stmt = $this->db->prepare("UPDATE users SET usertoken = ? WHERE userid = ?");
        $stmt->bind_param("si", $token, $userid);
        if ($stmt->execute())
            return $token;
        else
            return false;
    }
    
    public function testToken($userid){
    
        //lecho("testToken", $userid);
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if(empty($authHeader)) return false;
        list($jwt) = sscanf($authHeader, 'Bearer %s');
        
        $stmt = $this->db->prepare("SELECT usertoken FROM users WHERE userid = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
    
            $decoded = validateToken($jwt);
            if ($jwt && $jwt === $row['usertoken'] && $decoded) {
                if($decoded->exp - time() > 300) {
                    return true;
                }
            }
        }
        return false;
    }

}
