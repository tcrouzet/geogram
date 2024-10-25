<?php
https://geo.zefal.com/backend.php?page=userlogs&chatid=-1001831273860&userid=1934640167

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
}elseif($view == "route") {
    $data = new_route();
}elseif($view == "getroutes") {
    $data = get_routes();
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

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $routeid = $_POST['routeid'] ?? '';
    lecho($routeid);
    $route = get_route_by_id($routeid);
    lecho($route);

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

    $gpxfile_flag = true;
    $gpxfile = "";

    foreach ($logs as &$row) {
        $row['username_formatted'] = fName($row["username"]) . "<br>" . MyDateFormatN($route, $row["timestamp"]) . "<br>" . meters_to_distance($row["km"], $route);
        $row['usercolor'] = getDarkColorCode($row["userid"]);
        $row['userinitials'] = initial($row["username"]);

        if ($fileManager->avatarExists($routeid, $row["userid"])) {
            $row['userimg'] = $fileManager->avatarWeb($route, $row["userid"], true);
        } else {
            $row['userimg'] = false;
        }

        if ($gpxfile_flag){
            $gpxfile = $fileManager->geojsonWeb($route);
            $gpxfile_flag = false;
        }
        $row['gpxfile'] = $gpxfile;

    }

    //lecho($logs);
    return $logs;

}

function get_login(){
    global $mysqli;

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

    $query = "SELECT * FROM users u LEFT JOIN routes r ON u.userroute = r.routeid WHERE u.useremail = ?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['userpsw'])) {

            if (!testToken($user['userid'])){
                $user['auth_token'] = saveToken($user['userid']);
            }
            unset($user['userpsw']);

            return ['status' => 'success', 'userdata' => $user];
        } else {
            return ['status' => 'error', 'message' => 'Wrong password'];
        }
    } else {
        return ['status' => 'not_found', 'message' => $email.' not found, do you want to sign in?', 'email' => $email, 'password' => $password];
    }

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

    $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userpsw) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param("sssss", $username, $userinitials, $usercolor, $result['email'], $hashedPassword);
    
    if ($insertStmt->execute()) {
        // Retourne les données du nouvel utilisateur
        $token = saveToken($mysqli->insert_id);
        if ($token){
            $user = [
                'userid' => $mysqli->insert_id,
                'useremail' => $result['email'],
                'username' => $username,
                'userinitials' => $userinitials,
                'usercolor' => $usercolor,
                'userimg' => '',
                'auth_token' => $token
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
    $formType = $_POST['formType'] ?? '';

    $slug = slugify($routename);

    $query = "SELECT * FROM routes WHERE routename=? OR routeslug=?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $routename, $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        if ($formType=="newroute"){
            return ['status' => 'error', 'message' => 'Route already exists'];
        }
        if ($formType=="routeupdate"){
            $route = $result->fetch_assoc();
        }
    } else {
        $insertQuery = "INSERT INTO routes (routename, routeslug, routeuserid) VALUES (?, ?, ?)";
        $insertStmt = $mysqli->prepare($insertQuery);
        $insertStmt->bind_param("ssi", $routename, $slug, $userid);
        
        if ($insertStmt->execute()) {
            // Retourne les données du nouvel utilisateur
            $routeid = $mysqli->insert_id;
            updateRoute($userid,$routeid);
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

function get_routes(){
    global $mysqli;

    $userid = $_POST['userid'] ?? '';
    if (!testToken($userid)){
        return ['status' => 'error', 'message' => 'Bad token, please reconnect'];
    }

    $query = "SELECT r.* FROM connectors c INNER JOIN routes r ON c.conrouteid = r.routeid WHERE c.conuserid = ?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $routes = $result->fetch_all(MYSQLI_ASSOC);
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
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
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

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    list($jwt) = sscanf($authHeader, 'Bearer %s');
    
    $stmt = $mysqli->prepare("SELECT auth_token FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $decoded = validateToken($jwt);
    if ($jwt && $jwt === $row['auth_token'] && $decoded) {
        if($decoded->exp - time() < 300) {
            return false;
        }
        return true;
    } else {
        return false;
    }
    
}

function delete_user($userid){
    global $mysqli;
    $query = "DELETE FROM users WHERE userid=$userid;";
    $mysqli->query($query);
    return $mysqli->affected_rows;
}

function updateRoute($userid,$routeid){
    global $mysqli;

    $stmt = $mysqli->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
    $stmt->bind_param("si", $routeid, $userid);
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

function connect($userid,$routeid){
    global $mysqli;
    lecho("connect",$userid,$routeid);
    $insertQuery = "INSERT INTO connectors (conrouteid, conuserid) VALUES (?, ?)";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param("ii", $routeid, $userid);
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

?>