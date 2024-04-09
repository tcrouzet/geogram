<?php

//https://geogram.tcrouzet.com/telegram.php
//exit("not running");

$startTime = microtime(true);
$logBuffer = [];
define("DEBUG",false);

ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/logs/error_php.log');

require_once(__DIR__ . '/admin/secret.php');
require_once(__DIR__ . '/admin/filemanager.php');
require_once(__DIR__ . '/admin/functions.php');
require_once(__DIR__ . '/admin/functions_robot.php');
require_once(__DIR__ . '/admin/callback.php');

$content = file_get_contents("php://input");
$update = json_decode($content, true);

lecho($update);

if (!isset($update["update_id"]) || $update["update_id"] <= 0) {
    lexit("Inavid update_id");
}

set_time_limit(60);
ini_set('display_errors', 1);
include (__DIR__ . '/vendor/autoload.php');

$telegram = new Telegram(TELEGRAM_BOT_TOKEN);

init();
$fileManager = new FileManager();
prepare_queries();
lmicrotime();

//CALLBACK
if( isset($update["callback_query"])){
    callback_manager($update["callback_query"]);
}

//NEW CHAT
if( isset($update["my_chat_member"])){
    ChatMemberUpdate($update["my_chat_member"]);
}

if( isset($update["message"])){
    $message = $update["message"];
}elseif( isset($update["edited_message"])) {
    $message = $update["edited_message"];
}

$message_id = $message["message_id"];

//Timestamp
if( isset($message["edit_date"]) ){
    $timestamp = $message["edit_date"];
}else{
    $timestamp = $message["date"];
}

//chatid
$brut_chatid = $message["chat"]["id"];
$chatid = round($brut_chatid);
$chat_title = get_chatitle($message);
if ( empty($chat_title) ){
    lexit("empty chat_title");
}

//TEST
if( isset($message["group_chat_created"])){
    lexit("NewGroupCreated");
}
//MIGGRATE
if( isset($message["migrate_to_chat_id"])){
    NewChatID($message);
}

$chat_obj = get_chat($chatid);

//userid
$brut_userid=get_userid($message["from"]);
$userid = round($brut_userid);


//MENU
if( isset($message["text"]) && $message["text"] == "/menu"){

    if($chat_obj["menuid"]){
        $response = $telegram->deleteMessage(['chat_id' => $brut_chatid, 'message_id' => $chat_obj["menuid"]]);
    }
    $response = $telegram->deleteMessage(['chat_id' => $brut_chatid, 'message_id' => $message_id]);
    $menuid = sendMenu($chat_obj);
    update_menuid($brut_chatid,$menuid);
    lexit("Menu");
}

//STOPED
if($chat_obj['stop']>0 && $chat_obj['stop']<time()){
    lexit("stoped");
}

//LOCATION
if(isset($message["location"])){

    //lecho("Location find ",$userid);

    $username = get_username($message["from"]); 
    if(empty($username)){
        $telegram->deleteMessage(array('chat_id' => $chatid,'message_id' => $message_id));
        lexit("no username");
    }

    //No more than one location/10minutes except for the admin and test purpose
    if(!(is_admin($chat_obj, $userid) && $chat_obj['real_time']==0)){
        $query = "SELECT EXISTS(SELECT 1 FROM logs WHERE userid = ? AND timestamp > (UNIX_TIMESTAMP() - 600))";
        $stmt_lastuser = $mysqli->prepare($query);
        $stmt_lastuser->bind_param("i", $userid);
        $stmt_lastuser->execute();
        $result = $stmt_lastuser->get_result()->fetch_row();
        if ($result && $result[0]) {
            //Posted location during last 10 minutes
            if($chat_obj['real_time']==0){
                $telegram->deleteMessage(array('chat_id' => $chatid,'message_id' => $message_id));
                ShortLivedMessage($brut_chatid,"$username, you are too fast. Next localisation will be possible in 10 minutes.");
            }
            lexit("Too fast");
        }
    }

    $latitude = $message["location"]["latitude"];
    $longitude = $message["location"]["longitude"];

    //nerest_point_on_route
    $r = nearest_point($latitude,$longitude,$chatid,$userid);
    if($r){
        $gpx_point = $r["point"];
        $km = round($r["km"]);
        $dev = round($r["dev"]);
    }else{
        $gpx_point = -1;
        $km = 0;
        $dev = 0;
    }

    //Save
    lmicrotime("savelogSart");
    $stmt_insertlog->bind_param('iisiddidd', $chatid, $userid, $username, $timestamp, $latitude, $longitude, $gpx_point, $km, $dev);
    $stmt_insertlog->execute();
    lmicrotime("savelogEnd");

    if(isset($message["location"]["live_period"]) && $chat_obj['real_time']==1){
        //Real time
    }else{
        //Normal
        todelete($chat_obj,$message_id,2);
        ShortLivedMessage($brut_chatid,"$username, your are on the map!");
    }
    lexit("End location");

}


//REPLY
if( isset($message["reply_to_message"])){

    if(is_admin($chat_obj, $userid)){
    
        ReplyManager($chatid, $message_id, $message, REPLY_RENAME);
        ReplyManager($chatid, $message_id, $message, REPLY_TIMEDIFF);
        ReplyManager($chatid, $message_id, $message, REPLY_DESCRIPTION);
        ReplyManager($chatid, $message_id, $message, REPLY_SHARE);
        ReplyManager($chatid, $message_id, $message, REPLY_FUTURE);
        lexit("Admin Reply unknown");
    }
    
}


//TEXT
if( isset($message["text"])){
       
    //user comment
    $query = "UPDATE logs SET comment = JSON_SET(comment, '$.T".$timestamp."', '".$message["text"]."') WHERE chatid = $chatid AND userid = $userid AND timestamp = (SELECT MAX(timestamp) FROM logs WHERE chatid = $chatid AND userid = $userid)";
    $result = $mysqli->query($query);
    if(!$result){
        lecho("Error sql 2 - no log for the user\n");
    }
    todelete($chat_obj,$message_id);
    lexit("text");

}


//PHOTO
if( isset($message["photo"])){

    $max_index = max(array_keys($message["photo"]));
    $file_id = $message["photo"][$max_index]["file_id"];
    $file = $telegram->getFile($file_id);

    if($file["ok"]){

        $photo = $fileManager->chatimg($chatid,$userid,$timestamp);
        $telegram->downloadFile($file['result']['file_path'], $photo['full_path']);

        if(file_exists($photo['full_path'])){
            lecho("Photo OK ".$photo['full_path']);

            $query = "UPDATE logs SET comment = JSON_SET(comment, '$.P".$photo['pname']."', '".$photo['relative']."') WHERE chatid = $chatid AND userid = $userid AND timestamp = (SELECT MAX(timestamp) FROM logs WHERE chatid = $chatid AND userid = $userid)";
            //dump($query);
            $result = $mysqli->query($query);
            if(!$result){
                lexit("Error sql 3");
            }
        }

    }

    todelete($chat_obj,$message_id);
    lexit("Photo");

    //End photo
}


//CHAT PROFILE
if(isset($message["new_chat_photo"])){

    lecho("NewChatPhoto");
    $max_index = max(array_keys($message["new_chat_photo"]));
    $file_id = $message["new_chat_photo"][$max_index]["file_id"];
    $file = $telegram->getFile($file_id);

    if($file["ok"]){

        $photo = $fileManager->chatphoto($chatid);
        $telegram->downloadFile($file['result']['file_path'], $photo);

        if(file_exists($photo)){
            lecho("Chat Photo OK ".$photo);
            set_photo($chatid);
        }
    }

    todelete($chat_obj,$message_id);
    lexit("ChatPhoto");

}


//FILE
if(isset($message["document"])){

    if (substr($message["document"]["file_name"], -4) === ".gpx") {
        //GPX OK

        lecho("New GPX");

        $file = $telegram->getFile($message["document"]["file_id"]);
        if($file["ok"]){

            virtual_finish();
            ShortLivedMessage($brut_chatid,"Your GPX is processing.");
            $source = $fileManager->chatgpx_source($chatid);
            $telegram->downloadFile($file['result']['file_path'], $source);

            gpx2base($source);

        }
    }

    todelete($chat_obj,$message_id);
    lexit("GPX");

}

lexit("Unknown");

?>