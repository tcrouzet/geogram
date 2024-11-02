<?php

namespace App\Services;

use App\Controllers\AuthController;
use App\Services\Database;
use App\Utils\Tools;
use App\Services\FilesManager;

class UserService 
{
    private $db;
    private $auth;
    private $fileManager;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->auth = new AuthController();
        $this->fileManager = new FilesManager();
    }

    public function createUser(){
    
        lecho("CreateUser");
    
        $result = $this->auth->login();
        if($result['status'] != 'not_found') return ['status' => "fail", 'message' => "User allready there…"];
    
        list($username, $domain) = explode('@', $result['email']);
        $userinitials = $this->initial($username);
        $usercolor = $this->getDarkColorCode(rand(0,10000));
    
        $hashedPassword = password_hash($result['password'], PASSWORD_DEFAULT);
        $isPasswordValid = password_verify($result['password'], $hashedPassword);
        if(!$isPasswordValid){
            return ['status' => "fail", 'message' => "Bad password"];
        }
    
        $userroute = TESTROUTE; // Connected to testroute by default
    
        $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userpsw, userroute) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("sssssi", $username, $userinitials, $usercolor, $result['email'], $hashedPassword, $userroute);
        
        if ($insertStmt->execute()) {
    
            $userid = $this->db->insert_id;
    
            $this->connect($userid,$userroute,0);
    
            // Retourne les données du nouvel utilisateur
            $token = $this->auth->saveToken($userid);
            if ($token){
                $user =  $this->auth->get_user($userid);
                return [
                    'status' => "success",
                    'userdata' => $user,
                    'route' => $userroute
                ];
            } else {
                $message = "Bad token";
            }
            
        } else {
            $essage = "Can't insert in database";
        }
        return [
            'status' => "error",
            'message' => $message
        ];

    }
    
    public function initial($name){
        $r=substr(fName($name), 0, 2);
        $r=strtr($r, 'áàâãäåçéèêëìíîïñóòôõöøúùûüýÿ', 'aaaaaaceeeeiiiinoooooouuuuyy');
        return ucfirst($r);
    }

    public function getDarkColorCode($number) {
        $code = intval(substr(strval($number), -4));
        $code = $code % 20;
        //https://colorhunt.co/palettes/dark
        $colors =[
            '#712B75',  //violet
            '#533483',  //violet
            '#726A95',  //violet
            '#C147E9',  //violet
            '#790252',  //Violet 
            '#C74B50',
            '#D49B54',
            '#E94560',
            '#950101',
            '#87431D',
            '#2F58CD',  //jaune
            '#0F3460',  //bleu
            '#3282B8',  //bleu
            '#1597BB',  //bleu
            '#46B5D1',  //bleu
            '#346751',  //vert
            '#519872',  //vert
            '#03C4A1',  //vert
            '#6B728E',  //Gris
            '#7F8487'   //Gris
        ];
        //FF4C29 F10086 trop rouge
        return $colors[$code];
    }

    public function connect($userid,$routeid,$status=2){
        lecho("connect",$userid,$routeid);
        $insertQuery = "INSERT INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iii", $routeid, $userid, $status);
        return $insertStmt->execute();
    }

    public function delete_user($userid){
        $query = "DELETE FROM users WHERE userid=?;";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        return $this->db->affected_rows;
    }

    public function updateuser(){
    
        $userid = $_POST['userid'] ?? '';
        $username = $_POST['username'] ?? '';
        $useremail = $_POST['useremail'] ?? '';
        lecho($useremail);
    
        if ($user = $this->auth->get_user($userid)) {
    
            $user['username'] = $username;
            $user['useremail'] = $useremail;
            unset($user['userpsw']);
    
            $stmt = $this->db->prepare("UPDATE users SET username = ? WHERE userid = ?");
            $stmt->bind_param("si", $username, $userid);
            if ($stmt->execute())
               return ['status' => 'success', 'user' => $user];
            else
                return ['status' => 'error', 'message' => 'Update fail'];
    
        }
    
        return ['status' => 'error', 'message' => 'Unknown user'];
    
    }

    public function userphoto(){
        lecho("userphoto");
    
        $userid = $_POST['userid'] ?? '';
    
        if (!isset($_FILES['photofile']) || $_FILES['photofile']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
    
        $target = $this->fileManager->user_photo($userid);
    
        if($target){
            if(Tools::resizeImage($_FILES['photofile']['tmp_name'], $target, 500)){
                $this->set_user_photo($userid,1);
                return ['status' => 'success', 'message' => 'File uploaded successfully'];
            }else{
                $this->set_user_photo($userid,0);
            }
        }
    
        return ['status' => 'error', 'message' => 'Upload fail'];
    }

    public function set_user_photo($userid,$value){
        $stmt = $this->db->prepare("UPDATE users SET userphoto = ? WHERE userid = ?");
        $stmt->bind_param("ii", $value, $userid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }
    

    public function userAction(){
        lecho("userAction");
    
        $userid = $_POST['userid'] ?? '';
        $action = $_POST['action'] ?? '';    
        // lecho($_POST);
        // lecho($action);
    
        if($action == "purgeuser"){
            $message = $this->purgeuser($userid);
        }else{
            return ['status' => 'error', 'message' => "Unknown action: $action"];        
        }
    
        if($message)
            return ['status' => 'success', 'message' => "Action $action done"];
        else
            return ['status' => 'error', 'message' => "Action $action fail"];
    }
    
    public function purgeuser($userid){
        $stmt = $this->db->prepare("DELETE FROM rlogs WHERE loguser=?");
        $stmt->bind_param("i", $userid);
    
        if ($stmt->execute()){
            $this->fileManager->purgeUserData($userid);
            return true;
        }else{
            return false;
        }
    
    }
    
}
