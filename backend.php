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

lecho("Backend start");

init();

$view = $_POST['view'] ?? '';
$page = $_POST['page'] ?? '';
$userid = $_POST['userid'] ?? '';
$chatobj = $_POST['chatobj'] ?? '';
if($chatobj) $chatobj = json_decode($chatobj, true);

if($page=="userlogs" && isset($userid)){
    $data = get_user_logs($userid, $chatobj);
} else {
    $data = get_chat_logs($chatobj);
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

?>