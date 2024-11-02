<?php

set_time_limit(60);

require_once './vendor/autoload.php';
require_once './app/config/config.php';

use App\Services\RouteService;

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

// if($group=="help"){
//     require("admin/help.php");
//     html_footer();
// }elseif($group=="news_fr"){
//     require("admin/news.php");
//     html_footer();
// }elseif($group=="contact"){
//     require("admin/contact.php");
//     html_footer();
// }elseif($group=="archives"){
//     $archives="archives";
//     require("admin/une.php");
//     html_footer();
// }elseif($group=="login"){
//     require("admin/login.php");
// }elseif($group=="test"){
//     require("admin/test.php");
// }elseif($group=="routes"){
//     require("admin/routes.php");
// }else

$slugs = ['login', 'routes', 'test'];
if (!empty($route_slug) && !in_array($route_slug, $slugs)) {
    $route_O = new RouteService(); 
    $route = $route_O->get_route_by_slug($route_slug);
    $pagename = $route['routename'];
}elseif(in_array($route_slug, $slugs)){
    $OnMap = false;
    $pagename = $route_slug;
}

require("app/views/html_header.php");

if($route_slug=="login"){
    require("app/views/login.php");
}elseif($route_slug=="test"){
    require("app/views/test.php");
}elseif($route_slug=="routes"){
    require("app/views/routes.php");
}else{
    require("app/views/map.php");
}

require("app/views/html_footer.php");
