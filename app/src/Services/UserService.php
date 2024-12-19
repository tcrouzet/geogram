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
            $this->improuveUser($user['userid'], $userInfo);
            $user = $this->get_user($user['userid']);
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

        $userroute = TESTROUTE; // Connected to testroute by default
        $userinitials = Tools::initial($username);
        $usercolor = Tools::getDarkColorCode(rand(0,10000));
    
        $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userroute) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("ssssi", $username, $userinitials, $usercolor, $email, $userroute);
        
        if ($insertStmt->execute()) {
    
            $userid = $this->db->insert_id;
            $this->connect($userid,TESTROUTE,2);
            $this->improuveUser($userid, $userInfo);
            $user = $this->get_user($userid);
            return ['status' => "success", 'user' => $user];
        }
        return [
            'status' => "error",
            'message' => "Can't insert in database"
        ];

    }

    public function improuveUser($userid, $userInfo){
        lecho("Improve User");
        $telegram = $userInfo['telegram'] ?? '';
        $link = $userInfo['link'] ?? '';
        $picture = $userInfo['picture'] ?? '';
        $routeid = $userInfo['routeid'] ?? '';

        if($telegram){
            $this->set_user_telegram($userid, $telegram);
        }

        if($link){
            $route = (new RouteService())->get_route_by_link($link);
            if($route['routeid'] != TESTROUTE){
                $status = 1;
                if($route['routepublisherlink'] == $link)
                    $status = 2;
                $this->connect($userid, $route['routeid'], $status);
                $this->set_user_route($userid, $route['routeid']);
            }
        }

        if($routeid){
            $this->connect($userid, $routeid, 2);
            $this->set_user_route($userid, $routeid);
        }

        if($picture){
            $target = $this->fileManager->user_photo($userid);
            if(!file_exists($target)){
                $source = stripslashes($picture);
                if(Tools::resizeImage($source, $target, 250)){
                    $this->set_user_photo($userid,1);
                }
            }
        }

        return true;
    }

    // $param can be userid, useremail or token
    public function get_user($param) {
        lecho("GetUser");
    
        $isEmail = strpos($param, '@') !== false;
        $isToken = preg_match('/^\d+_[a-f0-9]{128}$/', $param) === 1;

        $query = "SELECT * FROM users u
        LEFT JOIN routes r ON u.userroute = r.routeid
        LEFT JOIN connectors c ON u.userid = c.conuserid AND r.routeid = c.conrouteid
        WHERE ";

        if ($isEmail) {
            $query .= "u.useremail = ?";
        } else if ($isToken) {
            lecho("isToken");
            $query .= "u.usertoken = ?";
        } else {
            $query .= "u.userid = ?";
        }
    
        $stmt = $this->db->prepare($query);
    
        if ($isEmail) {
            $stmt->bind_param("s", $param);
        } else if ($isToken) {
            $stmt->bind_param("s", $param);
        } else {
            $stmt->bind_param("i", $param);
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            lecho("UserFound");
            $user = $result->fetch_assoc();
            $user['photopath'] = $this->fileManager->user_photo_web($user);
            $user['fusername'] = Tools::normalizeName($user['username']);
            return $user;
        } else {
            return false;
        }
    }

    public function get_user_by_telegramid($userID) {
        lecho("get_user_by_telegramid");
        $query = "SELECT * FROM users u
            LEFT JOIN routes r ON u.userroute = r.routeid 
            LEFT JOIN connectors c ON u.userid = c.conuserid 
            WHERE u.usertelegram = ?";
    
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userID);    
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false;
        }
    }

    public function get_user_by_route($routeid) {
        lecho("get_user_by_route", $routeid);
        $query = "SELECT * FROM users WHERE userroute = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);    
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false;
        }
    }
    
    public function connect($userid,$routeid,$status=2){
        lecho("connect",$userid,$routeid);
        $insertQuery = "INSERT IGNORE INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)";
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
            //$telegram["photo_url"];
            if ($this->set_user_telegram($this->userid, $telegram_id) ){
                $this->user['usertelegram'] = $telegram_id; 
                return ['status' => 'success', 'user' => $this->user];
            }else{
                return ['status' => 'error', 'message' => 'Update Telegram fail'];
            }
        }
        return ['status' => 'error', 'message' => 'Unknown user'];    
    }

    public function getUserChannels(){
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

    public function set_user_token($userid){
        $timestamp = time();
        $token = strval($userid) . "_" . strval($timestamp) . "_" . bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("UPDATE users SET usertoken = ? WHERE userid = ?");
        $stmt->bind_param("si", $token, $userid);
        if ($stmt->execute())
            return $token;
        else
            return false;
    }

    public function is_token_valid($token, $expiration = 3600) {
        $parts = explode('_', $token);
        if (count($parts) !== 3) return false;
        
        $tokenTimestamp = intval($parts[1]);
        $currentTime = time();
        
        return ($currentTime - $tokenTimestamp) < $expiration;
    }

    public function set_user_telegram($userId,$telegramId){
        if ($userId && $telegramId) {
            $telegram_id = intval($telegramId);    
            $stmt = $this->db->prepare("UPDATE users SET usertelegram = ? WHERE userid = ?");
            $stmt->bind_param("ii", $telegram_id, $userId);
            if ($stmt->execute()){
                return true;
            }
        }
        return false;
    }

    public function set_user_route($userId,$routeId){
        if ($userId && $routeId) {
            $stmt = $this->db->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
            $stmt->bind_param("ii", $routeId, $userId);
            if ($stmt->execute()){
                return true;
            }
        }
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
