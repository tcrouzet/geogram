<?php

namespace App\Services;

use App\Services\Database;
use App\Utils\Tools;
use App\Services\FilesManager;

class UserService 
{
    private $user = null;
    private $userid = null;
    private $db;
    private $fileManager;
    private $error = false;
    
    public function __construct($user=null) 
    {
        $this->user = $user;
        if($this->user)
            $this->userid = $user["userid"];
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
    }

    public function getError() {
        return $this->error;
    }

    public function findOrCreateUser($userInfo){

        if($userInfo["email"] && $user = $this->get_user($userInfo["email"])){
            return ['status' => "success", 'user' => $user];
        }else{
            return $this->createUser($userInfo);
        }

    }

    public function createUser($userInfo){
        lecho("CreateUser");
        
        if( empty($userInfo["email"]) || !filter_var($userInfo["email"], FILTER_VALIDATE_EMAIL) ){
            return ['status' => 'error', 'message' => 'Invalid email'];
        }else{
            $email = $userInfo["email"];
        }

        if( empty($userInfo["name"]) ){
            list($username, $domain) = explode('@', $email);
        }else{
            $username = $userInfo["name"];
        }

        $userinitials = Tools::initial($username);
        $usercolor = Tools::getDarkColorCode(rand(0,10000));    
        $userroute = TESTROUTE; // Connected to testroute by default
    
        $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userroute) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("ssssi", $username, $userinitials, $usercolor, $email, $userroute);
        
        if ($insertStmt->execute()) {
    
            $userid = $this->db->insert_id;
    
            $this->connect($userid,$userroute,2);

            if( !empty($userInfo["picture"]) ){
                $source = stripslashes($userInfo["picture"]);
                $target = $this->fileManager->user_photo($userid);
                if(Tools::resizeImage($source, $target, 250)){
                    $this->set_user_photo($userid,1);
                }
            }

            $user = $this->get_user($userid);

            return ['status' => "success", 'user' => $user];

            
        }
        return [
            'status' => "error",
            'message' => "Can't insert in database"
        ];

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
            $user['photopath'] = $this->fileManager->user_photo_web($user);
            return $user;
        } else {
            return false;
        }
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
        lecho("Update user");
        $username = $_POST['username'] ?? '';
    
        if ($this->userid) {
    
            $user['username'] = $username;
    
            $stmt = $this->db->prepare("UPDATE users SET username = ? WHERE userid = ?");
            $stmt->bind_param("si", $username, $this->userid);
            if ($stmt->execute()){
                $this->user['username'] = $username; 
                return ['status' => 'success', 'user' => $this->user];
            }else{
                return ['status' => 'error', 'message' => 'Update fail'];
            }
    
        }    
        return ['status' => 'error', 'message' => 'Unknown user'];
    }

    public function updaTelegramUser($telegram){
        lecho("Update telegram user");
    
        if ($this->userid) {

            $telegram_id = intval($telegram["id"]);
            $telegram["photo_url"];
    
    
            $stmt = $this->db->prepare("UPDATE users SET usertelegram = ? WHERE userid = ?");
            $stmt->bind_param("si", $telegram_id, $this->userid);
            if ($stmt->execute()){
                $this->user['usertelegram'] = $telegram_id; 
                return ['status' => 'success', 'user' => $this->user];
            }else{
                return ['status' => 'error', 'message' => 'Update Telegram fail'];
            }
        }
        return ['status' => 'error', 'message' => 'Unknown user'];    
    }

    public function getUserChannels()
    {
        lecho("getUserChannels");
        if($this->user["usertelegram"]){
            $query = "SELECT * FROM telegram WHERE channel_admin = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $this->user["usertelegram"]);
            $stmt->execute();
            $result = $stmt->get_result();

            $channels = [];
            while ($row = $result->fetch_assoc()) {
                $channels[] = [
                    'id' => $row['channel_id'],
                    'title' => $row['channel_title']
                ];
            }
            lecho("channels", $channels);

            if (!empty($channels)) {
                return ['status' => 'success', 'channels' => $channels];
            }
    
        }
        return ['status' => 'error', 'message' => 'No telegram channels'];
    }


    public function userphoto(){
        lecho("userphoto");
        
        if (!isset($_FILES['photofile']) || $_FILES['photofile']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
    
        $target = $this->fileManager->user_photo($this->userid);
    
        if($target){
            if(Tools::resizeImage($_FILES['photofile']['tmp_name'], $target, 250)){
                $this->set_user_photo($this->userid,1);
                return ['status' => 'success', 'message' => 'File uploaded successfully'];
            }else{
                $this->set_user_photo($this->userid,0);
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
    
        $action = $_POST['action'] ?? '';
    
        if($action == "purgeuser"){
            $message = $this->purgeuser($this->userid);
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
