<?php

define("DEBUG", false);
define("VERSION", "2.1");
define('ADMIN_IPS', ['0.0.0.0']);
define('ADMIN_TELEGRAM', [0]);
define("GEONAME", "GeoZÉFAL");
define("IMAGE_DEF",1600);
define("IMAGE_COMPRESS",70);
define("TOKEN_EXPIRATION", 86400*90); // 90 jours

//Urls
define('GEO_DOMAIN', 'geo.zefal.com');
define('BASE_URL', "https://".GEO_DOMAIN);
define('LOGO',"/assets/img/geozefal-logo.png");

// Configuration de la base de données
define('MYSQL_HOST', "127.0.0.1:3306");
define('MYSQL_USER', "user");
define('MYSQL_PSW', "psw");
define('MYSQL_BASE', "bikepacking");

// Configuration des chemins
define("ROOT_PATH", dirname(__DIR__, 2));
define('USERDATA','userdata');
define("UPLOAD_PATH", ROOT_PATH . "/" . USERDATA . "/");

// Texts
define("BASELINE", 'This instance of <a href="https://github.com/tcrouzet/geogram">Geogram</a> is published with the support of <a href="https://www.zefal.com/">Zéfal</a>.');
define("DESCRIPTION", "Tracks adventures. No Spot or Garmin tracker, just your phone, your photos, your comments. Open source and free.");
define("AUTHOR", "Thierry Crouzet");

//Front
define('FORBIDDEN_SLUG', ['login','routes','test','callback','user','help','contact','donate','story','assets','api','app','userimg']);
define("OK_PAGES", ['map','list','story']);
define('TESTROUTE',19);

// Configuration des logs
ini_set('log_errors', 'On');
ini_set('error_log', ROOT_PATH . '/logs/error_php.log');

//Weather
define('WEATHER_API', 'key');

//Encrypt
define('JWT_SECRET','key');


