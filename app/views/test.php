<?php
// exit("test");

require_once __DIR__ . '/../../vendor/autoload.php';;
require_once __DIR__ . '/../../app/config/config.php';
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
use App\Services\RouteService;
use App\Services\UserService;

// $conv = new Convert();  
// $conv->import_logs();


$mapS = new MapService();
$userS = new UserService();

// $target = $userS->get_user(186);
// print_r($target);

// $userS->delete_user(52);
// $userS->purgeuser(186);
$userS->mergeAccounts(158, 159);

/*
SELECT usertelegram, userid, username, useremail, COUNT(*) as duplicates
FROM users 
WHERE usertelegram IS NOT NULL 
  AND usertelegram != 0
GROUP BY usertelegram 
HAVING COUNT(*) > 1;
*/

/*
1002476276858
115
whatilip
whatilip@gmail.com
6
-1001669242626
89
lamy_bertrand
lamy_bertrand@yahoo.com
3



*/