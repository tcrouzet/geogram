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
        if($this->user){
            $this->userid = $this->user['userid'];
        }else{
            $this->userid = tools::getRequestData('userid', null);
        }

        $this->routeid = tools::getRequestData('routeid', 0);
        if ($this->routeid < 1) {
            $this->error = ['status' => 'error', 'message' => "Route $this->routeid error"];
        }
    }

    public function getError() {
        return $this->error;
    }

    public function getData($routeid=null){
        if(!$routeid){
            $routeid = $this->routeid;
        }
        lecho("get_data $routeid");

        $route = $this->route->get_route_by_id($routeid);
        if(!$route){
            return ['status' => 'error', 'message' => 'Unknown route'];
        }

        $start = $this->resetSQLdate($route["routestart"]);
        lecho("Start: " . ($start ?: 'NULL'));
        $stop = $this->resetSQLdate($route["routestop"]);

        if (isset($route['routelastdays']) && $route['routelastdays'] > 0) {
            $lastDaysTimestamp = date('Y-m-d H:i:s', time() - ($route['routelastdays'] * 86400));
            if ($lastDaysTimestamp > $start) {
                $start = $lastDaysTimestamp;
            }
        }
        lecho("Start: " . ($start ?: 'NULL') . " Stop: " . ($stop ?: 'NULL'));

        $currentDate = date('Y-m-d H:i:s');

        $hasStartFilter = !empty($start) && ($start < $currentDate);
        $hasStopFilter = !empty($stop);

        $query = "SELECT 
                logid, logroute, loguser, loglatitude, loglongitude, logkm, logdev, logtime, logupdate, logcomment, logphoto, logcontext, logtelegramid,
                userid, username, userphoto, usercolor, userinitials, userupdate
            FROM rlogs
            INNER JOIN users ON rlogs.loguser = users.userid
            WHERE rlogs.logroute = ? ";

        if ($hasStartFilter) {
            $query .= " AND logtime >= ?";
        }

        if ($hasStopFilter) {
            $query .= " AND logtime <= ?";
        }

        $query .= " ORDER BY rlogs.logtime DESC";   

        $stmt = $this->db->prepare($query);

        if ($hasStartFilter && $hasStopFilter) {
            $stmt->bind_param("iss", 
                $routeid,
                $start,
                $stop, 
            );
        }else if($hasStartFilter){
            $stmt->bind_param("is",
                $routeid, 
                $start,
            );
        }else if($hasStopFilter){
            $stmt->bind_param("is",
                $routeid, 
                $stop,
            );
        }else{
            $stmt->bind_param("i",
                $routeid
            );
        }

        return $this->format_map_data($stmt, $route);
    }

    public function format_map_data($stmt, $route){
        lecho("format_map_data");
        $stmt->execute();
        if($result = $stmt->get_result()){
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            lecho("Logs: ".count($logs));

            $geojson = $this->fileManager->route_geojson_web($route);
            lecho("geoJSON:",$geojson);

            foreach ($logs as &$row) {
                // lecho($row['username']);
                $row['logkm_km'] = Tools::meters_to_distance($row["logkm"], $route, false);
                if($row['username'])
                    $row['username_formated'] = Tools::normalizeName($row['username']);
                else
                    $row['username']='Unknown';
                $row['date_formated'] = Tools::MyDateFormat($row['logtime'],$route);
                $row['photopath'] = $this->fileManager->user_photo_web($row);
                $row['photolog'] = $this->fileManager->user_route_photo_web($row, $row['logphoto']);
                $row['comment_formated'] = Tools::formatMessage($row['logcomment']);
                $row['logcontext'] = $this->contextToString($row['logcontext']);

            }

            //lecho($logs);
            return ['status' => 'success', 'logs' => $logs, 'geojson' => $geojson, 'route' => $route];
        }
        return ['status' => 'error', 'message' => 'Map format fail'];
    }

    public function contextToString($logcontext) {
        if (empty($logcontext)) {
            return '';
        }
        
        $context = json_decode($logcontext, true);
        
        if (!$context) {
            return '';
        }
        
        $city = $context['city'] ?? '';
        $country = $context['country'] ?? '';
        $temp = $context['temp'] ?? '';
        $icon = $context['icon'] ?? '';
        
        $iconUrl = "https://openweathermap.org/img/wn/{$icon}.png";
        
        return "{$city} {$temp}°C <img src=\"{$iconUrl}\"/>";
    }

    public function sendgeolocation(){
        lecho("sendgeolocation");
    
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        // lecho($latitude,  $longitude);
    
        if ($this->newlog($this->userid, $this->routeid, $latitude, $longitude)){
            return $this->getData($this->routeid);
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

    public function newlog($userid, $routeid, $latitude, $longitude, $message=null, $photo = 0, $timestamp = null, $telegramid = 0){    
        lecho("NewLog UserId: $userid RouteId: $routeid Photo: $photo");

        // Vérifier si c'est un doublon Telegram AVANT l'insertion
        if ($telegramid > 0) {
            $checkQuery = "SELECT COUNT(*) as count FROM rlogs WHERE logroute = ? and logtelegramid = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bind_param("ii", $routeid, $telegramid);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                lecho("Telegram message already exists: $telegramid");
                $this->error = "Duplicate Telegram message";
                return false;
            }
        }
        
        // Vérifier les coordonnées invalides
        if ($latitude == 0 && $longitude == 0) {
            lecho("Coordonnées invalides (0,0) - Log ignoré");
            $this->error = "Invalid coordinates (0,0)";
            return false;
        }
        
        // Vérifier les coordonnées vides ou non numériques
        if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
            lecho("Coordonnées vides ou non numériques - Log ignoré");
            $this->error = "Invalid coordinates";
            return false;
        }
        
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
    
        // Conversion en date au format ISO 8601 (YYYY-MM-DD HH:MM:SS)
        if($timestamp){
            lecho("Timestamp: $timestamp");
            $insertQuery = "INSERT IGNORE INTO rlogs (logroute, loguser, logtelegramid, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto, logtime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiiddiiisii", $routeid, $userid, $telegramid, $latitude, $longitude, $p, $km, $dev, $message, $photo, $timestamp);
        }else{
            lecho("No timestamp");
            $insertQuery = "INSERT IGNORE INTO rlogs (logroute, loguser, logtelegramid, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiiddiiisi", $routeid, $userid, $telegramid, $latitude, $longitude, $p, $km, $dev, $message, $photo);
        }
        
        if ($insertStmt->execute()) {
            if($insertStmt->affected_rows > 0){
                lecho("Insert ok");
                return true;
            }else{
                $this->error = "Duplicate log entry";
                if ($photo > 0 && $timestamp) {
                    $this->error .= " : Possible duplicate photo with same timestamp";
                }
                lecho($this->error);
                return false;
            }
        }else{
            lecho("Insert Bug - Error: " . $insertStmt->errno . " - " . $insertStmt->error);
            lecho("SQLSTATE: " . $insertStmt->sqlstate);
            $this->error = "Database insert failed: " . $insertStmt->error;
        }
        return false;
    }

    public function lastlog($userid, $routeid, $hours=12) {
        lecho("LastLog - User: $userid, Route: $routeid");
        
        $query = "SELECT * FROM rlogs 
            WHERE loguser = ? AND logroute = ? 
            AND logtime >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY logtime DESC 
            LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iii", $userid, $routeid, $hours);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        
        return false;
    }


    public function thislog($logid) {
        
        $query = "SELECT * FROM rlogs WHERE logid = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $logid);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        
        return false;
    }

    public function updatelogtime($logid) {
        lecho("Update logupdate $logid");
        $updateQuery = "UPDATE rlogs SET logupdate = NOW() WHERE logid = ?";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bind_param("i", $logid);
        
        return $updateStmt->execute();
    }

    // public function userMarkers() {
    //     lecho("userMarkersN");
    
    //     $loguser = $_POST['loguser'] ?? '';
    //     return $this->get_userMarkers($loguser, $this->routeid);

    // }

    // public function get_userMarkers($userid, $routeid) {
    //     $route = $this->route->get_route_by_id($routeid);
    
    //     $query = "SELECT * FROM rlogs l INNER JOIN users u ON l.loguser = u.userid WHERE loguser = ? AND logroute = ? ORDER BY l.logtime DESC";
    //     $stmt = $this->db->prepare($query);
    //     $stmt->bind_param("ii", $userid, $routeid);
    
    //     return $this->format_map_data($stmt, $route);
    // }

    public function logphoto(){
        lecho("logphoto user: $this->userid route: $this->routeid");
        
        $latitude = Tools::getRequestData('latitude');
        $longitude = Tools::getRequestData('longitude');
        $timestamp = intval(Tools::getRequestData('timestamp'));
        $photofile = Tools::getRequestData('photofile');

        if ($timestamp <= 0) {
            return ['status' => 'error', 'message' => "Invalid timestamp $timestamp"];
        }

        $thirtyDaysAgo = time() - (7 * 24 * 60 * 60);
        if ($timestamp < $thirtyDaysAgo) {
            return ['status' => 'error', 'message' => 'Timestamp too old (more than one week.)'];
        }

        if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
            lecho("GPS error (no latitude/longitude $latitude/$longitude)");
            return ['status' => 'error', 'message' => "GPS error (no latitude/longitude $latitude/$longitude)"];
        }

        if(empty($photofile)) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
        $photo64 = Tools::photo64decode($photofile);
        if($photo64['status'] != 'success'){
            return ['status' => 'error', 'message' => $photo64['message']];
        }
        $photosource = $photo64['file'];
        
        $target = $this->fileManager->user_route_photo($this->userid, $this->routeid, $timestamp, 1);
        if(!$target){
            return ['status' => 'error', 'message' => 'No target - ' . $this->fileManager->getError()];
        }

        if(!Tools::resizeImage($photosource, $target, IMAGE_DEF)){
            lecho("resizeFail");
            return ['status' => 'error', 'message' => 'Resize fail'];
        }

        if($this->newlog($this->userid, $this->routeid, $latitude, $longitude, null, 1, $timestamp)){
            lecho("New photo ok");
            return $this->getData($this->routeid);
        }else{
            lecho("Newlog error");
            //Delete file
            unlink($target);
            return ['status' => 'error', 'message' => "Newlog error - $this->error"];
        }
   
        return ['status' => 'error', 'message' => 'Strange bug'];
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
            return $this->getData($this->routeid);
        }
        return ['status' => 'error', 'message' => 'Comment error'];
    }

    public function rotateImage(){
        lecho("Rotate Image");
        $logid = $_POST['logid'] ?? '';

        if (empty($logid)) {
            return ['status' => 'error', 'message' => 'Missing logid'];
        }
        
        $userLog = $this->thislog($logid);
        
        if (!$userLog || $userLog['logphoto'] <= 0) {
            return ['status' => 'error', 'message' => 'No image found or not authorized'];
        }
        
        // Récupérer le timestamp depuis logtime
        $timestamp = strtotime($userLog['logtime']);
        
        // Récupérer le chemin de l'image avec FileManager
        $imagePath = $this->fileManager->user_route_photo($this->userid, $this->routeid, $timestamp, 1);
        
        if (!$imagePath || !file_exists($imagePath)) {
            return ['status' => 'error', 'message' => 'Image file not found'];
        }
        
        if (Tools::rotateImageFile($imagePath)){
            $this->updatelogtime($logid);
            lecho("Image rotated successfully");
            return $this->getData($this->routeid);
        }
        return ['status' => 'error', 'message' => 'Rotate impossible'];

    }


    public function deleteLog() {
        lecho("DeleteLog");

        $logid = $_POST['logid'] ?? '';    

        if($this->userid && $logid){
        
            $deleteQuery = "DELETE FROM rlogs WHERE logid = ? AND loguser = ?";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->bind_param("ii", $logid, $this->userid);
        
            if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
                return $this->getData($this->routeid);
            }
        
        }

        return ['status' => 'error', 'message' => 'Log not deleted'];

    }

}