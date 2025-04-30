<?php

//https://geo.zefal.com/api/telegram/

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

lecho("Telegram bot");

// Récupérer l'update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

lecho($update);

if (empty($update) || !isset($update["update_id"]) || $update["update_id"] <= 0) {
    lecho("Invalid update_id");
}else{
    // Traiter l'update
    lecho("Update");
    $telegramService = new TelegramService($update);
    $telegramService->handleUpdate();
    lecho( $telegramService->getError() );
}
lecho("Telegram bot done");
lexit();
