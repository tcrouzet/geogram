<?php
// app/src/Services/MapService.php

namespace App\Services;

use App\Services\Database;
use App\Services\RouteService;
use App\Services\UserService;
use App\Utils\Tools;
use App\Services\FilesManager;
use App\Services\Gpx\GpxNearest;

class MapService 
{
    private $user = null;
    private $userid = null;
    private $db;
    private $fileManager;
    private $route;
    private $routeid;
    private $error = false;
    
    public function __construct($user=null) 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->route = new RouteService();
        $this->user = $user;
        if($this->user)
            $this->userid = $this->user['userid'];

        $this->routeid = $_POST['routeid'] ?? '';
        if ($this->routeid < 1) {
            $this->error = ['status' => 'error', 'message' => "Route $this->routeid error"];
        }
    }

    public function getError() {
        return $this->error;
    }
    
    public function loadMapData(){
        lecho("load map data $this->routeid");
        return $this->get_map_data($this->routeid);
    }

    public function get_map_data($routeid){

        $route = $this->route->get_route_by_id($routeid);
        if(!$route){
            return ['status' => 'error', 'message' => 'Unknown route'];
        }

        $start = $this->resetSQLdate($route["routestart"]);
        $stop = $this->resetSQLdate($route["routestop"]); 

        $query = "SELECT *
            FROM rlogs
            INNER JOIN users ON rlogs.loguser = users.userid
            WHERE rlogs.logroute = ?
            AND (rlogs.loguser, rlogs.logtime) IN (
                SELECT loguser, MAX(logtime)
                FROM rlogs
                WHERE logroute = ?
                AND (? IS NULL OR logtime >= ?)
                AND (? IS NULL OR logtime <= ?)
                GROUP BY loguser
            )
            ORDER BY logtime DESC;";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iissss", 
            $routeid, 
            $routeid, 
            $start, 
            $start,
            $stop, 
            $stop
        );

        return $this->format_map_data($stmt, $route); 
    }

    public function format_map_data($stmt, $route){
        lecho("format_map_data");
        $stmt->execute();
        if($result = $stmt->get_result()){
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            //lecho($logs);

            $geojson = $this->fileManager->route_geojson_web($route);
            lecho("geoJSON:",$geojson);

            foreach ($logs as &$row) {
                //lecho($row['username']);
                $row['logkm_km'] = Tools::meters_to_distance($row["logkm"], $route, false);
                if($row['username'])
                    $row['username_formated'] = Tools::normalizeName($row['username']);
                else
                    $row['username']='Unknown';
                $row['date_formated'] = Tools::MyDateFormat($row['logtime'],$route);
                $row['photopath'] = $this->fileManager->user_photo_web($row);
                $row['photolog'] = $this->fileManager->user_route_photo_web($row);
                $row['comment_formated'] = Tools::formatMessage($row['logcomment']);
            }

            //lecho($logs);
            return ['status' => 'success', 'logs' => $logs, 'geojson' => $geojson];
        }
        return ['status' => 'error', 'message' => 'Map format fail'];
    }

    public function sendgeolocation(){
        lecho("sendgeolocation");
    
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        // lecho($latitude,  $longitude);
    
        if ($this->newlog($this->userid, $this->routeid, $latitude, $longitude)){
            return $this->get_map_data($this->routeid);
        }
        
        return ['status' => 'error', 'message' => 'Newlog error'];
    }

    private function resetSQLdate($mydate){
        if (empty($mydate) || $mydate == '0000-00-00 00:00:00')
            return null;
        else
            return $mydate;
    }

    private function isRouteActive($route) {
        if (!empty($route['routestop']) && 
            $route['routestop'] !== '0000-00-00 00:00:00' && 
            $route['routestop'] !== null) {
            
            $stopTime = strtotime($route['routestop']);
            if ($stopTime !== false) {
                return $stopTime > time();
            }
        }
        return true;
    }
    
    public function newlog($userid, $routeid, $latitude, $longitude, $message=null, $photo = 0, $timestamp = null, $weather = null, $city = null){    
        lecho("NewLog");

        $route = $this->route->get_route_by_id($routeid);
        if(!$this->isRouteActive($route)){
            $this->error = "Route closed";
            return false;
        }
        
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
    
        $weatherJson = json_encode($weather);
        $cityJson =  json_encode($city);

        // Conversion en date au format ISO 8601 (YYYY-MM-DD HH:MM:SS)
        if($timestamp){
            $insertQuery = "INSERT IGNORE INTO rlogs (logroute, loguser, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto, logweather, logcity, logtime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiddiiisissi", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo, $weatherJson, $cityJson, $timestamp);
        }else{
            lecho("No timestamp");
            $insertQuery = "INSERT IGNORE INTO rlogs (logroute, loguser, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto, logweather, logcity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiddiiisiss", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo, $weatherJson, $cityJson);
        }
        
        if ($insertStmt->execute() &&  $insertStmt->affected_rows > 0) {
            lecho("Insert ok");
            return true;
        }
        // lecho($insertStmt->error);
        // lecho($insertStmt->sqlstate);
        // lecho($insertStmt->errno);
        return false;
    }

    public function userMarkers() {
        lecho("userMarkersN");
    
        $loguser = $_POST['loguser'] ?? '';
        return $this->get_userMarkers($loguser, $this->routeid);

    }

    public function get_userMarkers($userid, $routeid) {
        $route = $this->route->get_route_by_id($routeid);
    
        $query = "SELECT * FROM rlogs l INNER JOIN users u ON l.loguser = u.userid WHERE loguser = ? AND logroute = ? ORDER BY l.logupdate ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $userid, $routeid);
    
        return $this->format_map_data($stmt, $route);
    }

    public function logphoto(){
        lecho("Route Photo Upload2");
        //lecho($_POST);
    
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        $timestamp = $_POST['timestamp'] ?? '';
        if(empty($latitude) || empty($longitude) || empty($timestamp)){
            return ['status' => 'error', 'message' => 'GPS error'];
        }
        
        $photofile = $_POST['photofile'] ?? '';
        if(empty($photofile)) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
        $photosource = Tools::photo64decode($photofile);
        
        $target = $this->fileManager->user_route_photo($this->userid, $this->routeid, $timestamp, 1);
        //lecho($target);
    
        if($target){
            if(Tools::resizeImage($photosource, $target, 1200)){
                if($this->newlog($this->userid, $this->routeid, $latitude, $longitude, null, 1, $timestamp)){
                    return $this->get_userMarkers($this->userid, $this->routeid);
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

    public function submitComment(){
        $logid = $_POST['logid'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $stmt = $this->db->prepare("UPDATE rlogs SET logcomment = ? WHERE logid = ?");
        $stmt->bind_param("si", $comment, $logid);
        if ($stmt->execute()){
            return $this->get_userMarkers($this->userid, $this->routeid);
        }
        return ['status' => 'error', 'message' => 'Comment error'];
    }

    public function story(){
        lecho("story");
        $routeid =  $_POST['routeid'] ?? '';

        if($routeid){

            $route = (new RouteService())->get_route_by_id($routeid);

            $start = $this->resetSQLdate($route["routestart"]);
            $stop = $this->resetSQLdate($route["routestop"]); 
    
            $query = "SELECT *
                FROM rlogs
                INNER JOIN users ON rlogs.loguser = users.userid
                WHERE rlogs.logroute = ?
                AND (? IS NULL OR logtime >= ?)
                AND (? IS NULL OR logtime <= ?)
                ORDER BY rlogs.logtime ASC;";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("issss", 
                $routeid, 
                $start, 
                $start,
                $stop, 
                $stop
            );
            $data = $this->format_map_data($stmt, $this->route->get_route_by_id($routeid));
            $data['route'] = $route;
            lecho($data);
            return $data; 
        }
        return ['status' => 'error', 'message' => 'Unknown story'];
    }
    
}