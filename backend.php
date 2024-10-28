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
    $data = getroutes();
}elseif($view == "updateroute"){
    $data = updateroute();
}elseif($view == "gpxupload"){
    $data = gpxupload();
}elseif($view == "routephoto"){
    $data = routephoto();
}elseif($view == "routeconnect"){
    $data = routeconnect();
}elseif($view == "map") {
    $data = get_chat_logs();
}elseif($page=="userlogs" && isset($userid) && $chatobj){
    $data = get_user_logs($userid, $chatobj);
} else {
    $data = [];
}


header('Content-Type: application/json');
echo json_encode($data);
lexit();


function get_user_logs($userid, $chatid) {
    global $mysqli;

    $query="SELECT * FROM logs WHERE userid=? AND chatid=? ORDER BY timestamp ASC;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $userid, $chatid);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);

}

function get_chat_logs(){
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

    $route = get_route_by_id($routeid);

    $fileManager = new FileManager();

    if(!empty($route["link"]) && $route["stop"]==0){

        $start = time()-86400*7;
        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? AND timestamp> ? GROUP BY userid) ORDER BY km DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("iii", $route['chatid'], $route['chatid'], $start);

    }elseif ($route["start"]>0){

        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY km DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $route['chatid'], $route['chatid']);

    }else{

        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY timestamp DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $routeid, $routeid);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);

    $geojson = $fileManager->route_geojson_web($route);

    foreach ($logs as &$row) {
        $row['username_formatted'] = fName($row["username"]) . "<br>" . MyDateFormatN($route, $row["timestamp"]) . "<br>" . meters_to_distance($row["km"], $route);
        $row['usercolor'] = getDarkColorCode($row["userid"]);
        $row['userinitials'] = initial($row["username"]);

        if ($fileManager->avatarExists($routeid, $row["userid"])) {
            $row['userimg'] = $fileManager->avatarWeb($route, $row["userid"], true);
        } else {
            $row['userimg'] = false;
        }

    }

    //lecho($logs);
    return ['status' => 'success', 'logs' => $logs, 'geojson' => $geojson];
}

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
                $user['auth_token'] = saveToken($user['userid']);
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
                'auth_token' => $token,
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

function updateuser(){
    global $mysqli;

    $userid = $_POST['userid'] ?? '';
    lecho("updateuser", $userid);
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $username = $_POST['username'] ?? '';

    if ($user = get_user($userid)) {

        $user['username'] = $username;
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
            $user['userroute'] = $routeid;
            unset($user['userpsw']);
            //lecho($user);
           return ['status' => 'success', 'user' => $user ];
        }else
            return ['status' => 'error', 'message' => 'Update fail'];

    }

    return ['status' => 'error', 'message' => 'Unknown user'];
}

function gpxupload(){
    global $mysqli;

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
                //$minimise = gpx2base($nimigpx);
                routeGPX($routeid,1);
            }else{
                routeGPX($routeid,0);
            }
            return ['status' => 'success', 'message' => 'File uploaded successfully'];
        }
    }

    return ['status' => 'error', 'message' => 'Upload fail'];

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

function getroutes(){
    global $mysqli;

    lecho("getroutes");

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    //lecho("userid",$userid);

    $query = "SELECT * FROM connectors c INNER JOIN routes r ON c.conrouteid = r.routeid WHERE c.conuserid = ?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $routes = $result->fetch_all(MYSQLI_ASSOC);

    //lecho($routes);

    $filemanager = new FileManager();

    foreach ($routes as &$route) {
        $route['photopath'] = $filemanager->route_photo_web($route);
    }

    return $routes;

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

    $stmt = $mysqli->prepare("UPDATE users SET auth_token = ? WHERE userid = ?");
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
    
    $stmt = $mysqli->prepare("SELECT auth_token FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $decoded = validateToken($jwt);
        //lecho($row['auth_token']);
        if ($jwt && $jwt === $row['auth_token'] && $decoded) {
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
    list($width, $height) = getimagesize($sourcefile);

    // Calculer le ratio de redimensionnement
    $ratio = min($maxSize / $width, $maxSize / $height);

    // Calculer la taille de la nouvelle image
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;

    // Créer une nouvelle image avec la taille calculée
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Charger l'image d'origine
    $sourceImage = imagecreatefromjpeg($sourcefile);

    // Redimensionner l'image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Sauvegarder l'image redimensionnée
    imagejpeg($newImage, $targetfile, 60);

    // Libérer la mémoire
    imagedestroy($newImage);

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