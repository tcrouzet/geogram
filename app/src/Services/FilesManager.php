<?php
namespace App\Services;

class FilesManager {
    public $absolute_path;

    //NEW
    public $datadir;
    public $datadir_abs;

    public function __construct() {
        $this->absolute_path = ROOT_PATH . "/";

        $this->datadir = "userdata/";
        $this->datadir_abs = $this->absolute_path . $this->datadir;
    }


    public function supDir($dossier) {
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

    // NEW

    public function transfertUserData($fromUserId, $toUserId){
        lecho("Transferring user data from $fromUserId to $toUserId");
        $fromDir = $this->user_dir2($fromUserId);
        $toDir =  $this->user_dir2($toUserId);
        
        if (!is_dir($fromDir)) {
            lecho("Source directory does not exist");
            return true;
        }
        
        if (!is_dir($toDir)) {
            if (!mkdir($toDir, 0777, true)) {
                lecho("Failed to create destination directory");
                return false;
            }
        }
        
        // Utiliser une commande système pour copier (plus rapide pour de gros volumes)
        $command = "cp -r " . escapeshellarg($fromDir . "*") . " " . escapeshellarg($toDir);
        $result = shell_exec($command . " 2>&1");
        
        if ($result === null) {
            lecho("Transfer completed successfully");
            return true;
        }else{
            lecho("Transfer failed: " . $result);
            return false;
        }
    }

    public function purgeUserData($userid) {
        $dir = $this->user_dir2($userid);

        if ($dir) {
            $objets = scandir($dir);
            foreach ($objets as $objet) {
                if ($objet != "." && $objet != "..") {
                    if (filetype($dir.$objet) == "dir") {
                        $this->supDir($dir.$objet);
                    }
                }
            }
            return true;
        }
        return false;
    }

    function purgeUserRouteData($userid, $routeid) {
        $dir = $this->user_route_dir($userid, $routeid);
        return $this->supDir($dir);
    }


    // NEW

    public function route_photo($routeid) {
        $dir = $this->route_dir($routeid);
        if($dir)
            return  $dir . "photo.jpeg";
        else
            return false;
    }

    public function route_photo_web($route) {
        if($route['routephoto']){
            $photo = $this->route_photo($route['routeid']);
            return $this->relativize($photo,$this->datadir) . "?" . strtotime($route['routeupdate']);
        }else{
            return false;
        }
    }

    public function user_photo($userid) {
        $dir = $this->user_dir2($userid);
        if($dir)
            return  $dir . "photo.jpeg";
        else
            return false;
    }

    public function user_photo_web($user) {
        if($user['userphoto']){
            $photo = $this->user_photo($user['userid']);
            return $this->relativize($photo,$this->datadir) . "?" . strtotime($user['userupdate']);
        }else{
            return false;
        }
    }

    public function user_route_photo($userid, $routeid, $timestamp, $logphoto) {
        $dir = $this->user_route_dir($userid, $routeid);
        if($dir)
            return  $dir . "$timestamp" . "_" . "$logphoto" . ".webp";
        else
            return false;
    }

    public function user_route_photo_web($log,$index=1) {
        if($log['logphoto']){
            $photo = $this->user_route_photo($log['loguser'], $log['logroute'], strtotime($log['logtime']), $index);
            $photo .= "?" . strtotime($log['logupdate']);
            return $this->relativize($photo,$this->datadir);
        }else{
            return false;
        }
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

    public function user_dir2($userid){
        $userdir = $this->datadir_abs . "users/$userid/";
        if (!is_dir($userdir)) {
            if(!mkdir($userdir, 0777, true))
                return false;
        }
        return $userdir;
    }

    public function user_route_dir($userid, $routeid){
        $userdir = $this->datadir_abs . "users/$userid/$routeid/";
        if (!is_dir($userdir)) {
            if(!mkdir($userdir, 0777, true))
                return false;
        }
        return $userdir;
    }

    public function gpx_source($routeid) {
        $dir = $this->route_dir($routeid);
        if($dir)
            return  $dir . "source.gpx";
        else
            return false;
    }

    public function gpx_mini($routeid) {
        $dir = $this->route_dir($routeid);
        if($dir)
            return  $dir . "source-mini.gpx";
        else
            return false;
    }


    //GEOJSON

    // public function geojson($chatid) {
    //     return $this->chat_imgdir($chatid) . "optimize.geojson";
    // }

    // public function geojsonWeb($chat) {
    //     $geojson = $this->geojson($chat['chatid']);
    //     return $this->relative($geojson) . "?" . strtotime($chat['last_update']);
    // }

    //NEW
    public function route_geojson($routeid) {
        return $this->route_dir($routeid) . "source.geojson";
    }

    public function route_geojson_web($route) {
        if($route['gpx']){
            $geojson = $this->route_geojson($route['routeid']);
            return $this->relativize($geojson,$this->datadir) . "?" . strtotime($route['routeupdate']);
        }else{
            return false;
        }
    }
    
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

}