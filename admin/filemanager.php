<?php

class FileManager {
    public $absolute_path;

    //OLD
    public $userimg;
    public $userimg_dir;

    //NEW
    public $datadir;
    public $datadir_abs;

    public function __construct() {
        $this->absolute_path = realpath(__DIR__ . '/..')."/";

        //OLD
        $this->userimg_dir = "userimg/";
        $this->userimg =  $this->absolute_path . ltrim($this->userimg_dir,"/");

        //NEW
        $this->datadir = "userdata/";
        $this->datadir_abs = $this->absolute_path . $this->datadir;
    }

    public function chat_imgdir($chatid) {
        return $this->userimg . (string)round($chatid) . "/";
    }

    public function chatWeb($chatObj, $absolute=true) {
        $prefix = "";
        if($absolute)
            $prefix = BASE_URL;
        return $prefix . "/". $chatObj['chatname'];
    }

    //USER

    public function user_dir($chatid,$userid) {
        return $this->chat_imgdir($chatid) . (string)round($userid) ."/";
    }

    public function userWeb($chatObj, $userid, $absolute=false) {
        $prefix = "";
        if($absolute)
            $prefix = BASE_URL;
        return $prefix . "/". $chatObj['chatname']."/user/".round($userid);
    }

    public function make_chat_dir($chatid) {
        $path = $this->chat_imgdir($chatid);
        if (!is_dir($path)) {
            if(!mkdir($path, 0777, true)) {
                lexit("make_userimg_dir BUG");
            }
        }
        return true;
    }

    public function rename_chat_dir($oldid,$newid){
        $old_path = $this->chat_imgdir($oldid);
        if (!is_dir($old_path)) {
            return $this->make_chat_dir($newid);
        }else{
            $new_path = $this->chat_imgdir($newid);
            if(rename($old_path, $new_path))
                return true;
            else
                return false;
        }
    }

    public function delete_chat_dir($chatid){
        $path = $this->chat_imgdir($chatid);
        return $this->supDir($path);
    }

    public function delete_user_dir($chatid,$userid){
        $dir = $this->user_dir($chatid,$userid);
        return $this->supDir($dir);
    }


    function supDir($dossier) {
        if (is_dir($dossier)) {
            $objets = scandir($dossier);
            foreach ($objets as $objet) {
                if ($objet != "." && $objet != "..") {
                    if (filetype($dossier."/".$objet) == "dir") {
                        $this->supDir($dossier."/".$objet);
                    } else {
                        unlink($dossier."/".$objet);
                    }
                }
            }
            reset($objets);
            rmdir($dossier);
            return true;
        }else{
            false;
        }
    }

    // CHAT PHOTO

    public function chatphoto($chatid) {
        return $this->chat_imgdir($chatid) . "chatphoto.jpeg";
    }

    public function chatphotoWeb($chatObj,$timestamp=false,$absolute=false) {
        $path = $this->chatphoto($chatObj['chatid']);
        $prefix = "";
        if($absolute){
            $prefix = BASE_URL;
        }
        if($timestamp)
            return $prefix . $this->relative($path) . "?" . strtotime(@$chatObj['last_update']);
        else
            return $prefix . $this->relative($path);
    }

    //GPX

    public function chatgpx($chatid) {
        return $this->chat_imgdir($chatid) . "optimize.gpx";
    }

    public function chatgpx_source($chatid) {
        return $this->chat_imgdir($chatid) . "source.gpx";        
    }

    public function chatgpxWeb($chatid,$timestamp=false) {
        $gpx = $this->chatgpx($chatid);
        if(file_exists($gpx)){
            $gpx = $this->relative($gpx);
            if($timestamp)
                return $gpx .= "?" . strtotime($timestamp);
            else
                return $gpx;
        }else
            return 'v.gpx';
        
    }

    //NEW

    public function route_dir($routeid){
        $routedir = $this->datadir_abs . "routes/$routeid/";
        if (!is_dir($routedir)) {
            if(!mkdir($routedir, 0777, true))
                return false;
        }
        return $routedir;
    }

    public function gpx_source($routeid) {
        $dir = $this->route_dir($routeid);
        if($dir)
            return  $dir . "source.gpx";
        else
            return false;
    }


    //GEOJSON

    public function geojson($chatid) {
        return $this->chat_imgdir($chatid) . "optimize.geojson";
    }

    public function geojsonWeb($chat) {
        $geojson = $this->geojson($chat['chatid']);
        return $this->relative($geojson) . "?" . strtotime($chat['last_update']);
    }

    //NEW
    public function route_geojson($routeid) {
        return $this->route_dir($routeid) . "source.geojson";
    }

    public function route_geojson_web($route) {
        if($route['gpx']){
            $geojson = $this->route_geojson($route['routeid']);
            return $this->relativize($geojson,$this->datadir) . "?" . strtotime($route['last_update']);
        }else{
            return false;
        }
    }

    //AVATAR

    public function avatarFile($chatid,$userid){
        return $this->user_dir($chatid,$userid) ."avatar.jpeg";
    }

    public function avatarExists($chatid,$userid) {
        $path = $this->avatarFile($chatid,$userid);
        if (file_exists($path)){
            //echo("exist: $path");
            return $path;
        }else{
            return false;
        }
    }

    public function avatar($chatid,$userid) {
        $dir = $this->user_dir($chatid,$userid);
        if(!is_dir($dir))
            @mkdir($dir, 0777, true);
        return $dir. "avatar.jpeg";
    }

    public function avatarWeb($chatObj,$userid, $timestamp=false, $absolute=false) {
        $path = $this->avatarFile($chatObj['chatid'],$userid);
        $prefix = "";
        if($absolute)
            $prefix = BASE_URL;
        if($timestamp)
            return $prefix . $this->relative($path) . "?" . strtotime(@$chatObj['last_update']);
        else
            return $prefix . $this->relative($path);
    }


    public function chatimg($chatid,$userid,$timestamp) {
        $path= $this->chat_imgdir($chatid) . (string)round($userid). "/";
        if (!is_dir($path)) {
            if(!mkdir($path, 0777, true)) {
                lexit("make_chatimg_dir BUG");
            }
        }

        $imgpath_root = $path . $timestamp;
        
        $i = 0;
        $indice = ".png";
        while(file_exists($imgpath_root . $indice)){
            lecho("Exist $i");
            $i++;
            $indice = "_$i.png";
        }

        if($i>0)
            $pname = "_$i";
        else
            $pname = "";

        return [
            'full_path' => "/" . ltrim($imgpath_root,"/") . $indice,
            'pname' => $timestamp.$pname,
            'relative' => $this->relative($imgpath_root . $indice)
        ];
    }

    public function relative($path){
        $path = str_replace($this->absolute_path,"",$path);
        $path = ltrim($path,"/");
        if (substr($path, 0, 8) !== $this->userimg_dir) {
            $path = "userimg" . "/" . $path;
        }
        return $path;
    }

    //NEW
    
    public function relativize($path,$datadir){
        $path = str_replace($this->absolute_path,"",$path);
        $path = ltrim($path,"/");
        if (substr($path, 0, strlen($datadir)) !== $datadir) {
            $path = $datadir . $path;
        }
        return $path;
    }


    public function help_user(){
        return BASE_URL."help#join";
    }

    public function help_admin(){
        return BASE_URL."help#help_admin";
    }

    public function old_chatphoto($chatid) {
        return $this->userimg . "chatphoto/". (string)round($chatid) . ".jpeg";
    }

    public function old_chatgpx($chatid) {
        return $this->userimg . "gpx/". (string)round($chatid) . ".gpx";
    }

    public function old_chatgpx_source($chatid) {
        return $this->userimg . "gpx/". (string)round($chatid) . "-source.gpx";
    }

    public function old_avatar($userid) {
        return $this->userimg . "avatars/" . (string)round($userid). ".jpeg";
    }


}

?>