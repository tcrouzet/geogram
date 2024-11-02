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



function updateuser(){
    global $mysqli;

    $userid = $_POST['userid'] ?? '';
    lecho("updateuser", $userid);
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $username = $_POST['username'] ?? '';
    $useremail = $_POST['useremail'] ?? '';

    lecho($useremail);

    if ($user = get_user($userid)) {

        $user['username'] = $username;
        $user['useremail'] = $useremail;
        unset($user['userpsw']);

        $stmt = $mysqli->prepare("UPDATE users SET username = ? WHERE userid = ?");
        $stmt->bind_param("si", $username, $userid);
        if ($stmt->execute())
           return ['status' => 'success', 'user' => $user];
        else
            return ['status' => 'error', 'message' => 'Update fail'];

    }

    return ['status' => 'error', 'message' => 'Unknown user'];

}

function userphoto(){

    lecho("userphoto");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    if (!isset($_FILES['photofile']) || $_FILES['photofile']['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'message' => 'Bad photo file'];
    }

    $filemanager = new FileManager();
    $target = $filemanager->user_photo($userid);

    if($target){
        if(resizeImage($_FILES['photofile']['tmp_name'], $target, 500)){
            set_user_photo($userid,1);
            return ['status' => 'success', 'message' => 'File uploaded successfully'];
        }else{
            set_user_photo($userid,0);
        }
    }

    return ['status' => 'error', 'message' => 'Upload fail'];

}

function new_route(){
    global $mysqli;

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $routename = $_POST['routename'] ?? '';
    if(empty($routename)){
        return ['status' => 'error', 'message' => 'Empty routename'];
    }

    $slug = slugify($routename);
    $initials = initial($routename);

    $query = "SELECT * FROM routes WHERE routename=? OR routeslug=?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $routename, $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Route already exists'];
    } else {
        $insertQuery = "INSERT INTO routes (routename, routeinitials, routeslug, routeuserid) VALUES (?, ?, ?, ?)";
        $insertStmt = $mysqli->prepare($insertQuery);
        $insertStmt->bind_param("sssi", $routename, $initials, $slug, $userid);
        
        if ($insertStmt->execute()) {
            // Retourne les données du nouvel utilisateur
            $routeid = $mysqli->insert_id;
            updateUserRoute($userid,$routeid);
            $routeviewerlink = generateInvitation($routeid, 1);
            $routepublisherlink = generateInvitation($routeid, 2);
            updateRouteInvitation($routeid, $routeviewerlink, $routepublisherlink);
        
            connect($userid,$routeid);
            $route = [
                'routeid' => $routeid,
                'routename' => $routename,
                'routeslug' => $slug,
                'routeuserid' => $userid,
            ];
            return [
                'status' => "success",
                'routedata' => $route
            ];
        } else {
            return [
                'status' => "fail",
                'message' => "Can't add route"
            ];
        }
    
    }

}

function updateroute(){
    global $mysqli;

    $userid = $_POST['userid'] ?? '';
    lecho("updateRoute", $userid);
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $routeid = $_POST['routeid'] ?? '';
    if(empty($routeid)){
        return ['status' => 'error', 'message' => 'Empty routename'];
    }

    $routename = $_POST['routename'] ?? '';
    if(empty($routename)){
        return ['status' => 'error', 'message' => 'Empty routename'];
    }

    $routestatus = $_POST['routestatus'] ?? '';
    $routerem = $_POST['routerem'] ?? '';

    $query = "SELECT * FROM routes WHERE routeid=?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $routeid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $stmt = $mysqli->prepare("UPDATE routes SET routename = ?, routerem = ?, routestatus = ? WHERE routeid = ?");
        $stmt->bind_param("sssi", $routename, $routerem, $routestatus, $routeid);
        if ($stmt->execute())
           return ['status' => 'success', 'message' => 'Update fail'];
        else
            return ['status' => 'error', 'message' => 'Update fail'];

    } else {
        return ['status' => 'error', 'message' => 'Unknown route'];    
    }

}

function routeconnect(){
    global $mysqli;

    lecho("routeconnect");

    $userid = $_POST['userid'] ?? '';
    lecho("updateuser", $userid);
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $routeid = intval($_POST['routeid'] ?? '');

    //lecho($_POST);

    if ($user = get_user($userid)) {

        $stmt = $mysqli->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
        $stmt->bind_param("ii", $routeid, $userid);
        if ($stmt->execute()){
            $user = get_user($userid);
            unset($user['userpsw']);
            return ['status' => 'success', 'user' => $user ];
        }else
            return ['status' => 'error', 'message' => 'Update fail'];

    }

    return ['status' => 'error', 'message' => 'Unknown user'];
}

function userAction(){
    lecho("userAction");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $action = $_POST['action'] ?? '';

    // lecho($_POST);
    // lecho($action);

    if($action == "purgeuser"){
        $message = purgeuser($userid);
    }else{
        return ['status' => 'error', 'message' => "Unknown action: $action"];        
    }

    if($message)
        return ['status' => 'success', 'message' => "Action $action done"];
    else
        return ['status' => 'error', 'message' => "Action $action fail"];
}

function purgeuser($userid){
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM rlogs WHERE loguser=?");
    $stmt->bind_param("i", $userid);

    if ($stmt->execute()){
        $filemanager = New FileManager();
        $filemanager->purgeUserData($userid);
        return true;
    }else{
        return false;
    }

}


function gpxupload(){

    lecho("gpxUpload");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    if (!isset($_FILES['gpxfile']) || $_FILES['gpxfile']['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'message' => 'Bad gpx file'];
    }

    $routeid = $_POST['routeid'] ?? '';
    if(empty($routeid)){
        return ['status' => 'error', 'message' => 'Empty routename'];
    }

    $filemanager = new FileManager();
    $source = $filemanager->gpx_source($routeid);

    if($source){
        if (move_uploaded_file($_FILES['gpxfile']['tmp_name'], $source)) {

            $nimigpx = gpx_minimise($source);
            $geojson = gpx_geojson($nimigpx);
            if($geojson){
                if ($gpxdata = new_gpx($routeid)){
                    routeGPX($routeid,1);
                    return ['status' => 'success', 'gpx' => $gpxdata];
                }
            }
        }
    }

    routeGPX($routeid,0);
    return ['status' => 'error', 'message' => 'Upload fail'];

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

function photo64decode($photofile){

    // Extraction du type MIME (si présent)
    $matches = [];
    preg_match('/data:image\/(\w+);base64/', $photofile, $matches);
    $imageType = $matches[1] ?? 'jpeg'; // Par défaut, on considère que c'est un JPEG

    // Suppression du préfixe
    $base64_string = str_replace('data:image/' . $imageType . ';base64,', '', $photofile);

    // Décodage en base64
    $data = base64_decode($base64_string);

    // Enregistrement du fichier
    $tmpFilename = tempnam(sys_get_temp_dir(), 'photo_');

    if (file_put_contents($tmpFilename, $data) === false) {
        return false;
    }

    return $tmpFilename;

}

function logphoto(){
    lecho("Route Photo Upload2");
    //lecho($_POST);

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

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
    $photosource = photo64decode($photofile);
    
    $filemanager = new FileManager();
    $target = $filemanager->user_route_photo($userid, $routeid, $timestamp);
    lecho($target);

    if($target){
        if(resizeImage($photosource, $target, 1200)){
            return newlog($userid, $routeid, $latitude, $longitude, null, $timestamp, $timestamp);
        }else{
            lecho("resizeFail");
        }
    }

    return ['status' => 'error', 'message' => 'Upload fail'];

}



function generateToken($userid) {
    $payload = [
        'iss' => GEO_DOMAIN,           // Émetteur
        'aud' => GEO_DOMAIN,           // Audience
        'iat' => time(),               // Temps d'émission
        'exp' => time() + 86400*90,    // Expiration (90 jours)
        'sub' => $userid               // Sujet (ID utilisateur)
    ];

    $secretKey = JWT_SECRET;
    return JWT::encode($payload, $secretKey, 'HS256');
}

function validateToken($jwt) {
    try {
        //lecho("validate",$jwt);
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        lecho($e);
        return false;
    }
}

function saveToken($userid){
    global $mysqli;

    $token = generateToken($userid);

    $stmt = $mysqli->prepare("UPDATE users SET usertoken = ? WHERE userid = ?");
    $stmt->bind_param("si", $token, $userid);
    if ($stmt->execute())
        return $token;
    else
        return false;
}

function testToken($userid){
    global $mysqli;

    //lecho("testToken", $userid);
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    if(empty($authHeader)) return false;
    list($jwt) = sscanf($authHeader, 'Bearer %s');
    
    $stmt = $mysqli->prepare("SELECT usertoken FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $decoded = validateToken($jwt);
        if ($jwt && $jwt === $row['usertoken'] && $decoded) {
            if($decoded->exp - time() > 300) {
                return true;
            }
        }
    }
    return false;
}

function delete_user($userid){
    global $mysqli;
    $query = "DELETE FROM users WHERE userid=$userid;";
    $mysqli->query($query);
    return $mysqli->affected_rows;
}

function updateUserRoute($userid,$routeid){
    global $mysqli;

    $stmt = $mysqli->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
    $stmt->bind_param("si", $routeid, $userid);
    if ($stmt->execute())
        return true;
    else
        return false;
}

function set_user_photo($userid,$value){
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE users SET userphoto = ? WHERE userid = ?");
    $stmt->bind_param("ii", $value, $userid);
    if ($stmt->execute())
        return true;
    else
        return false;
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

function slugify($text) {
    // Translitération des caractères spéciaux en équivalents ASCII
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Suppression des caractères non alphanumériques et des espaces
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    // Conversion en minuscules
    $text = strtolower($text);
    // Suppression des tirets en début et fin de chaîne
    $text = trim($text, '-');

    return $text;
}

function connect($userid,$routeid,$status=2){
    global $mysqli;
    lecho("connect",$userid,$routeid);
    $insertQuery = "INSERT INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param("iii", $routeid, $userid, $status);
    return $insertStmt->execute();
}

function get_route_by_id($routeid){
    global $mysqli;

    $query="SELECT * FROM `routes` WHERE routeid = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $routeid);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result){
        return $result->fetch_assoc();
    }else{
        return false;
    }
}

function updateRouteInvitation($routeid,$viewer,$publisher){
    global $mysqli;

    $stmt = $mysqli->prepare("UPDATE routes SET routepublisherlink = ?, routeviewerlink = ? WHERE routeid = ?");
    $stmt->bind_param("ssi", $publisher, $viewer, $routeid);
    if ($stmt->execute())
        return true;
    else
        return false;
}

function generateInvitation($routeid, $status) {
    $randomString = bin2hex(random_bytes(8));
    $data = $routeid . '|' . $status . '|' . $randomString;

    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', JWT_SECRET, 0, $iv);
    $token = base64_encode($iv . $encryptedData);

    return $token;
}

function decodeInvitation($token) {
    $decoded = base64_decode($token);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($decoded, 0, $ivLength);
    $encryptedData = substr($decoded, $ivLength);

    $data = openssl_decrypt($encryptedData, 'aes-256-cbc', JWT_SECRET, 0, $iv);
    if ($data === false) {
        return false;
    }

    list($routeid, $status, $randomString) = explode('|', $data);
    return ['routeid' => $routeid, 'status' => $status];
}

function resizeImage($sourcefile, $targetfile, $maxSize) {

    $imageInfo = getimagesize($sourcefile);
    if ($imageInfo === false) {
        return false;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];

    // Calculer le ratio de redimensionnement
    $ratio = min($maxSize / $width, $maxSize / $height);
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;

    // Créer une nouvelle image avec la taille calculée
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    $white = imagecolorallocate($newImage, 255, 255, 255);
    imagefill($newImage, 0, 0, $white);

    // Charger l'image source selon son type
    $sourceImage = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcefile);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcefile);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcefile);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcefile);
            break;
        default:
            return false;
    }

    if (!$sourceImage) {
        return false;
    }

    // Charger l'image d'origine
    $sourceImage = imagecreatefromjpeg($sourcefile);

    // Redimensionner l'image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Sauvegarder
    // imagejpeg($newImage, $targetfile, 60);
    imagewebp($newImage, $targetfile, 60);

    // Libérer la mémoire
    imagedestroy($newImage);
    imagedestroy($sourceImage);

    return true;
}

function get_user($param) {
    global $mysqli;

    $isEmail = strpos($param, '@') !== false;

    if ($isEmail) {
        $query = "SELECT * FROM users u LEFT JOIN routes r ON u.userroute = r.routeid WHERE u.useremail = ?";
    } else {
        $query = "SELECT * FROM users u LEFT JOIN routes r ON u.userroute = r.routeid WHERE u.userid = ?";
    }

    $stmt = $mysqli->prepare($query);

    if ($isEmail) {
        $stmt->bind_param("s", $param);
    } else {
        $stmt->bind_param("i", $param);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $filemanager = New FileManager();
        $user['photopath'] = $filemanager->user_photo_web($user);
        return $user;
    } else {
        return false;
    }
}

?>