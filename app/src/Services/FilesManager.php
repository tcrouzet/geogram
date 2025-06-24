<?php
namespace App\Services;

class FilesManager {
    public $absolute_path;

    //NEW
    public $datadir;
    public $datadir_abs;
    public $error = "";

    public function __construct() {
        $this->absolute_path = ROOT_PATH . "/";

        $this->datadir = "userdata/";
        $this->datadir_abs = $this->absolute_path . $this->datadir;
    }

    public function getError(){
        return $this->error;
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

    public function copyFolderContent($source, $destination) {
        // Vérifier que le dossier source existe
        if (!is_dir($source)) {
            return false;
        }
        
        // Créer le dossier destination s'il n'existe pas
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                return false;
            }
        }
        
        // Ouvrir le dossier source
        $handle = opendir($source);
        if (!$handle) {
            return false;
        }
        
        $success = true;
        
        // Parcourir tous les éléments du dossier source
        while (($item = readdir($handle)) !== false) {
            // Ignorer les références . et ..
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($sourcePath)) {
                // Si c'est un dossier, appel récursif
                if (!$this->copyFolderContent($sourcePath, $destinationPath)) {
                    $success = false;
                }
            } else {
                // Si c'est un fichier, copier seulement s'il n'existe pas déjà
                if (!file_exists($destinationPath)) {
                    if (!copy($sourcePath, $destinationPath)) {
                        $success = false;
                    }
                }
            }
        }
        
        closedir($handle);
        return $success;
    }

    public function transfertUserData($fromUserId, $toUserId){
        lecho("Transferring user data from $fromUserId to $toUserId");
        $fromDir = $this->user_dir2($fromUserId);
        $toDir =  $this->user_dir2($toUserId);

        if($this->copyFolderContent($fromDir, $toDir)){
            lecho("Transfer completed successfully");
            return true;
        }else{
            lecho("Transfer failed");
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
            $this->error = "Could not create user route photo directory for user $userid and route $routeid";
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
                $this->error = "Could not create user route directory for user $userid and route $routeid";
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