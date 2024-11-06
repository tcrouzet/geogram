<?php
require_once '../../vendor/autoload.php';
require_once '../../app/config/config.php';
require_once '../../app/config/telegram.php';

use App\Services\Telegram\TelegramService;

set_time_limit(60);

if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Récupérer l'update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

lexit($update);

if (!isset($update["update_id"]) || $update["update_id"] <= 0) {
    lexit("Invalid update_id");
}else{
    // Traiter l'update
    $telegramService = new TelegramService($update);
    $telegramService->handleUpdate();
    lexit();
}
