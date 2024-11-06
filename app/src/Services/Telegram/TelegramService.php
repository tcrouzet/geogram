<?php

namespace App\Services\Telegram;

use App\Services\Database;
use App\Services\FilesManager;
use App\Services\Telegram\TelegramCallback;
use App\Services\Telegram\TelegramTools;


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
    private $userid;

    public function __construct($update) 
    {
        $this->update = $update;
        $this->telegram = new \Telegram(TELEGRAM_BOT_TOKEN, false);
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleUpdate() {
        if ($this->isNewChannel()) return;
        if (!$this->init()) return;
        if ($this->isCallback()) return;

        //TEST
        if( isset($this->message["group_chat_created"])){
            $this->error = "NewGroupCreated";
            lecho($this->error);
            return;
        }
    }

    private function init(){
        if( isset($this->update["message"])){
            $this->message = $this->update["message"];
        }elseif( isset($this->update["edited_message"])) {
            $this->message = $this->update["edited_message"];
        }
        
        $this->title = TelegramTools::get_chatitle($this->message);
        if ( empty($this->title) ){
            $this->error = "Empty chat_title";
            lecho($this->error);
            return false;
        }

        $this->userid = TelegramTools::get_userid($this->message);

        //Migrate
        if( isset($this->message["migrate_to_chat_id"]) ){
            $this->migrate($this->message);
        }
        
        //chatid
        $this->chatid = $this->message["chat"]["id"];
        $this->channel = $this->getChannel( round($this->chatid) );
        if(!$this->channel){
            $this->error = "Unknown channel $this->chatid $this->title";
            lecho($this->error);
            return false;
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

    private function isCallback(){
        if( isset($this->update["callback_query"])){
            $callback = new TelegramCallback($this->telegram, $this->update["callback_query"]);
            return $callback->handleCallback();
        }
        return false;
    }

    private function isNewChannel(){
        if (isset($this->update["my_chat_member"])) {
            $chat = $this->update["my_chat_member"]["chat"];
            
            if ($chat['type'] === 'channel') {

                $user = $this->update["my_chat_member"]["from"];
                $chatId = round($chat['id']);
                $userId = round($user['id']);

                return $this->newChannel($chatId, $userId, $chat['title'], $this->update["my_chat_member"]["new_chat_member"]["status"]);

                //Implement
                //ChatMemberUpdate($update["my_chat_member"]);

                return true;
            }
        }
        return false;
    }

    private function newChannel($chatId, $userId, $title, $status){
        $insertQuery = "INSERT INTO telegram (channel_id, channel_user, channel_title, channel_status) VALUES (?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iiss", $chatId, $userId, $title, $status);
        return $insertStmt->execute();
    }

    private function getChannel($chatId){
        $query = "SELECT * FROM telegram WHERE channel_id = ?";
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

        $channel = $this->getChannel(round($old_chatid));
        if(!$channel){
            //Unknown old_chatid
            return $this->newChannel( round($new_chatid), round($this->userid), $this->title, "migrate" );
        }else{
            //Reindex
            return $this->updateChannel( round($old_chatid), round($new_chatid ) );
        }
    }
    
    public function getError() {
        return $this->error;
    }
    
}