<?php
https://geo.zefal.com/backend.php

define("DEBUG",true);
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/logs/error_php.log');

set_time_limit(60);
ini_set('display_errors', 1);
require_once(__DIR__ . '/admin/secret.php');
include (__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/admin/filemanager.php');
require_once(__DIR__ . '/admin/functions.php');
require_once(__DIR__ . '/admin/mylogs.php');
require_once(__DIR__ . '/admin/tools_gpx.php');
require_once(__DIR__ . '/admin/gpxmanager.php');

require_once(__DIR__ . '/admin/gpxmanager.php');


use Firebase\JWT\JWT;
use Firebase\JWT\Key;

init();

$view = $_POST['view'] ?? '';
$page = $_POST['page'] ?? '';
$userid = $_POST['userid'] ?? '';
$chatobj = $_POST['chatobj'] ?? '';

lecho("Backend:", $view);

if($view == "login") {
    $data = get_login();
}elseif($view == "createuser") {
    $data = create_user();
}elseif($view == "updateuser") {
    $data = updateuser();
}elseif($view == "userphoto") {
    $data = userphoto();
}elseif($view == "route") {
    $data = new_route();
}elseif($view == "getroutes") {
//    $data = getroutes();
}elseif($view == "updateroute"){
    $data = updateroute();
}elseif($view == "gpxupload"){
    $data = gpxupload();
}elseif($view == "routephoto"){
    $data = routephoto();
}elseif($view == "routeconnect"){
    $data = routeconnect();
}elseif($view == "routeAction"){
    $data = routeAction();
}elseif($view == "sendgeolocation"){
    $data = sendgeolocation();    
}elseif($view == "loadMapData") {
    $data = loadMapData();
}elseif($view == "userMarkers") {
    $data = userMarkers();
}elseif($view == "logphoto") {
    $data = logphoto();
}elseif($view == "userAction") {
    $data = userAction();
} else {
    $data = [];
}


header('Content-Type: application/json');
echo json_encode($data);
lexit();

function get_login(){
    global $mysqli;

    lecho("get_login");

    $email = $_POST['email'] ?? '';
    if(!empty($email)){
        $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
    }else{
        $isEmailValid = false;
    }
    if(!$isEmailValid){
         return ['status' => 'error', 'message' => 'Invalid email'];
    }

    $password = $_POST['password'] ?? '';
    if (empty($password)) {
        return ['status' => 'error', 'message' => 'Invalid password'];
    }

    if ($user = get_user($email)) {
        if (password_verify($password, $user['userpsw'])) {

            if (!testToken($user['userid'])){
                $user['usertoken'] = saveToken($user['userid']);
            }
            unset($user['userpsw']);

            return ['status' => 'success', 'userdata' => $user];
        } else {
            return ['status' => 'error', 'message' => 'Wrong password'];
        }
    }

    return ['status' => 'not_found', 'message' => $email.' is not a user, do you want to sign in?', 'email' => $email, 'password' => $password];

}

function create_user(){
    global $mysqli;

    lecho("CreateUser");

    $result = get_login();
    if($result['status'] != 'not_found') return ['status' => "fail", 'message' => "User allready there…"];

    list($username, $domain) = explode('@', $result['email']);
    $userinitials = initial($username);
    $usercolor = getDarkColorCode(rand(0,10000));

    $hashedPassword = password_hash($result['password'], PASSWORD_DEFAULT);
    $isPasswordValid = password_verify($result['password'], $hashedPassword);
    if(!$isPasswordValid){
        return ['status' => "fail", 'message' => "Bad password"];
    }

    $userroute = TESTROUTE; // Connected to testroute by default

    $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userpsw, userroute) VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param("sssssi", $username, $userinitials, $usercolor, $result['email'], $hashedPassword, $userroute);
    
    if ($insertStmt->execute()) {

        $userid = $mysqli->insert_id;

        connect($userid,$userroute,0);

        // Retourne les données du nouvel utilisateur
        $token = saveToken($userid);
        if ($token){
            $user = [
                'userid' => $userid,
                'useremail' => $result['email'],
                'username' => $username,
                'userinitials' => $userinitials,
                'usercolor' => $usercolor,
                'userimg' => '',
                'usertoken' => $token,
                'userroute' => $userroute,
                'routeid' => $userroute
            ];
            return [
                'status' => "success",
                'userdata' => $user,
                'route' => null
            ];
        } else {
            return [
                'status' => "fail",
                'message' => "Bad token"
            ];    
        }
    } else {
        // Erreur lors de la création de l'utilisateur
        delete_user($mysqli->insert_id);
        return [
            'status' => "fail",
            'message' => "Can't add user"
        ];
    }

}

function userMarkers() {
    global $mysqli;

    lecho("userMarkers");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $loguser = $_POST['loguser'] ?? '';
    $routeid = $_POST['routeid'] ?? '';
    $route = get_route_by_id($routeid);

    $query = "SELECT * FROM rlogs l INNER JOIN users u ON l.loguser = u.userid WHERE loguser = ? AND logroute = ?";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $loguser, $routeid);

    return format_map_data($stmt, $route);
}

function loadMapData(){
    global $mysqli;

    $routestatus = $_POST['routestatus'] ?? '';
    $routeid = $_POST['routeid'] ?? '';
    $userroute = $_POST['userroute'] ?? '';

    if($routestatus >1 ){
        //Private route
        $userid = $_POST['userid'] ?? '';
        if (!testToken($userid)){
            return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
        }
        if ($routeid != $userroute){
            return ['status' => 'error', 'message' => "User $userid (on route:$userroute) not connected to this route $routeid (status:$routestatus)"];
        }
    }

    return get_map_data($routeid);

}

function get_map_data($routeid){
    global $mysqli;

    $route = get_route_by_id($routeid);

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

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $routeid, $routeid);
    }

    return format_map_data($stmt, $route); 
}

function format_map_data($stmt, $route){
    lecho("format_map_data");
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    //lecho($logs);

    $filemanager = new FileManager();
    $geojson = $filemanager->route_geojson_web($route);

    foreach ($logs as &$row) {
        //lecho("Routetime", $row["logtime"]);
        $row['logkm_km'] = meters_to_distance2($row["logkm"], $route, false);
        $row['username_formatted'] = $row['username'] . "<br/>" . MyDateFormat2($row['logtime'],$route) . "<br/>" . meters_to_distance2($row["logkm"], $route);
        $row['photopath'] = $filemanager->user_photo_web($row);
        $row['photolog'] = $filemanager->user_route_photo_web($row);
        //lecho($row['photopath']);
    }

    //lecho($logs);
    return ['status' => 'success', 'logs' => $logs, 'geojson' => $geojson];    

}












function random_geoloc(){
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

function sendgeolocation(){

    lecho("sendgeolocation");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $routeid = $_POST['routeid'] ?? '';
    if($routeid<1){
        return ['status' => 'error', 'message' => 'Route problem'];
    }

    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    return newlog($userid, $routeid, $latitude, $longitude);

}

function newlog($userid, $routeid, $latitude, $longitude, $message=null, $photo = null, $timestamp = null){
    global $mysqli;

    lecho("NewLog");

    $nearest = new gpxnearest($routeid);
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
        $insertStmt = $mysqli->prepare($insertQuery);
        $insertStmt->bind_param("iiddiiisii", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo, $timestamp);
    }else{
        $insertQuery = "INSERT  IGNORE INTO rlogs (logroute, loguser, loglatitude, loglongitude, loggpxpoint, logkm, logdev, logcomment, logphoto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $mysqli->prepare($insertQuery);
        $insertStmt->bind_param("iiddiiisi", $routeid, $userid, $latitude, $longitude, $p, $km, $dev, $message, $photo);
    }
    
    if ($insertStmt->execute()) {
        return get_map_data($routeid);
    }else{
        return ['status' => 'error', 'message' => 'SQL error'];    
    }

    return ['status' => 'error', 'message' => 'Log fail'];    
}

function routephoto(){

    lecho("Route Photo Upload");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    if (!isset($_FILES['photofile']) || $_FILES['photofile']['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'message' => 'Bad photo file'];
    }

    $routeid = $_POST['routeid'] ?? '';
    if(empty($routeid)){
        return ['status' => 'error', 'message' => 'Empty route'];
    }

    $filemanager = new FileManager();
    $target = $filemanager->route_photo($routeid);

    if($target){
        if(resizeImage($_FILES['photofile']['tmp_name'], $target, 500)){
            set_route_photo($routeid,1);
            return ['status' => 'success', 'message' => 'File uploaded successfully'];
        }else{
            set_route_photo($routeid,0);
        }
    }

    return ['status' => 'error', 'message' => 'Upload fail'];

}


function routeGPX($routeid,$value){
    global $mysqli;

    $stmt = $mysqli->prepare("UPDATE routes SET gpx = ? WHERE routeid = ?");
    $stmt->bind_param("ii", $value, $routeid);
    if ($stmt->execute())
        return true;
    else
        return false;
}

function set_route_photo($routeid,$value){
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE routes SET routephoto = ? WHERE routeid = ?");
    $stmt->bind_param("ii", $value, $routeid);
    if ($stmt->execute())
        return true;
    else
        return false;
}



?>