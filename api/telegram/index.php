<?php
require_once '../../vendor/autoload.php';
require_once '../../app/config/config.php';

use App\Services\Telegram\TelegramService;
use App\Utils\Logger;

$logger = Logger::getInstance();
$telegramService = new TelegramService();

// Récupérer l'update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

$logger->log($update);

if (!isset($update["update_id"]) || $update["update_id"] <= 0) {
    $logger->lexit("Invalid update_id");
}

// Traiter l'update
$telegramService->handleUpdate($update);
