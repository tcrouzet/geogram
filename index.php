<?php

set_time_limit(60);
ini_set('display_errors', 1);
require_once(__DIR__ . '/admin/secret.php');
include (__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/admin/filemanager.php');
require_once(__DIR__ . '/admin/functions.php');

define('SPACE1', '&nbsp;');
define('SPACE2', '&nbsp;&nbsp;');
define('SPACE3', '&nbsp;&nbsp;&nbsp;');

init();

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
list($group,$page,$id)=$parts;
$group_id = "";
$start = 0;
$chatObj = null;

$fileManager = new FileManager();

//dump($group);

if($group=="help"){
    require("admin/help.php");
}elseif($group=="news_fr"){
    require("admin/news.php");
}elseif($group=="contact"){
    require("admin/contact.php");
}elseif($group=="archives"){
    $archives="archives";
    require("admin/une.php");

}elseif(!empty($group)){
    $chatObj = get_chat_by_name($group);
    if($chatObj){
        $group_id = $chatObj["chatid"];
        $start = $chatObj["start"];

        if($page=="story") {
            require("admin/story.php");
        }elseif($page=="info") {
            require("admin/info.php");
        }else{
            if(empty($start) || $start>time()){
                $start = 0;
            }
            require("admin/map.php");
        }        

    }else{
        $group="404";
        require("admin/404_page.php");
    }

}else{
    $archives="";
    require("admin/une.php");
}

require("admin/footer.php");

?>