<?php
// exit("test");

require_once __DIR__ . '/../../vendor/autoload.php';;
// require_once '../config/config.php';
// require_once '../config/telegram.php';

use App\Services\Telegram\TelegramService;

set_time_limit(60);


if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

use App\Utils\Convert;
use App\Services\MapService;

// $conv = new Convert();  
// $conv->import_logs();


$map = new MapService();  
$r = $map->get_map_data(33);
dump($r);
