<?php

// crontab -e
// */10 * * * * /usr/bin/php /var/www/html/geogram/app/src/Utils/cron.php

require_once __DIR__ . '/../../../vendor/autoload.php';;
require_once __DIR__ . '/../../../app/config/config.php';

use App\Services\ContextService;
use App\Utils\Logger;

$logger = Logger::getInstance();

set_time_limit(60);

if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    $logger->disableLogging();
    ini_set('display_errors', 0);
    error_reporting(0);
}

lecho("Sart cron");

$contextS = new ContextService();
$contextS->cron();

lecho("End cron");
?>