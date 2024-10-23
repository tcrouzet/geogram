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
}elseif($page=="userlogs" && isset($userid) && $chatobj){
    $data = get_user_logs($userid, $chatobj);
} elseif($chatobj) {
    $data = get_chat_logs($chatobj);
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

function get_chat_logs($chatobj){
    global $mysqli;

    $chatobj = json_decode($chatobj, true);

    $fileManager = new FileManager();

    if(!empty($chatobj["link"]) && $chatobj["stop"]==0){

        $start = time()-86400*7;
        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? AND timestamp> ? GROUP BY userid) ORDER BY km DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("iii", $chatobj['chatid'], $chatobj['chatid'], $start);

    }elseif ($chatobj["start"]>0){

        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY km DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $chatobj['chatid'], $chatobj['chatid']);

    }else{

        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY timestamp DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $chatobj['chatid'], $chatobj['chatid']);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);

    $gpxfile_flag = true;
    $gpxfile = "";

    foreach ($logs as &$row) {
        $row['username_formatted'] = fName($row["username"]) . "<br>" . MyDateFormatN($chatobj, $row["timestamp"]) . "<br>" . meters_to_distance($row["km"], $chatobj);
        $row['usercolor'] = getDarkColorCode($row["userid"]);
        $row['userinitials'] = initial($row["username"]);

        if ($fileManager->avatarExists($chatobj["chatid"], $row["userid"])) {
            $row['userimg'] = $fileManager->avatarWeb($chatobj, $row["userid"], true);
        } else {
            $row['userimg'] = false;
        }

        if ($gpxfile_flag){
            $gpxfile = $fileManager->geojsonWeb($chatobj);
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

    $query = "SELECT * FROM users WHERE useremail=?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['userpsw'])) {
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
        $user = [
            'userid' => $mysqli->insert_id,
            'useremail' => $result['email'],
            'username' => $username,
            'userinitials' => $userinitials,
            'usercolor' => $usercolor,
            'userimg' => ''
        ];
        return [
            'status' => "success",
            'userdata' => $user
        ];
    } else {
        // Erreur lors de la création de l'utilisateur
        return [
            'status' => "fail",
            'message' => "Can't add user"
        ];
    }

}

function new_route(){
    global $mysqli;

    $routename = $_POST['routename'] ?? '';
    if(!empty($routename)){
        return ['status' => 'error', 'message' => 'Invalid routename'];
    }
    $formType = $_POST['formType'] ?? '';
    $userid = $_POST['userid'] ?? '';

    $query = "SELECT * FROM routes WHERE routename=?;";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $routename);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        if ($formType=="newroute"){
            return ['status' => 'error', 'message' => 'Route exists'];
        }
        if ($formType=="routeupdate"){
            $route = $result->fetch_assoc();
        }
    } else {
        $insertQuery = "INSERT INTO routes (routename, routeuserid) VALUES (?, ?)";
        $insertStmt = $mysqli->prepare($insertQuery);
        $insertStmt->bind_param("si", $routename, $userid);
        
        if ($insertStmt->execute()) {
            // Retourne les données du nouvel utilisateur
            $route = [
                'routeid' => $mysqli->insert_id,
                'routename' => $routename,
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

?>