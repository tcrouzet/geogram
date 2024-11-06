<?php
namespace App\Services\Telegram;

use App\Services\Database;
use App\Services\FilesManager;

class TelegramCallback 
{
    private $telegram;
    private $cq;
    private $db;
    private $error = false;

    public function __construct($telegram, $callback_query) {
        $this->telegram = $telegram;
        $this->cq = $callback_query;
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleCallback(){
        //callback_manager($this->cq);
    }

    public function getError() {
        return $this->error;
    }

}
