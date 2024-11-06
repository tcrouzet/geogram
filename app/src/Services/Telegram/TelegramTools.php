<?php

namespace App\Services\Telegram;
use App\Utils\Tools;

class TelegramTools 
{

    public static function MyDateFormat($timestampInput, $route, $justhour=false){
    }

    public static function get_chatitle($message){
        if(@$message["chat"] && @$message["chat"]["title"])
            return Tools::format_title($message["chat"]["title"]);
        else
            return "NoTitle";
    }

    public static function get_userid($message){
        return $message["from"]["id"];
    }
    
    public static function get_username($message){
        $from = $message["from"];
        if( isset($from["username"]) ){
            $username = $from["username"];
        }elseif( isset($from["first_name"]) && isset($from["last_name"]) ){
            $username = $from["first_name"]." ".$from["last_name"];
        }elseif( isset($from["first_name"]) ){
            $username = $from["first_name"];
        }elseif( isset($from["last_name"]) ){
            $username = $from["last_name"];
        }
        return trim($username);
    }
    
    public static function ShortLivedMessage($telegram, $chatid, $msg, $timeout=2){    
        $response = $telegram->sendMessage(['chat_id' => $chatid, 'text' => $msg, 'disable_notification' => true]);
        virtual_finish();
        if (isset($response['ok'])) {
            $messageId = $response['result']['message_id'];
            //lecho("ShortLivedMessage", $messageId);
            sleep($timeout);
            $telegram->deleteMessage(['chat_id' => $chatid, 'message_id' => $messageId, 'disable_notification' => true]);
            return true;
        }
        return false;    
    }

    public static function todelete($telegram, $chatid, $message_id, $routemode, $level=1){    
        lecho("To delete",$level);
        if($routemode<$level){
            $telegram->deleteMessage(['chat_id' => $chatid, 'message_id' => $message_id]);
        }
    
    }
    
    
}
