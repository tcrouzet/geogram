<?php

define("REPLY_TIMEDIFF","Respond to this message with your time difference in hours; otherwise, delete the message.");
define("REPLY_DESCRIPTION","Respond to this message with a description of your Geogram; otherwise, delete the message.");
define("REPLY_RENAME","Respond to this message with the new name of your Geogram; otherwise, delete the message.");
define("REPLY_SHARE","Respond to this message with the invitation link of your group; otherwise, delete the message. Your adventure will be open to everyone. Respond with \"none\" to delete the link.");

function callback_manager($callbackQuery){
    global $telegram;
    $brut_userid=get_userid($callbackQuery["from"]);
    $userid=round($brut_userid);
    $username = get_username($callbackQuery["from"]);
    $chatId = $callbackQuery['message']['chat']['id'];
    $chat_title = get_chatitle($callbackQuery['message']);
    $chat = get_chat($chatId);
    //lecho("callBack",$chatId);
    $messageId = $callbackQuery['message']['message_id'];
    $userAction = $callbackQuery['data'];

    $parts = explode("_", $userAction);
    $mainAction = $parts[0];
    $userid_cmd = isset($parts[1]) ? $parts[1] : '';
    if($userid_cmd==$userid)
        $user_flag=true;
    else
        $user_flag=false;

    $admin_flag = is_admin($chat, $brut_userid);

    if($userAction=="goback"){
        sendMenu($chat, $messageId);
    }elseif($userAction=="cansel" && $admin_flag){
        sendMenuAdmin($chatId,$messageId);

    //INFO
    }elseif($userAction=="info" && $admin_flag){
        sendMenuInfo($chatId,$messageId,$brut_userid);
    }elseif(strpos($userAction, "info_delete_") !== false && $admin_flag){
        $cleanedAction = str_replace("info_delete_", "", $userAction);
        $confirmaction = str_replace("_delete_","_confirmdelete_",$userAction);
        list($theuserid,$theusername) = explode("_", $cleanedAction);
        $msg = "Do you really want to delete all messages and positions for $theusername?";
        sendMenuConfirm($chatId,$messageId,$msg,$confirmaction);
    }elseif(strpos($userAction, "info_confirmdelete_") !== false && $admin_flag){
        $cleanedAction = str_replace("info_confirmdelete_", "", $userAction);
        list($theuserid,$theusername) = explode("_", $cleanedAction);
        if(delete_one_user($chatId,$theuserid)){
            $msg = "$theusername not anuymore o, Geogram $chat_title.";
        }else{
            $msg = "Something wrong happens!";
        }
        sendMenuInfo($chatId,$messageId,$brut_userid); 

    //PURGE
    }elseif($userAction=="purge" && $admin_flag){
        $msg = "Do you really want to delete all messages and positions?";
        $msg .= " Can be usefull just before the start of an adventure.";
        sendMenuConfirm($chatId,$messageId,$msg,"purgeconfirm");
    }elseif($userAction=="purgeconfirm" && $admin_flag){
        $msg = "Are you sure? The action is irreversible!!!";
        sendMenuConfirm($chatId,$messageId,$msg,"purgereconfirm");
    }elseif($userAction=="purgereconfirm" && $admin_flag){
        $msg = "Are you sure? The action is irreversible!!!";
        $logdeleted = purge($chatId);
        sendMenuAdmin($chatId,$messageId,"$logdeleted logs deleted.");

    //DELETE
    }elseif($userAction=="delete" && $admin_flag){
        $msg = "Do you really want to delete messages, positions, gpx…?";
        $msg .= " Usefull if you don't need the adventure story.";
        sendMenuConfirm($chatId,$messageId,$msg,"purgeconfirm");
    }elseif($userAction=="deleteconfirm" && $admin_flag){
        $msg = "Are you sure? The action is irreversible!!!";
        sendMenuConfirm($chatId,$messageId,$msg,"deletereconfirm");
    }elseif($userAction=="deletereconfirm" && $admin_flag){
        $msg = "Are you sure? The action is irreversible!!!";
        delete_one_chat($chatId);
        sendMenuAdmin($chatId,$messageId,"You have a white pas.");

    //MODES
    }elseif($userAction=="mode" && $admin_flag){
        sendMenuMode($chatId,$messageId,$chat);
    }elseif(strpos($userAction, "mode_") !== false && $admin_flag){
        update_mode($chatId,$userAction);
        sendMenuMode($chatId,$messageId,$chat);

    //UNITS
    }elseif($userAction=="units" && $admin_flag){
        sendMenuUnits($chatId,$messageId,$chat);
    }elseif($userAction=="unit_timediff" && $admin_flag){
        $telegram->sendMessage(['chat_id' => $chatId,'text' => REPLY_TIMEDIFF]);
    }elseif(strpos($userAction, "unit_") !== false && $admin_flag){
        update_unit($chatId,$userAction);
        sendMenuUnits($chatId,$messageId,$chat);

    //REAL TIME
    }elseif($userAction=="realtime_set" && $admin_flag){
        update_real_time($chatId,1);
        sendMenuAdmin($chatId,$messageId,"Real time mode updated!");
    }elseif($userAction=="realtime_reset" && $admin_flag){
        update_real_time($chatId,0);
        sendMenuAdmin($chatId,$messageId,"Real time mode updated!");

    //DESCRIPTION
    }elseif($userAction=="description" && $admin_flag){
        $telegram->sendMessage(['chat_id' => $chatId,'text' => REPLY_DESCRIPTION]);

    //SHARE
    }elseif($userAction=="share" && $admin_flag){
        $telegram->sendMessage(['chat_id' => $chatId,'text' => REPLY_SHARE]);

    //RENAME
    }elseif($userAction=="rename" && $admin_flag){
        $telegram->sendMessage(['chat_id' => $chatId,'text' => REPLY_RENAME]);

    //START/STOP
    }elseif($userAction=="start" && $admin_flag){
        set_start($chatId,time());
        sendMenuAdmin($chatId,$messageId,"Adventure started! Time is counting…");
    }elseif($userAction=="started" && $admin_flag){
        sendMenuStarted($chatId,$messageId,$chat);
    }elseif($userAction=="start0" && $admin_flag){
        set_start($chatId,0);
        set_stop($chatId, 0);
        sendMenuAdmin($chatId,$messageId,"Adventure started time reset to zero!");
    }elseif($userAction=="stop" && $admin_flag){
        $msg = "Do you really want to stop and archive ".(string)$chat['chatname']."?";
        $msg .= " Adventures are automatically archived after one week of inactivity.";
        sendMenuConfirm($chatId,$messageId,$msg,"stopconfirm");
    }elseif($userAction=="stopconfirm" && $admin_flag){
        set_stop($chatId,time());
        sendMenuAdmin($chatId,$messageId,"Adventure stopped and archived! GPX deleted from base.");
    }elseif($userAction=="stopped" && $admin_flag){
        sendMenuAdmin($chatId,$messageId,"Adventure allaready stopped and archived!");

    //ADMIN
    }elseif($userAction=="admin" && $admin_flag){
        sendMenuAdmin($chatId,$messageId);
    }elseif($userAction=="admin"){
        ShortLivedMessage($chatId,"Your are nor admin!");

    //ADVENTURE PHOTO
    }elseif($userAction=="sendchatphoto" && $admin_flag){
        sendMenuAdmin($chatId,$messageId);
        update_photoprofil($chatId);

    //AVATAR
    }elseif($mainAction=="avatar" && $user_flag){
        if(newavatar($chatId, $brut_userid)){
            ShortLivedMessage($chatId,"Your avatar is updated!");
        }else{
            ShortLivedMessage($chatId,"Your first have to associate a picture to your Telegram account!");
        }
    
    //DISAPPEAR
    }elseif($mainAction=="disappear" && $user_flag){
        $msg = "$username, do you really want to quit $chat_title?";
        $msg .= ' Your history will be deleted. You will reappear if you geolocate again.';
        sendMenuConfirm($chatId,$messageId,$msg,"disappearconfirm_$userid");
    }elseif($mainAction=="disappearconfirm" && $user_flag){
        $msg = "$username, are you really sure? This action is irreversible!!!";
        sendMenuConfirm($chatId,$messageId,$msg,"disappearreconfirm_$userid");
    }elseif($mainAction=="disappearreconfirm" && $user_flag){
        if(delete_one_user($chatId,$brut_userid)){
            $msg = "$username, you are not anymore on $chat_title, but you are still on the group. Il you geolocalise again you will reappear.";
        }else{
            $msg = "Something wrong happens!";
        }
        sendMenuUser($chat,$brut_userid,$messageId,$msg,$username);

    //USER
    }elseif($userAction=="user"){
        sendMenuUser($chat,$brut_userid,$messageId,"",$username);

    //DEFAULT
    }else{
        sendMenu($chat, $messageId);
    }

    lexit("End Callback");

}

function is_admin($chat, $userid){
    global $telegram;

    if($chat['adminid']==round($userid)) return true;

    $member = $telegram->getChatMember(array('chat_id' => $chat['chatid'], 'user_id' => round($userid)));
    //lecho("is_admin",$chatid,$userid,$member);

    if(!isset($member["result"]))
        return false;
    if($member["result"]["status"] == 'creator' || $member["result"]["status"] == 'administrator'){
        update_adminid($chat['chatid'], $userid);
        return true;
    }else
        return false;
}

function insert_chat($chatid,$chat_name){
    global $mysqli;

    $query = "INSERT INTO `chats` (chatid, chatname) VALUES (?, ?) ON DUPLICATE KEY UPDATE chatname=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iss', $chatid, $chat_name, $chat_name);
    if($stmt->execute())
        return true;
    else
        return false;
}

function get_chat($chatid){
    global $stmt_getchatid;
    $stmt_getchatid->bind_param("i", round($chatid));
    $stmt_getchatid->execute();
    $chat = $stmt_getchatid->get_result()->fetch_assoc() ?? false;

    if($chat){
        return($chat);
    }else{
        return false;
    }
}

function purge($chatid,$all=true){
    global $mysqli;
    if($all){
        $query = "DELETE FROM logs WHERE chatid=$chatid;";
    }else{
        //Hold on last ones
        $query = "DELETE FROM logs WHERE (userid, timestamp) NOT IN (SELECT userid, MAX(timestamp) FROM logs GROUP BY userid) AND chatid=$chatid;";
    }
    $mysqli->query($query);
    lmicrotime();
    lecho("Purge done");
    return $mysqli->affected_rows;
}

function is_active_user($chatid,$userid){
    global $mysqli;

    $query = "SELECT count(1) FROM `logs` WHERE userid=? AND chatid=?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", round($userid), round($chatid));
    $stmt->execute();
    $value = $stmt->get_result()->fetch_row()[0];
    lecho("is_active",$chatid,$userid,$value);
    $stmt->close();
    return $value;
}

function delete_one_user($chatid,$userid){
    global $mysqli,$fileManager;
    $query = "DELETE FROM logs WHERE chatid=".round($chatid)." AND userid=".round($userid);
    $mysqli->query($query);
    return $fileManager->delete_user_dir($chatid,$userid);
}

function delete_one_chat($chatid){
    global $mysqli,$fileManager;

    $query = "DELETE FROM logs WHERE chatid=$chatid";
    $mysqli->query($query);

    $query = "DELETE FROM chats WHERE chatid=$chatid";
    $mysqli->query($query);

    $query = "DELETE FROM gpx WHERE chatid=$chatid";
    $mysqli->query($query);

    return $fileManager->delete_chat_dir($chatid);

}

function update_chatid($old_chatid,$new_chatid){
    global $mysqli;

    lecho("update_chaid",$old_chatid,$new_chatid);

    $query = "UPDATE `chats` SET chatid = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", round($new_chatid), round($old_chatid));
    $stmt_photo->execute();

    //Rename dir
}

function update_mode($chatid,$mode_text){
    global $mysqli;

    lecho("update_mode",$mode_text);

    if($mode_text=='mode_silent')
        $mode=0;
    elseif($mode_text=='mode_normal')
        $mode=1;
    else
        $mode=2;

    $query = "UPDATE `chats` SET mode = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", $mode, round($chatid));
    $stmt_photo->execute();
}

function update_real_time($chatid,$value){
    global $mysqli;
    lecho("real_time",$value);

    $query = "UPDATE `chats` SET real_time = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", $value, round($chatid));
    $stmt_photo->execute();
}

function update_unit($chatid,$mode_text){
    global $mysqli;

    lecho("update_unit",$mode_text);

    if($mode_text=='unit_metric')
        $mode=0;
    else
        $mode=1;
    lecho($mode);

    $query = "UPDATE `chats` SET unit = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", $mode, round($chatid));
    $stmt_photo->execute();
}

function update_menuid($chatid,$id){
    global $mysqli;

    lecho("update_menuid",$id);
    $query = "UPDATE `chats` SET menuid = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", $id, round($chatid));
    $stmt_photo->execute();
}

function update_adminid($chatid,$id){
    global $mysqli;

    lecho("update_adminid",$id);
    $query = "UPDATE `chats` SET adminid = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", $id, round($chatid));
    $stmt_photo->execute();
}

function update_link($chatid,$value){
    global $mysqli;

    $url = trim($value);
    if (!(filter_var($url, FILTER_VALIDATE_URL) && strpos($url, "https://t.me/") === 0)){
        lecho("non URL");
        $url = "";
    }

    $query = "UPDATE `chats` SET link = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("si", $url, round($chatid));
    $stmt_photo->execute();
}

function update_description($chatid,$text){
    global $mysqli;

    $desc = trim($text);
    $query = "UPDATE `chats` SET description = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("si", $desc, round($chatid));
    return $stmt_photo->execute();
}

function update_chatname($chatid,$text){
    global $mysqli;

    $title = format_chatitle($text);
    $query = "UPDATE `chats` SET chatname = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("si", $title, round($chatid));
    return $stmt_photo->execute();
}

function update_timediff($chatid,$text){
    global $mysqli;

    lecho("Timediff update");
    $timediff = intval($text);
    $roundedChatId = round($chatid);

    $query = "UPDATE `chats` SET timediff = ? WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("ii", $timediff, $roundedChatId);
    $stmt_photo->execute();
}

function update_photoprofil($chatid){
    global $telegram,$fileManager;
    $response = $telegram->getChat(['chat_id' => $chatid]);

    if (isset($response['result']['photo'])) {
        lecho("photo",$response['result']['photo']);
        $photo = $response['result']['photo'];
        $file = $telegram->getFile($photo['small_file_id']);
        lecho($file);
        if ($file['ok']) {
            $file_path = $file['result']['file_path'];
            $photo_path = $fileManager->chatphoto($chatid);
            $telegram->downloadFile($file_path, $photo_path);
            if(file_exists($photo_path)){
                set_photo($chatid);
                ShortLivedMessage($chatid,"Profil picture uploaded!");
            }else{
                ShortLivedMessage($chatid,"Can't download profil picture!");
                return false;
            }

        }else{
            ShortLivedMessage($chatid,"Impossible to get profil picture!");
            return false;
        }

    }else{
        ShortLivedMessage($chatid,"Your group have no profil picture!");
        return false;
    }
}

function set_photo($chatid){
    global $mysqli;

    $query = "UPDATE `chats` SET photo = 1 WHERE chatid=?;";
    $stmt_photo = $mysqli->prepare($query);
    $stmt_photo->bind_param("i", $chatid);
    return $stmt_photo->execute();
}

function set_start($chatid, $timestamp){
    global $mysqli;

    $query = "UPDATE `chats` SET start=? WHERE chatid=?;";
    $stmt_query = $mysqli->prepare($query);
    $stmt_query->bind_param("ii", $timestamp, $chatid);
    $stmt_query->execute();
    //lecho("start ".$chatid." ".date("j F Y H:i",$timestamp));
}

function set_stop($chatid, $timestamp){
    global $mysqli;

    $query = "UPDATE `chats` SET stop=? WHERE chatid=?;";
    $stmt_query = $mysqli->prepare($query);
    $stmt_query->bind_param("ii", $timestamp, $chatid);
    $stmt_query->execute();

    if($timestamp>0){
        //No more GPX in base
        $query = "DELETE FROM gpx WHERE chatid=$chatid";
        $mysqli->query($query);    
    }
    lecho("stop ".$chatid." ".date("j F Y H:i",$timestamp));
}

function newavatar($chatid,$userid){
    global $telegram,$fileManager;

    $param=array('user_id' => $userid, 'offset' => 0,'limit' => 1);
    $user_profile_photos = $telegram->getUserProfilePhotos($param);
    if($user_profile_photos["ok"]){
        if(isset($user_profile_photos["result"]["photos"][0][0])){
            $photo=$user_profile_photos["result"]["photos"][0][0];
            $file_id = $telegram->getFile($photo["file_id"]);
            $absfilepath=$fileManager->avatar($chatid,$userid);
            $telegram->downloadFile($file_id['result']['file_path'], $absfilepath);
            lecho("photo ok");
            return true;
        }else{
            lecho("Can't find photo");
            return false;
        }
    }else{
        lecho("No photo");
        return false;
    }

}

function timestampsToDuration($startTimestamp, $endTimestamp) {
    $diffInSeconds = $endTimestamp - $startTimestamp;
    $hours = floor($diffInSeconds / 3600);
    $minutes = floor(($diffInSeconds % 3600) / 60);

    return sprintf("%dh %02dmin", $hours, $minutes);
}

function ShortLivedMessage($id,$msg,$timeout=2){
    global $telegram;

    $response = $telegram->sendMessage(['chat_id' => $id,'text' => $msg]);
    virtual_finish();
    //lecho($response);

    if (isset($response['ok'])) {
        $messageId = $response['result']['message_id'];
        //lecho("ShortLivedMessage", $messageId);
        sleep($timeout);
        $telegram->deleteMessage(['chat_id' => $id, 'message_id' => $messageId, 'disable_notification' => true]);
    }

}

function sendMenu($chat, $messageId="") {
    global $telegram,$fileManager;

    $inlineKeyboard = [
        [
            ['text' => format_chatname($chat["chatname"]), 'url' => $fileManager->chatWeb($chat,true)],
        ],[
            ['text' => 'User', 'callback_data' => 'user'],
            ['text' => 'Admin', 'callback_data' => 'admin']
        ]
    ];

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $msg = format_chatname($chat["chatname"]). " dashboard.";

    $params = [
        'chat_id' => $chat["chatid"],
        'text' => $msg,
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];

    if(empty($messageId)){
        $response = $telegram->sendMessage($params);
    }else{
        $params['message_id'] = $messageId;
        $response = $telegram->editMessageText($params);
    }
    lecho("keyboard",$response);
    if($response['ok']){
        lecho("keyboardID",$response['result']['message_id']);
        return $response['result']['message_id'];
    }else{
        return null;
    }

}

function sendMenuAdmin($brut_chatid, $messageId, $message="") {
    global $telegram,$fileManager;

    $chat = get_chat($brut_chatid);

    //lecho("menuadmin",$chat);
    if($chat['start']==0 || $chat['stop']>0){
        //lecho("start");
        $start = "Start";
        $start_cmd = "start";
    }else{
        $start = timestampsToDuration($chat['start'],time());
        $start_cmd = "started";
    }

    if($chat['stop']>0){
        $stop = "Archived";
        $stop_cmd = "stopped";
    }else{
        $stop = "Archive";
        $stop_cmd = "stop";
    }

    if($chat['real_time']>0){
        $rt = "Real time✓";
        $rt_cmd = "realtime_reset";
    }else{
        $rt = "Real time";
        $rt_cmd = "realtime_set";
    }

    $inlineKeyboard = [
        [
            ['text' => ' < ', 'callback_data' => 'goback'],
        ],[
            ['text' => 'Info', 'callback_data' => 'info'],
            ['text' => 'Help', 'url' => $fileManager->help_admin()],
        ],[
            ['text' => $start, 'callback_data' => $start_cmd],
            ['text' => $stop, 'callback_data' => $stop_cmd],
        ],[
            ['text' => 'Description', 'callback_data' => 'description'],
            ['text' => 'Rename', 'callback_data' => 'rename'],
            ['text' => 'Share', 'callback_data' => 'share'],
        ],[
            ['text' => 'Mode', 'callback_data' => 'mode'],
            ['text' => $rt, 'callback_data' => $rt_cmd],
            ['text' => 'Units', 'callback_data' => 'units'],
        ],[
            ['text' => 'Purge', 'callback_data' => 'purge'],
            ['text' => 'Delete', 'callback_data' => 'delete'],
        ]
    ];

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    if(empty($message)){
        $message = format_chatname($chat['chatname']).' admin dashboard…'; 
    }

    $params = [
        'chat_id' => $brut_chatid,
        'message_id' => $messageId,
        'text' => $message,
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];    

    lecho($params);
    $response = $telegram->editMessageText($params);
    //lecho("keyboard:", $response);
}

function sendMenuUser($chat,$brut_userid,$messageId,$message="",$username="") {
    global $telegram, $fileManager;

    $userid=round($brut_userid);
    $user_logs = is_active_user($chat['chatid'],$userid);

    if($user_logs == 0){

        //New user
        $message = $username."\n";
        $message .= "First, you have to geolocate to start your history on ".format_chatname($chat['chatname']).".\n";
        $message .= "Click on the Paperclip on the left side of the input field, then click on the Location icon and choose Send my current location.";

        $inlineKeyboard = [
            [
                ['text' => ' < ', 'callback_data' => 'goback'],
            ]
        ];

    }else{

        if($path_avatar = $fileManager->avatarExists($chat['chatid'],$userid)){
            $up_avatar_text = 'Reupload avatar';
            $avatar_url = $fileManager->avatarWeb($chat,$brut_userid,true,true); 
            $avatar_text = "Avatar";
            $avatar_cmd = "url";
        }else{
            $up_avatar_text = 'Upload avatar';
            $avatar_url = "avatar_$userid"; 
            $avatar_text = "No avatar";
            $avatar_cmd = "callback_data";
        }

        $history_url = $fileManager->userWeb($chat,$brut_userid,true);

        $inlineKeyboard = [
            [
                ['text' => ' < ', 'callback_data' => 'goback'],
            ],[
                ['text' => $avatar_text, $avatar_cmd => $avatar_url],
                ['text' => 'History', 'url' => $history_url],
                ['text' => 'Help', 'url' => $fileManager->help_user()],
            ],[
                ['text' => $up_avatar_text, 'callback_data' => "avatar_$userid"],
                ['text' => 'Disappear', 'callback_data' => "disappear_$userid"],
            ]
        ];

        if(empty($message)){
            $log_msg="log";
            if($user_logs>1)
               $log_msg.="s";
            $message = "$username ($user_logs $log_msg)";
            if($avatar_text == "No avatar"){
                $message .= "\nIf you have a profile picture, use \"Upload avatar\" to upload it on Geogram.\n";
            }
        }
    }

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $params = [
        'chat_id' => $chat['chatid'],
        'message_id' => $messageId,
        'text' => $message,
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true,
    ];    

    $response = $telegram->editMessageText($params);
    //lecho("keyboard:", $response);
}

function sendMenuConfirm($brut_chatid,$messageId,$message,$cmd) {
    global $telegram;

    $inlineKeyboard = [
        [
            ['text' => 'Cansel', 'callback_data' => 'cansel'],
            ['text' => 'Confirm', 'callback_data' => $cmd],
        ]
    ];

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $params = [
        'chat_id' => $brut_chatid,
        'message_id' => $messageId,
        'text' => $message,
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];    

    $response = $telegram->editMessageText($params);
    //lecho("keyboard:", $response);

}

function sendMenuMode($brut_chatid,$messageId,$chat) {
    global $telegram;

    $mode = $chat['mode'];
    $option_n = "";
    $option_v = "";
    $option_s = "";
    if($mode==0)
        $option_s = "✓";
    elseif($mode==1)
        $option_n = "✓";
    else
        $option_v = "✓";

    $inlineKeyboard = [
        [
            ['text' => ' < ', 'callback_data' => 'goback'],
        ],[
            ['text' => 'Silent'.$option_s, 'callback_data' => 'mode_silent'],
            ['text' => 'Normal'.$option_n, 'callback_data' => 'mode_normal'],
            ['text' => 'Verbose'.$option_v, 'callback_data' => 'mode_verbose'],
        ]
    ];

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $params = [
        'chat_id' => $brut_chatid,
        'message_id' => $messageId,
        'text' => "Bot actions on messages after processing. Silent: all messages are immediately deleted. Normal: only localisations are deleted. Verbose: nothing is deleted.",
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];    

    $response = $telegram->editMessageText($params);
    //lecho("keyboard:", $response);

}

function sendMenuUnits($brut_chatid,$messageId,$chat) {
    global $telegram;

    $unit = $chat['unit'];
    $option_m = "";
    $option_e = "";
    if($unit==0)
        $option_m = "✓";
    else
        $option_e = "✓";

    $inlineKeyboard = [
        [
            ['text' => ' < ', 'callback_data' => 'goback'],
        ],[
            ['text' => 'Metric'.$option_m, 'callback_data' => 'unit_metric'],
            ['text' => 'Emperial'.$option_e, 'callback_data' => 'unit_emperial'],
        ],[
            ['text' => 'Timediff:'.(string)$chat['timediff'].'h', 'callback_data' => 'unit_timediff'],
        ]
    ];

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $currentTime = new DateTime();
    $currentTime->add(new DateInterval('PT' . $chat['timediff'] . 'H'));
    $currentTime->format('Y-m-d H:i:s');
    $msg = "Select the display units on geogram website for ".$chat['chatname'].".\n";
    $msg .= "For Geogram is now ".$currentTime->format('H:i').". Adjust Timediff to your timezone.";

    $params = [
        'chat_id' => $brut_chatid,
        'message_id' => $messageId,
        'text' => $msg,
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];    

    $response = $telegram->editMessageText($params);
    //lecho("keyboard:", $response);

}

function sendMenuStarted($brut_chatid,$messageId,$chat) {
    global $telegram;

    $date = date("Y-m-d H:i", $chat['start']);

    $inlineKeyboard = [
        [
            ['text' => ' < ', 'callback_data' => 'goback'],
        ],[
            ['text' => 'Restart', 'callback_data' => 'start'],
            ['text' => 'Reset', 'callback_data' => 'start0'],
        ]
    ];

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $params = [
        'chat_id' => $brut_chatid,
        'message_id' => $messageId,
        'text' => $chat['chatname']." starting date: $date. You can restart now or reset to zero (for open adventures with no timing).",
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];    

    $response = $telegram->editMessageText($params);
    //lecho("keyboard:", $response);

}

function sendMenuInfo($brut_chatid,$messageId,$brut_userid) {
    global $telegram,$mysqli,$fileManager;

    //lecho("Info");
    $chatid = round($brut_chatid);
    $chat = get_chat($chatid);
    $cmd=array();

    if($chat){

        $message = $chat['chatname']."\n";
        $message .= $chat['chatid']."\n";
        if(empty($chat['description']))
            $message .= "No description.\n";
        else
            $message .= $chat['description']."\n";

        $dateTime = new DateTime(@$chat['creationdate']);
        $creation = $dateTime->format('Y-m-d H:m');
        $message .= "Creation: " .$creation."\n"; 
        $dateTime = new DateTime(@$chat['last_update']);
        $updated = $dateTime->format('Y-m-d H:m');
        $message .= "Updated: " .$updated."\n";

        $geogram_url = $fileManager->chatWeb($chat);

        if($chat['gpx']){
            $message .= "GPX: ". meters_to_distance($chat['total_km'],$chat)."/".meters_to_dev($chat['total_dev'],$chat)."\n";
        }else{
            $message .= "You need to publish your GPX on the chat, then geolocalise at least once.\n";
        }

        if($chat['unit']){
            $message .= "Unit: emperial\n";
        }else{
            $message .= "Unit: metric\n";
        }

        if($chat['link']){
            $message .= "Public invitation link: ".$chat['link'];
        }else{
            $message .= "Private group";
        }

        $chatphoto = $fileManager->chatphoto($brut_chatid);
        if(file_exists($chatphoto)){
            $avatar_text = "Adventure picture";
            $avatar_url = $fileManager->chatphotoWeb($chat,true,true);
            $avatar_cmd = 'url';
        }else {
            $avatar_text = "Send adventure picture";
            $avatar_url = "sendchatphoto";
            $avatar_cmd = 'callback_data';
        }

        $query = "SELECT userid,username FROM `logs` WHERE chatid=? GROUP BY userid;";
        $stmt_query = $mysqli->prepare($query);
        $stmt_query->bind_param("i", $chatid);
        $stmt_query->execute();
        $users = $stmt_query->get_result();
        foreach($users as $user){
            if($user["userid"]!=round($brut_userid)){
                $url = $fileManager->userWeb($chat,$user["userid"],true);
                $avatar = $fileManager->avatarWeb($chat,$user["userid"],true,true);
                $cmd[] = [
                    ['text' => $user["username"], 'url' => $url],
                    ['text' => "Avatar", 'url' => $avatar],
                    ['text' => 'DELETE', 'callback_data' => 'info_delete_'.$user["userid"].'_'.$user["username"]]
                ];
            }
        }
        //lecho($cmd);

    }else{
        $infos = "Telegram ChatIds changent sometime when deleting all messages or other actions. This is the case for the chat $chatid you manage. You have to create a new chat, and start from scratch. Sorry.\n\n";
        $infos .= " If the Chat very import to you contact Geogram admin fort help.";
    }    

    $inlineKeyboard = [
        [
            ['text' => ' < ', 'callback_data' => 'goback'],
        ],
        [
            ['text' => "Geogram page", 'url' => $geogram_url],
        ],
        [
            ['text' => $avatar_text, $avatar_cmd => $avatar_url],
        ],
            ...$cmd
    ];

    //lecho("keyboard",$inlineKeyboard);

    $replyMarkup = [
        'inline_keyboard' => $inlineKeyboard
    ];

    $params = [
        'chat_id' => $brut_chatid,
        'message_id' => $messageId,
        'text' => $message,
        'reply_markup' => json_encode($replyMarkup),
        'disable_notification' => true
    ];    

    $response = $telegram->editMessageText($params);
    //lecho("InfoKeyboard",$response);
}

function ChatMemberUpdate($update){
    global $telegram;

    lecho("ChatMember",$update);

    if ($update['new_chat_member']){
        $bot_name = $update['new_chat_member']['user']["username"]=="GeoBikepacking_bot";
        $bot_status = $update['new_chat_member']['status'];
    }else{
        lexit("ChatMember - no bot detexted");
    }

    if ($bot_status == 'left' && $bot_name=="GeoBikepacking_bot"){

        //BOT QUIT THE GROUP
        $chatid = $update['chat']['id'];
        delete_one_chat($chatid);
        lexit("MemberOUT Chat Deleted");

    }elseif ($bot_status == 'member' && $bot_name=="GeoBikepacking_bot") {

        //BOT IN THE GROUP
        $chatid = $update['chat']['id'];
        $chat_name = get_chatitle($update);
        $msg = "Hello, I'm the GeoBikepacking_bot. You need to give me admistrator right to manage $chat_name for you.";
        $telegram->sendMessage(['chat_id' => $chatid,'text' => $msg]);
        lexit("MemberOK");

    }elseif ($bot_status == 'administrator' && $bot_name=="GeoBikepacking_bot") {

        //BOT IS ADMINISTRATOR
        $chatid = $update['chat']['id'];
        $chat_name = get_chatitle($update);
        insert_chat($chatid, $chat_name);
        $chat = ["chatid" => $chatid, "chatname" => $chat_name];
        sendMenu($chat,"");
        lexit("AdmlinustratorOK");
    }

    lexit("ChatMember Unknown");
}

function NewChatID($update){
    global $fileManager;

    lecho("NewChatID");
    $old_chatid = $update["migrate_from_chat_id"];
    $new_chatid = $update['chat']['id'];
    if(get_chat($old_chatid)){
        update_chatid($old_chatid,$new_chatid);
        $fileManager->rename_chat_dir($old_chatid,$new_chatid);
        lexit("NewChatID");
    }else{
        lexit("Old chatid not existing");
    }
}

function ReplyManager($chatid, $message_id, $reply, $case_descripion){
    global $telegram;

    if($reply["reply_to_message"]["text"]==$case_descripion){
        lecho("Value",$reply["text"]);
        switch ($case_descripion) {
            case REPLY_TIMEDIFF:
                $echo = "Timediff updated!";
                update_timediff($chatid, $reply["text"]);
                break;
            case REPLY_DESCRIPTION:
                lecho("ReplyManager",$reply);
                update_description($chatid, $reply["text"]);
                $echo = "Description updated!";
                break;
            case REPLY_RENAME:
                if(update_chatname($chatid, $reply["text"]))
                    $echo = "Chat renamed!";
                else
                    $echo = "Name allready in use!";
                break;
            case REPLY_SHARE:
                update_link($chatid,$reply["text"]);
                $echo = "Link updated!";
                break;
        }

        $response = $telegram->deleteMessage(['chat_id' => $chatid, 'message_id' => $reply["reply_to_message"]["message_id"]]);
        $response = $telegram->deleteMessage(['chat_id' => $chatid, 'message_id' => $message_id]);
        ShortLivedMessage($chatid,$echo);
        lexit("Reply $echo");
    }

}
?>