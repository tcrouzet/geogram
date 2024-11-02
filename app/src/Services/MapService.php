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
    private $logger;
    private $auth;
    private $route;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->route = new RouteService();
    }
    
    function loadMapData(){

        $routestatus = $_POST['routestatus'] ?? '';
        $routeid = $_POST['routeid'] ?? '';
        $userroute = $_POST['userroute'] ?? '';

        if($routestatus >1 ){
            //Private route
            $userid = $_POST['userid'] ?? '';
            if (!$this->auth->testToken($userid)){
                return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
            }
            if ($routeid != $userroute){
                return ['status' => 'error', 'message' => "User $userid (on route:$userroute) not connected to this route $routeid (status:$routestatus)"];
            }
        }

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
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        //lecho($logs);

        $geojson = $this->fileManager->route_geojson_web($route);

        foreach ($logs as &$row) {
            //lecho("Routetime", $row["logtime"]);
            $row['logkm_km'] = Tools::meters_to_distance($row["logkm"], $route, false);
            $row['username_formatted'] = $row['username'] . "<br/>" . Tools::MyDateFormat($row['logtime'],$route) . "<br/>" . Tools::meters_to_distance($row["logkm"], $route);
            $row['photopath'] = $this->fileManager->user_photo_web($row);
            $row['photolog'] = $this->fileManager->user_route_photo_web($row);
            //lecho($row['photopath']);
        }

        //lecho($logs);
        return ['status' => 'success', 'logs' => $logs, 'geojson' => $geojson];    

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

        lecho($latitude,  $longitude);
    
        return $this->newlog($userid, $routeid, $latitude, $longitude);
    
    }
    
    function newlog($userid, $routeid, $latitude, $longitude, $message=null, $photo = null, $timestamp = null){    
        lecho("NewLog");
    
        $nearest = new GpxNearest($routeid);
        $point = $nearest->user($latitude, $longitude);
    
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
            $insertQuery = "INSERT  IGNORE INTO rlogs (logroute, loguser, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiddiiisi", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo);
        }
        
        if ($insertStmt->execute()) {
            return $this->get_map_data($routeid);
        }else{
            return ['status' => 'error', 'message' => 'SQL error'];    
        }
    
        return ['status' => 'error', 'message' => 'Log fail'];    
    }

    function userMarkers() {
        lecho("userMarkersN");
    
        $loguser = $_POST['loguser'] ?? '';
        $routeid = $_POST['routeid'] ?? '';
        $route = $this->route->get_route_by_id($routeid);
    
        $query = "SELECT * FROM rlogs l INNER JOIN users u ON l.loguser = u.userid WHERE loguser = ? AND logroute = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $loguser, $routeid);
    
        return $this->format_map_data($stmt, $route);
    }
    

}