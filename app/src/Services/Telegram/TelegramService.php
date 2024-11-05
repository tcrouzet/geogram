<?php

namespace App\Services\Telegram;

use App\Services\Database;
use App\Services\FilesManager;
use App\Utils\Logger;

class TelegramService 
{
    private $telegram;
    private $fileManager;
    private $db;
    private $logger;
    
    public function __construct() 
    {
        $this->telegram = new \Telegram(TELEGRAM_BOT_TOKEN);
        $this->fileManager = new FilesManager();
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
        
        set_time_limit(60);
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

}