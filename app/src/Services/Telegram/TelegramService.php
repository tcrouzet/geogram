<?php

namespace App\Services\Telegram;

use App\Services\Database;
use App\Services\MapService;
use App\Services\FilesManager;
use App\Services\Telegram\TelegramCallback;
use App\Services\Telegram\TelegramTools;
use App\Utils\Tools;


class TelegramService 
{
    private $telegram;
    private $update = null;
    private $db;
    private $error = false;
    private $callback;
    private $message;
    private $message_id;
    private $timestamp;
    private $chatid;
    private $title;
    private $channel;
    private $userid;    //Telegram userid
    private $username;  //Telegram username
    private $user;      //Geogram user

    public function __construct($update) 
    {
        $this->update = $update;
        $this->telegram = new \Telegram(TELEGRAM_BOT_TOKEN, false);
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleUpdate() {

        if (!$this->init()) return;
        if ($this->privateChat()) return;
        if ($this->isCallback()) return;

        //TEST
        // if( isset($this->message["group_chat_created"])){
        //     $this->error = "NewGroupCreated";
        //     lecho($this->error);
        //     return;
        // }

        if ($this->command()) return;
        if ($this->location()) return;
        if ($this->text()) return;
        if ($this->photo()) return;

    }

    private function init(){
        if( isset($this->update["message"])){
            $this->message = $this->update["message"];
        }elseif( isset($this->update["edited_message"])) {
            $this->message = $this->update["edited_message"];
        }

        $this->chatid = TelegramTools::get_chatid($this->message);
        $this->userid = TelegramTools::get_userid($this->message);

        if($this->isPrivateChat())
            return true;
        
        $this->title = TelegramTools::get_chatitle($this->message);
        if ( empty($this->title) ){
            $this->error = "Empty chat_title";
            return false;
        }

        if ($this->isNewChannel()) return true;

        //Channel
        $this->channel = $this->getChannel( round($this->chatid) );
        if(!$this->channel){
            $this->error = "Unknown channel $this->chatid $this->title";
            return false;
        }
        if(!isset($this->channel["routeid"])){
            $this->error = "Unknown route $this->chatid $this->title Need a connexion";
            return false;
        }

        //User
        if( !$this->user = $this->getUser( round($this->userid) )){
            $this->error = "The chat user $this->userid not in Geogram";
            // $link = BASE_URL . "/login/?link=" . $this->channel["routepublisherlink"]. "&telegram=" . $this->chatid;
            // TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, "If you want to publish on Geogram you must follow this link: $link",10);
            lecho($this->error);
            return false;
        }

        $this->username = TelegramTools::get_username($this->message); 
        if(empty($this->username)){
            $this->error = "no username";
            return false;
        }

        // Migrate
        if( isset($this->message["migrate_to_chat_id"]) ){
            $this->migrate();
        }

        $this->message_id = $this->message["message_id"];
        
        //Timestamp
        if( isset($this->message["edit_date"]) ){
            $this->timestamp = $this->message["edit_date"];
        }else{
            $this->timestamp = $this->message["date"];
        }

        return true;
    }

    // Manage messages starting with /
    private function command(){
        lecho("commandStart");
        if ( isset($this->message["text"]) && strpos( stripslashes($this->message["text"]), '/') === 0 ){
            TelegramTools::deleteMessage($this->telegram, $this->chatid, $this->message_id);
            if($this->geogram()) return true;
            if($this->reconnect()) return true;
            // More commands to come
            return true;
        }
        return false;
    }

    private function geogram(){
        if ( isset($this->message["text"]) && $this->message["text"] === "/geogram" ){
            lecho("geogram");
            $privateChatLink = "https://t.me/".TELEGRAM_BOT."?start=".$this->chatid;
            $message = "To connect with ".GEONAME.", please click $privateChatLink to start a conversation with ".TELEGRAM_BOT.". Once you have opened the private chat, please clic the START button down screen and wait a link to proceed.";
            TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, $message, 15);
            return true;
        }
        return false;
    }

    private function reconnect(){
        if ( isset($this->message["text"]) && $this->message["text"] === "/reconnect" ){
            lecho("reconnect");
            $this->newChannel($this->chatid, $this->userid, $this->title, "reconnect");
            return true;
        }
        return false;
    }

    private function isPrivateChat(){
        return isset($this->message["chat"]["type"]) && $this->message["chat"]["type"] === "private";
    }

    private function privateChat() {
        if ( $this->isPrivateChat()){
            lecho("privateChat");
            if (isset($this->message['text']) && strpos($this->message['text'], '/start') === 0) {
                lecho("/start");
                // Extrait le paramètre après '/start '
                $startParam = trim(substr($this->message['text'], 6));
                $channel = $this->getChannel( intval($startParam) );
                if(!$channel){
                    return TelegramTools::SendMessage($this->telegram, $this->chatid, "Unknown channel");
                }        
                $link = BASE_URL."/login?telegram=".$this->userid."&link=".urlencode($channel['routepublisherlink']);
                $message = "To connect to ". $channel['routename'] ." on ". GEONAME . " click $link";
                return TelegramTools::SendMessage($this->telegram, $this->chatid, $message);
            }
        }
        return false;
    }
    
    private function text(){
        if( isset($this->message["text"])){
            $query = "UPDATE rlogs SET logcomment = CONCAT(COALESCE(logcomment, ''), '\n', ?) WHERE logroute = ? AND loguser = ? AND logupdate = (SELECT MAX(logupdate) FROM rlogs WHERE logroute = ? AND loguser = ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("siiii", $this->message["text"], $this->user["routeid"], $this->user["userid"], $this->user["routeid"], $this->user["userid"]);
            if (!$stmt->execute()){
                $this->error = "Error sql 2 - no log for the user";
                lecho($this->error);
            }
            TelegramTools::todelete($this->telegram, $this->chatid, $this->message_id, $this->channel["routemode"],1);
            TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, "$this->username, your message is on the map!");
            lecho("text");
            return true;
        }
        return false;
    }

    private function photo(){

        if( isset($this->message["photo"])){
            lecho("photo");

            if (!$lastLog = $this->getLastLog($this->user["routeid"], $this->user["userid"])) {
                TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, "$this->username, your need first to geolocalise!");
                lecho("No last log");
                return false;    
            }    

            $result = false;
    
            $max_index = max(array_keys($this->message["photo"]));
            $file_id = $this->message["photo"][$max_index]["file_id"];
            $file = $this->telegram->getFile($file_id);
        
            if($file["ok"]){
                lecho("photo OK");

                $fileManager = new FilesManager();

                $photoI = 1;
                $target = $fileManager->user_route_photo($this->user["userid"], $this->user["routeid"], strtotime($lastLog['logtime']), $photoI);
                while(file_exists($target)){
                    $photoI++;
                    $target = $fileManager->user_route_photo($this->user["userid"], $this->user["routeid"], strtotime($lastLog['logtime']), $photoI);
                }
                lecho($target);

                $tempFile = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
                $this->telegram->downloadFile($file['result']['file_path'], $tempFile);
        
                if(file_exists($tempFile)){
                    lecho("Photo OK 2");
                    if(Tools::resizeImage($tempFile, $target, 1200)){

                        $query = "UPDATE rlogs SET logphoto = ? WHERE logid = ?";
                        $stmt = $this->db->prepare($query);
                        $stmt->bind_param("ii", $photoI, $lastLog['logid']);                
                        if (!$stmt->execute()) {
                            $this->error = "Error sql 2 - no log for the user";
                            lecho($this->error);
                            return false;
                        }
                        TelegramTools::todelete($this->telegram, $this->chatid, $this->message_id, $this->channel["routemode"],2);
                        TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, "$this->username, your photo is on the map!");
                        lecho("photo");
                        return true;
                    }
                }
            }
            TelegramTools::todelete($this->telegram, $this->chatid, $this->message_id, $this->channel["routemode"],1);
            if($result)
                TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, "$this->username, your message is on the map!");
            lecho("Photo");
            return true;
        }
        return false;
    }

    private function location(){
        if(isset($this->message["location"])){

            lecho("Location find ",$this->userid);

            //No more than one location/10minutes except for real time
            if ( $this->isAdmin() || $this->channel['routerealtime']==0 ){
                // $query = "SELECT EXISTS(SELECT 1 FROM rlogs WHERE loguser = ? AND logtime > (UNIX_TIMESTAMP() - 600))";
                $query = "SELECT EXISTS(
                    SELECT 1 
                    FROM rlogs 
                    WHERE loguser = ? 
                    AND logtime > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                )";
                $stmt_lastuser = $this->db->prepare($query);
                $stmt_lastuser->bind_param("i", $this->user["userid"]);
                $stmt_lastuser->execute();
                $result = $stmt_lastuser->get_result()->fetch_row();
                if ($result && $result[0]) {
                    //Posted location during last 10 minutes
                    if($this->channel['routerealtime']==0){
                        $this->telegram->deleteMessage(array('chat_id' => $this->chatid,'message_id' => $this->message_id));
                        TelegramTools::ShortLivedMessage($this->telegram, $this->chatid,"$this->username, you are too fast. Next localisation will be possible in 10 minutes.");
                    }
                    lecho("Too fast");
                    return true;
                }
            }

            $latitude = $this->message["location"]["latitude"];
            $longitude = $this->message["location"]["longitude"];

            $map = new MapService($this->user);
            //lecho($this->user);
            $map->newlog($this->user["userid"], $this->user["routeid"], $latitude, $longitude);

            if(isset($this->message["location"]["live_period"]) && $this->channel['routerealtime']==1){
                //Real time
            }else{
                //Normal
                TelegramTools::todelete($this->telegram, $this->chatid, $this->message_id, $this->channel["routemode"],2);
                TelegramTools::ShortLivedMessage($this->telegram, $this->chatid, "$this->username, your are on the map!");
            }
            lecho("End location");
            return true;
        }
        return false;
    }

    private function isCallback(){
        if( isset($this->update["callback_query"])){
            $callback = new TelegramCallback($this->telegram, $this->update["callback_query"]);
            return $callback->handleCallback();
        }
        return false;
    }

    private function isNewChannel(){
        if (isset($this->update["my_chat_member"])) {
            lecho("IsNewChannel");
            $chat = $this->update["my_chat_member"]["chat"];
            
            if ($chat['type'] === 'channel' || $chat['type'] === 'group') {

                $user = $this->update["my_chat_member"]["from"];
                $chatId = round($chat['id']);
                $userId = round($user['id']);

                if($this->update["my_chat_member"]["new_chat_member"]["status"] == "left"){
                    //Destruction du groupe
                    $this->deleteChannelConnexion($chatId);
                }else{
                    $this->newChannel($chatId, $userId, $chat['title'], $this->update["my_chat_member"]["new_chat_member"]["status"]);
                }

                //Implement
                //ChatMemberUpdate($update["my_chat_member"]);

                return true;
            }
        }
        return false;
    }

    private function deleteChannelConnexion($chatId){
        lecho("deleteChannelConnexion");
        $query = "DELETE FROM telegram WHERE channel_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $chatId);
        return $stmt->execute();
    }    

    private function newChannel($chatId, $userId, $title, $status) {
        lecho("NewChannel");
        $insertQuery = "INSERT INTO telegram (channel_id, channel_admin, channel_title, channel_status) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        channel_title = VALUES(channel_title),
                        channel_status = VALUES(channel_status)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iiss", $chatId, $userId, $title, $status);
        if ($insertStmt->execute()){
            $user = $this->getUser($userId);
            if($user){
                TelegramTools::SendMessage($this->telegram, $chatId, $user['username']." connected to ".BASE_URL.".");
            }else
                TelegramTools::SendMessage($this->telegram, $chatId, "Connected to ".BASE_URL.". On ".GEONAME." you must be connected to Telegram to associate a route to this group.");
        } else {
            TelegramTools::SendMessage($this->telegram, $chatId, "Connection to ".BASE_URL." impossible.");
        }
    }
    

    private function getChannel($chatId){
        $query = "SELECT * FROM telegram t LEFT JOIN routes r ON t.channel_id = r.routetelegram WHERE t.channel_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $chatId);    
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    private function updateChannel($oldId, $newId){
        $stmt = $this->db->prepare("UPDATE telegram SET channel_id = ? WHERE channel_id = ?");
        $stmt->bind_param("ii", $newId, $oldId);
        if ($stmt->execute()){
            return true;
        }else{
            return false;
        }
    }

    private function migrate(){
        lecho("NewChatID");
        $old_chatid = $this->update["migrate_from_chat_id"];
        $new_chatid = $this->update['chat']['id'];

        $channel = $this->getChannel( round($old_chatid) );
        if(!$channel){
            //Unknown old_chatid
            return $this->newChannel( round($new_chatid), round($this->userid), $this->title, "migrate" );
        }else{
            //Reindex
            return $this->updateChannel( round($old_chatid), round($new_chatid ) );
        }
    }

    private function getUser($userid){
        $query = "SELECT * FROM users u LEFT JOIN routes r ON u.userroute = r.routeid WHERE u.usertelegram = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    private function getLastLog($chatid, $userid) {
        $query = "SELECT * FROM rlogs 
                WHERE logroute = ? AND loguser = ? 
                ORDER BY logupdate DESC 
                LIMIT 1";
                
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $chatid, $userid);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        return false;
    }

    private function isAdmin(){
        if($this->channel["channel_admin"] == $this->userid){
            return true;
        }
        return false;
    }

    public function getError() {
        return $this->error;
    }
    
}