<?php
// exit("test");

require_once __DIR__ . '/../../vendor/autoload.php';;
require_once __DIR__ . '/../../app/config/config.php';

use App\Services\Telegram\TelegramService;
use App\Services\ContextService;
use App\Utils\Convert;
use App\Services\MapService;
use App\Services\RouteService;
use App\Services\UserService;

set_time_limit(60);


if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}


// $conv = new Convert();  
// $conv->import_logs();


$mapS = new MapService();
$userS = new UserService();
$contextS = new ContextService();
$routeS = new RouteService();

$logs = $contextS->lastNlogs();
foreach($logs as $log){
  // $context = $contextS->weather($log['loglatitude'],$log['loglongitude']);

  $context = $contextS->findContext($log['loglatitude'],$log['loglongitude']);
  $contextS->updateLogContext($log['logid'], $context);
  sleep(1);
}

exit("EN test");

// Reclame telegram user 
// $target = $userS->get_user(186);
// print_r($target);

// $userS->delete_user(52);
// $userS->purgeuser(186);
// $userS->mergeAccounts(158, 159);

/*
SELECT usertelegram, userid, username, useremail, COUNT(*) as duplicates
FROM users 
WHERE usertelegram IS NOT NULL 
  AND usertelegram != 0
GROUP BY usertelegram 
HAVING COUNT(*) > 1;


UPDATE users 
SET usertelegram = NULL 
WHERE usertelegram = 0;
*/


/*
CREATE TABLE `context` (
  `contextid` bigint(20) AUTO_INCREMENT PRIMARY KEY,
  `lat_grid` decimal(8,5) NOT NULL,
  `lon_grid` decimal(8,5) NOT NULL,
  `city_name` varchar(255),
  `weather_data` JSON,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `grid_coords` (`lat_grid`, `lon_grid`),
  KEY `cleanup_index` (`created_at`)       -- Pour le nettoyage
);
*/