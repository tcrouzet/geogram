<?php

namespace App\Services\Telegram;

use App\Services\Database;
use App\Services\FilesManager;

class TelegramService 
{
    private $telegram;
    private $fileManager;
    private $db;
    private $user = null;
    
    public function __construct($user = null) 
    {
        $this->telegram = new \Telegram(TELEGRAM_BOT_TOKEN);
        $this->fileManager = new FilesManager();
        $this->db = Database::getInstance()->getConnection();
        $this->user = $user;
    }

    public function handleUpdate($update) 
    {
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

    public function getUserChannels() 
    {
        try {
            $adminChannels = [];
            // D'abord obtenir les chats où le bot est membre
            $updates = $this->telegram->getUpdates();
            if ($updates['ok']) {
                foreach ($updates['result'] as $update) {
                    if (isset($update['my_chat_member'])) {
                        $chat = $update['my_chat_member']['chat'];
                        if ($chat['type'] === 'channel') {
                            // Vérifier si l'utilisateur est admin
                            $member = $this->telegram->getChatMember([
                                'chat_id' => $chat['id'],
                                'user_id' => $this->user->userid
                            ]);
                            
                            if ($member['ok'] && 
                                in_array($member['result']['status'], ['creator', 'administrator'])) {
                                $adminChannels[] = [
                                    'id' => $chat['id'],
                                    'title' => $chat['title'],
                                    'type' => $chat['type']
                                ];
                            }
                        }
                    }
                }
            }
            
            return ['status' => 'success', 'channels' => $adminChannels];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
}