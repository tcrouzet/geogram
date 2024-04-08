<?php
// cd /volume1/web/geogram
// php74 tools_maintenance.php

//http://geogram.tcrouzet.com/tools_maintenance.php
//http://geogram.tcrouzet.com/tools_maintenance.php?id=1265
require_once('filemanager.php');
require_once('functions.php');
require_once('functions_robot.php');
require_once('callback.php');
ini_set('display_errors', 1);
include (__DIR__ . '/vendor/autoload.php');

set_time_limit(0);
init();
prepare_queries();
$fileManager = new FileManager();

extract($_GET);

// $chatid=-974735885;
// delete_one_chat($chatid);
// echo("$chatid<br/>");
// exit($chatid);

//GEOJSON
$query = "SELECT * FROM `chats`";
$stmt_query = $mysqli->prepare($query);
$stmt_query->execute();
$result = $stmt_query->get_result();
if($result){

  while($chat = $result->fetch_assoc()) {
    echo($chat["chatid"]."<br/>");
    gpx_geojson($chat["chatid"]);
    
  }
}
exit("done");

//CORRECT NEW
if(true){
  //$query = "SELECT * FROM `chats` WHERE chatname = '727bikepacking'";
  $query = "SELECT * FROM `chats`";
  $stmt_query = $mysqli->prepare($query);
  $stmt_query->execute();
  $result = $stmt_query->get_result();
  if($result){

    while($chat = $result->fetch_assoc()) {
      echo($chat["chatid"]."<br/>");
      $path = $fileManager->chat_imgdir($chat["chatid"]);
      $fileManager->supDir($path."avatars");
      if(file_exists($path."chatgpx.jpeg")){
        unlink($path."chatgpx.jpeg");
        unlink($path."chatgpx-source.gpx");
      }

      copy($fileManager->old_chatgpx($chat["chatid"]), $fileManager->chatgpx($chat["chatid"]));
      copy($fileManager->old_chatgpx_source($chat["chatid"]), $fileManager->chatgpx_source($chat["chatid"]));

      $query = "SELECT * FROM `logs` WHERE chatid=?;";
      $stmt2_query = $mysqli->prepare($query);
      $stmt2_query->bind_param("i", $chat["chatid"]);
      $stmt2_query->execute();
      $result2 = $stmt2_query->get_result();

      if($result2){
        while($log = $result2->fetch_assoc()) {

          echo($fileManager->old_avatar($log['userid'])."<br>");
          echo($fileManager->avatar($chat["chatid"], $log['userid'])."<br>");

          if(file_exists($fileManager->old_avatar($log['userid']))){
            echo("Exist<br>");
            copy($fileManager->old_avatar($log['userid']), $fileManager->avatar($chat["chatid"], $log['userid']));
          }

        }

      }
    }
  }
  exit;
}
//CONVERT OLD FILE SYSTEM
if(false){
  //$query = "SELECT * FROM `chats` WHERE chatname = '727bikepacking'";
  $query = "SELECT * FROM `chats`";
  $stmt_query = $mysqli->prepare($query);
  //$stmt_query->bind_param("i", $chatid);
  $stmt_query->execute();
  $result = $stmt_query->get_result();
  if($result){

    while($chat = $result->fetch_assoc()) {
      echo($chat["chatid"]."<br/>");
      //$fileManager->make_chat_dir($chat["chatid"]);
      //echo($fileManager->old_chatphoto($chat["chatid"]));
      //echo($fileManager->chatphoto($chat["chatid"]));

      // copy($fileManager->old_chatphoto($chat["chatid"]), $fileManager->chatphoto($chat["chatid"]));
      // copy($fileManager->old_chatgpx($chat["chatid"]), $fileManager->chatgpx($chat["chatid"]));
      // copy($fileManager->old_chatgpx_source($chat["chatid"]), $fileManager->chatgpx_source($chat["chatid"]));

      $query = "SELECT * FROM `logs` WHERE chatid=?;";
      $stmt2_query = $mysqli->prepare($query);
      $stmt2_query->bind_param("i", $chat["chatid"]);
      $stmt2_query->execute();
      $result2 = $stmt2_query->get_result();

      if($result2){
        while($log = $result2->fetch_assoc()) {
          $new_flag = false;
          $json = json_decode($log["comment"],true);
          foreach($json as $key => $picture){
            if (substr($key, 0, 1)=="P"){
                  //$picture=$values[$index];
                  $path = $fileManager->absolute_path . $picture;
                  if(file_exists($path)){
                    echo ($path."<br/>");
                    $regex = '/\/(\d+)_(\d+)\.png$/';
                    $regex = '/\/(\d+)_(\d+)(?:_\d+)*\.png$/';
                    if (preg_match($regex, $path, $matches)) {
                      $userid = $matches[1];
                      $timestamp = $matches[2];
                      //echo($fileManager->chatimg($chat["chatid"],$userid,$timestamp) . "<br>");
                      $new = $fileManager->chatimg($chat["chatid"],$userid,$timestamp);
                      //echo(str_replace($fileManager->userimg,"",$new)."<br>");
                      copy($path, $new['full_path']);
                      $new_flag = true;

                      $json[$key] = $new['relative'];

                      //avatard
                      // $old_avatar = $fileManager->old_avatar($userid);
                      $avatar = $fileManager->avatar($chat["chatid"],$userid);
                      //echo($old_avatar." OA<br>");
                      //echo($avatar." A<br>");

                      if(file_exists($old_avatar)){
                        copy($old_avatar, $avatar);
                      }

                    }else{
                      $json[$key] = "";
                      echo("NoMatches<br>");
                    }
                  }
            }
          }

          if($new_flag){
            //echo("Need to update ".$log['id']."<br>");
            $updatedComment = json_encode($json);

            $query = "UPDATE logs SET comment = ? WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            if ($stmt) {
                $stmt->bind_param("si", $updatedComment, $log['id']);
                echo("Updage logs<br>");
                $stmt->execute();
                $stmt->close();
            }else{
              echo("Wrong query update<br/>");
            }
          }

        }

      }
    }
  }
  exit;
}


//reducePoints(-984025889);
exit("done");



if(false){
  $query = "SELECT * FROM logs WHERE id=".$_GET["id"].";";

  $result = $mysqli->query($query);
  if (!$result) {
    return false;
  }

  $row=$result->fetch_assoc();
  dump($row);

  $r = nearest_point($row["latitude"],$row["longitude"], $row["chatid"], $row["userid"]);
  dump($r);
  if($r){
      $gpx_point = $r["point"];
      $km = $r["km"];
      $dev = $r["dev"];
  }else{
      $gpx_point = -1;
      $km = 0;
      $dev = 0;
  }

  $query = "UPDATE logs SET gpx_point = $gpx_point, km = $km,dev = $dev WHERE id = $id";
  //$mysqli->query($query);

  exit;
}


?>