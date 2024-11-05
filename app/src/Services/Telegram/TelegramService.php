<?php

namespace App\Services\Telegram;

use App\Services\Database;
use App\Services\FilesManager;

class TelegramService 
{
    private $telegram;
    private $user = null;
    private $db;
    private $error = false;

    public function __construct($user = null) 
    {
        $this->telegram = new \Telegram(TELEGRAM_BOT_TOKEN, false);
        $this->db = Database::getInstance()->getConnection();
        $this->user = $user;
    }

    public function handleUpdate($update) 
    {
        $this->isNewChannel();
        // Gérer les différents types de messages
        // if (isset($update["callback_query"])) {
        //     $this->handleCallback($update["callback_query"]);
        // }
        // elseif (isset($update["my_chat_member"])) {
        //     $this->handleChatMemberUpdate($update["my_chat_member"]);
        // }
        // elseif (isset($update["message"]) || isset($update["edited_message"])) {
        //     $this->handleMessage($update);
        // }
    }

    public function isNewChannel()
    {
        if (isset($update["my_chat_member"])) {
            $chat = $update["my_chat_member"]["chat"];
            $user = $update["my_chat_member"]["from"];
            
            if ($chat['type'] === 'channel') {

                $insertQuery = "INSERT INTO telegram (routename, routeinitials, routeslug, routeuserid) VALUES (?, ?, ?, ?)";
                $insertStmt = $this->db->prepare($insertQuery);
                $insertStmt->bind_param("sssi", $routename, $initials, $slug, $userid);
                
                if ($insertStmt->execute()) {
    
                // Sauvegarder le channel et l'admin
                // $this->saveChannel([
                //     'id' => $chat['id'],
                //     'title' => $chat['title'],
                //     'type' => 'channel',
                //     'user_telegram_id' => $user['id'],  // ID Telegram de l'admin
                //     'status' => $update["my_chat_member"]["new_chat_member"]["status"]
                // ]);
                }
            }
        }
    }

    public function getError() {
        return $this->error;
    }

    
}