<?php
// app/src/Services/MapService.php

namespace App\Services;

use App\Services\Database;
use App\Services\RouteService;
use App\Utils\Tools;
use App\Services\FilesManager;
use App\Services\Gpx\GpxNearest;

class MapService 
{
    private $db;
    private $fileManager;
    private $route;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->route = new RouteService();
    }
    
    function loadMapData(){

        //$routestatus = $_POST['routestatus'] ?? '';
        $routeid = $_POST['routeid'] ?? '';
        //$userroute = $_POST['userroute'] ?? '';

        // if($routestatus >1 ){
        //     //Private route
        //     $userid = $_POST['userid'] ?? '';
        //     if (!$this->auth->testToken($userid)){
        //         return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
        //     }
        //     if ($routeid != $userroute){
        //         return ['status' => 'error', 'message' => "User $userid (on route:$userroute) not connected to this route $routeid (status:$routestatus)"];
        //     }
        // }

        return $this->get_map_data($routeid);

    }

    function get_map_data($routeid){

        $route = $this->route->get_route_by_id($routeid);

        if(!empty($route["link"]) && $route["stop"]==0){

            // $start = time()-86400*7;
            // $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? AND timestamp> ? GROUP BY userid) ORDER BY km DESC,username ASC;";
            // $stmt = $mysqli->prepare($query);
            // $stmt->bind_param("iii", $route['chatid'], $route['chatid'], $start);

        }elseif ($route["start"]>0){

            // $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY km DESC,username ASC;";
            // $stmt = $mysqli->prepare($query);
            // $stmt->bind_param("ii", $route['chatid'], $route['chatid']);

        }else{

            $query = "SELECT *
                FROM rlogs
                INNER JOIN users ON rlogs.loguser = users.userid
                WHERE rlogs.logroute = ?
                AND (rlogs.loguser, rlogs.logtime) IN (
                    SELECT loguser, MAX(logtime)
                    FROM rlogs
                    WHERE logroute = ?
                    GROUP BY loguser
                )
                ORDER BY rlogs.logtime DESC;";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ii", $routeid, $routeid);
        }

        return $this->format_map_data($stmt, $route); 
    }

    function format_map_data($stmt, $route){
        lecho("format_map_data");
        $stmt->execute();
        if($result = $stmt->get_result()){
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            //lecho($logs);

            $geojson = $this->fileManager->route_geojson_web($route);

            foreach ($logs as &$row) {
                //lecho("Routetime", $row["logtime"]);
                $row['logkm_km'] = Tools::meters_to_distance($row["logkm"], $route, false);
                $row['username_formatted'] = $row['username'] . "<br/>" . Tools::MyDateFormat($row['logtime'],$route) . "<br/>" . Tools::meters_to_distance($row["logkm"], $route);
                $row['photopath'] = $this->fileManager->user_photo_web($row);
                $row['photolog'] = $this->fileManager->user_route_photo_web($row);
                lecho($row['photopath']);
            }

            //lecho($logs);
            return ['status' => 'success', 'logs' => $logs, 'geojson' => $geojson];
        }else{
            return ['status' => 'error', 'message' => 'Map format fail'];
        }

    }

    function sendgeolocation(){
        lecho("sendgeolocation");
    
        $userid = $_POST['userid'] ?? '';
        $routeid = $_POST['routeid'] ?? '';
        if($routeid<1){
            return ['status' => 'error', 'message' => 'Route problem'];
        }
    
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        // lecho($latitude,  $longitude);
    
        if ($this->newlog($userid, $routeid, $latitude, $longitude)){
            return $this->get_map_data($routeid);
        }
        
        return ['status' => 'error', 'message' => 'Newlog error'];
    }
    
    function newlog($userid, $routeid, $latitude, $longitude, $message=null, $photo = 0, $timestamp = null){    
        lecho("NewLog");
    
        $nearest = new GpxNearest($routeid);
        $point = $nearest->user($latitude, $longitude);
        // lecho($point);
    
        if($point){
            $p = $point['gpxpoint'];
            $km = round($point['gpxkm']);
            $dev = round($point['gpxdev']);
            // $distance = round($point['distance']);
        }else{
            $p = -1;
            $km = 0;
            $dev = 0;
        }
    
        // Conversion en date au format ISO 8601 (YYYY-MM-DD HH:MM:SS)
        if($timestamp){
            $insertQuery = "INSERT  IGNORE INTO rlogs (logroute, loguser, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto, logtime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiddiiisii", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo, $timestamp);
        }else{
            // lecho("No timestamp");
            $insertQuery = "INSERT  IGNORE INTO rlogs (logroute, loguser, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiddiiisi", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo);
        }
        
        if ($insertStmt->execute() &&  $insertStmt->affected_rows > 0) {
            return true;
        }
        // lecho($insertStmt->error);
        // lecho($insertStmt->sqlstate);
        // lecho($insertStmt->errno);
        return false;
    }

    function userMarkers() {
        lecho("userMarkersN");
    
        $loguser = $_POST['loguser'] ?? '';
        $routeid = $_POST['routeid'] ?? '';
        return $this->get_userMarkers($loguser, $routeid);

    }

    function get_userMarkers($userid, $routeid) {
        $route = $this->route->get_route_by_id($routeid);
    
        $query = "SELECT * FROM rlogs l INNER JOIN users u ON l.loguser = u.userid WHERE loguser = ? AND logroute = ? ORDER BY l.loginsertime ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $userid, $routeid);
    
        return $this->format_map_data($stmt, $route);
    }

    function logphoto(){
        lecho("Route Photo Upload2");
        //lecho($_POST);
    
        $userid = $_POST['userid'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        $timestamp = $_POST['timestamp'] ?? '';
        if(empty($latitude) || empty($longitude) || empty($timestamp)){
            return ['status' => 'error', 'message' => 'GPS error'];
        }
    
        $routeid = $_POST['routeid'] ?? '';
        if(empty($routeid)){
            return ['status' => 'error', 'message' => 'Empty route'];
        }
    
        $photofile = $_POST['photofile'] ?? '';
        if(empty($photofile)) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
        $photosource = Tools::photo64decode($photofile);
        
        $target = $this->fileManager->user_route_photo($userid, $routeid, $timestamp);
        //lecho($target);
    
        if($target){
            if(Tools::resizeImage($photosource, $target, 1200)){
                if($this->newlog($userid, $routeid, $latitude, $longitude, null, $timestamp, $timestamp)){
                    return $this->get_userMarkers($userid, $routeid);
                }
            }
            lecho("resizeFail");
        }
    
        return ['status' => 'error', 'message' => 'Upload fail'];
    }

    private function random_geoloc(){
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
    
        // Rayon de variation en kilomètres
        $radius = 150;
    
        // Convertir le rayon en degrés
        $lat_variation = $radius / 111; // 1 degré de latitude ~ 111 km
        $lon_variation = $radius / (111 * cos(deg2rad($latitude))); // Ajustement longitude
    
        // Générer une variation aléatoire
        $random_lat_offset = (mt_rand() / mt_getrandmax() - 0.5) * 2 * $lat_variation;
        $random_lon_offset = (mt_rand() / mt_getrandmax() - 0.5) * 2 * $lon_variation;
    
        // Appliquer la variation
        $new_latitude = $latitude + $random_lat_offset;
        $new_longitude = $longitude + $random_lon_offset;
    
        // Afficher les nouvelles valeurs
        return [$new_latitude, $new_longitude];
    }
    
}