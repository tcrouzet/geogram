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
        lecho("CreateUser", $userInfo);
        
        if (empty($userInfo["email"])) {
            return ['status' => 'error', 'message' => 'Email is required'];
        } else if ($this->isTelegramUserEmail($userInfo["email"])) {
            // C'est un email Telegram valide
            $email = $userInfo["email"];
        } else if (filter_var($userInfo["email"], FILTER_VALIDATE_EMAIL)) {
            // C'est un email standard valide
            $email = $userInfo["email"];
        } else {
            return ['status' => 'error', 'message' => 'Invalid email format: ' . $userInfo["email"]];
        }

        if( empty($userInfo["name"]) ){
            list($username, $domain) = explode('@', $email);
        }else{
            $username = $userInfo["name"];
        }

        if( !empty($userInfo["route"]) && $userInfo["route"]>0) {
            lecho("Route value from userInfo:", $userInfo["route"]);
            $userroute = $userInfo["route"];
        }else{
            $userroute = TESTROUTE; // Connected to testroute by default
        }
        lecho("Create user on ".$userroute);

        $userinitials = Tools::initial($username);
        $usercolor = Tools::getDarkColorCode(rand(0,10000));
    
        $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userroute) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("ssssi", $username, $userinitials, $usercolor, $email, $userroute);
        
        if ($insertStmt->execute()) {
    
            $userid = $this->db->insert_id;
            $this->connect($userid,$userroute,2);
            $this->improuveUser($userid, $userInfo);
            $user = $this->get_user($userid);
            return ['status' => "success", 'user' => $user];
        }
        return [
            'status' => "error",
            'message' => "Can't insert in database"
        ];

    }

    function isTelegramUserEmail($email) {
        // Vérifie si l'email correspond au format xxx@telegram
        return (bool)preg_match('/^[0-9]+@telegram$/', $email);
    }
    
    public function improuveUser($userid, $userInfo){
        lecho("Improve User");
        $telegram = intval($userInfo['telegram'] ?? 0);
        $link = $userInfo['link'] ?? '';
        $picture = $userInfo['picture'] ?? '';
        $routeid = $userInfo['routeid'] ?? '';

        if($telegram != 0){
            lecho("Improve User telegram", $telegram);
            $this->set_user_telegram($userid, $telegram);
        }

        if(!empty($link) && $link !== 'null'){
            lecho("Improve User link",$link);
            $route = (new RouteService())->get_route_by_link($link);
            if($route){
                $status = 1;
                if($route['routepublisherlink'] == $link)
                    $status = 2;
                $this->connect($userid, $route['routeid'], $status);
                $this->set_user_route($userid, $route['routeid']);
            }
        }elseif($routeid){
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
        lecho("get_user");
        lecho($param);
        bactrace("get_user");
    
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
            if (!$this->is_token_valid($param)) {
                lecho("Token expired");
                return false;
            }
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
            lecho($user);
            return $user;
        } else {
            return false;
        }
    }

    public function get_users_by_telegramid($userID) {
        lecho("get_users_by_telegramid");
        $query = "SELECT * FROM users u
            LEFT JOIN routes r ON u.userroute = r.routeid 
            WHERE u.usertelegram = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userID);    
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
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
    
    public function connect($userid, $routeid, $status = 2) {
        lecho("connect", $userid, $routeid);
    
        // Requête SQL avec ON DUPLICATE KEY UPDATE pour mettre à jour le statut uniquement s'il est supérieur
        $insertQuery = "INSERT INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE constatus = IF(VALUES(constatus) > constatus, VALUES(constatus), constatus)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iii", $routeid, $userid, $status);
    
        return $insertStmt->execute();
    }

    public function purgeConnectors($userid){
        $stmt = $this->db->prepare("DELETE FROM connectors WHERE conuserid = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        lecho("Connexions cleaned");
        lecho($this->db->affected_rows);
        return $this->db->affected_rows;
    }

    public function delete_user($userid){
        lecho("Delete User $userid");
        $query = "DELETE FROM users WHERE userid=?;";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        lecho($this->db->affected_rows);

        $this->purgeuser($userid);
        $this->purgeConnectors($userid);

        $dir = $this->fileManager->user_dir2($userid);
        $this->fileManager->supDir($dir);
        return true;
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
        // lecho($telegram);

        if ($this->userid) {
            if (!isset($telegram["id"]) || !is_numeric($telegram["id"])) {
                return ['status' => 'error', 'message' => 'Invalid Telegram ID'];
            }
            $telegram_id = intval($telegram["id"]);

            //Search user with same $telegram_id
            $existingUsers = $this->get_users_by_telegramid($telegram_id);

            if ($existingUsers) {

                foreach($existingUsers as $existingUser){
                    lecho("Récup user");
                    // Vérifier si c'est un mail de type id@telegram
                    if ($this->isTelegramUserEmail($existingUser['useremail'])) {
                        lecho("Existing user found with telegram_id: $telegram_id");
                        //Récupérer cet utilisateur
                        $this->mergeAccounts($existingUser['userid'], $this->userid);
                    }else{
                        return ['status' => 'error',  'message' => 'Telegram account already in use'];
                    }
                }

            }

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

    public function mergeAccounts($fromUserId, $toUserId) {
        lecho("Merging accounts: $fromUserId -> $toUserId");
        
        // Transférer les logs
        $stmt = $this->db->prepare("UPDATE rlogs SET loguser = ? WHERE loguser = ?");
        $stmt->bind_param("ii", $toUserId, $fromUserId);
        $stmt->execute();
        lecho("log tranfered");
        
        // Transférer les connexions
        $stmt = $this->db->prepare("UPDATE connectors SET conuserid = ? WHERE conuserid = ? AND NOT EXISTS (SELECT 1 FROM connectors c2 WHERE c2.conuserid = ? AND c2.conrouteid = connectors.conrouteid)");
        $stmt->bind_param("iii", $toUserId, $fromUserId, $toUserId);
        $stmt->execute();
        lecho("Connexion transfered");
                
        // Transférer les fichiers si nécessaire
        $this->fileManager->transfertUserData($fromUserId, $toUserId);
        
        // Supprimer l'ancien compte
        $this->delete_user($fromUserId);
        
        lecho("Account merge completed");
        return true;
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

    public function is_token_valid($token, $expiration = TOKEN_EXPIRATION) {
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

    public function set_user_email($userId,$email){
        if ($userId && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $this->db->prepare("UPDATE users SET useremail = ? WHERE userid = ?");
            $stmt->bind_param("si", $email, $userId);
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
        }else if($action == "purgeuser_route"){
            $routeid = isset($_POST['routeid']) ? intval($_POST['routeid']) : 0;
            if($routeid>0) {
                $message = $this->purgeuser_route($this->userid,$routeid);
            }
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

    public function purgeuser_route($userid, $routeid){
        $stmt = $this->db->prepare("DELETE FROM rlogs WHERE loguser=? AND logroute=?");
        $stmt->bind_param("ii", $userid, $routeid);
    
        if ($stmt->execute()){
            $this->fileManager->purgeUserRouteData($userid, $routeid);
            return true;
        }else{
            return false;
        }
    
    }

}
