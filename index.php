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
$parsed = parse_url($_SERVER["REQUEST_URI"]);
//dump($parsed);
$just_url=$parsed['host'].$parsed['path'];
list($group,$page,$id)=explode("/",trim($just_url,"/"));
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
    $query="SELECT * FROM `chats` WHERE chatname = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result){
        $chatObj = $result->fetch_assoc();

        if ($result && !empty($chatObj["chatid"])) {
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
    }

}else{
    $archives="";
    require("admin/une.php");
}

require("admin/footer.php");

?>