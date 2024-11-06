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
    
    
    

}
