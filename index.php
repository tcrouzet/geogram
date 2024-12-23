<?php

set_time_limit(60);

require_once './vendor/autoload.php';
require_once './app/config/config.php';
require_once './app/config/telegram.php';

use App\Services\RouteService;
use App\Services\UserService;

if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

//Analyse URL
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$scheme = 'http';
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $scheme = 'https';
}
$url = $scheme . "://" . $host . $uri;

$url = str_replace(BASE_URL,"",$url);
$parsed = parse_url($url);
$path = trim($parsed['path'], "/");
$parts = explode("/", $path);
$parts = array_pad($parts, 3, '');
//dump($parts);exit();

//Init
list($route_slug,$page,$userid)=$parts;
$routeid = "";
$route = null;
$pagename = "";
$OnMap = true;

//dump($group);

// }elseif($group=="news_fr"){
//     require("admin/news.php");
//     html_footer();
// }elseif($group=="archives"){
//     $archives="archives";
//     require("admin/une.php");
//     html_footer();

if (!empty($route_slug) && !in_array($route_slug, FORBIDDEN_SLUG)) {
    $route_O = new RouteService(); 
    $user_O = new UserService();
    $route = $route_O->get_route_by_slug($route_slug);
    if($route){
        $pagename = $route['routename'];
    }
    if($userid){
        $user = $user_O->get_user($userid);
    }
}elseif(in_array($route_slug, FORBIDDEN_SLUG)){
    $OnMap = false;
    $pagename = $route_slug;
}

require("app/views/html_header.php");

if($route_slug=="login"){
    require("app/views/login.php");
}elseif($route_slug=="test"){
    if (in_array($_SERVER['REMOTE_ADDR'], ADMIN_IPS)) {
        require("app/views/test.php");
    } else {
        require("app/views/map.php");
    }
}elseif($route_slug=="routes"){
    require("app/views/routes.php");
}elseif($route_slug=="user"){
    require("app/views/user.php");
}elseif($route_slug=="help"){
    require("app/views/help.php");
}elseif($route_slug=="contact"){
    require("app/views/contact.php");
}else{
    require("app/views/map.php");
}

require("app/views/html_footer.php");
