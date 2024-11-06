<?php

use maxh\Nominatim\Nominatim;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;
use Http\Factory\Guzzle\RequestFactory;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use phpGPX\phpGPX;

require_once('filemanager.php');

function gpx2base($absfilepath,$multisegments=true){
    global $mysqli,$brut_chatid,$chatid;

    if(!file_exists($absfilepath)){
        lecho("BUG GPX");
        ShortLivedMessage($brut_chatid,"Error: GPX not imported.");
        return false;
    }

    $r=newGPX($chatid, $multisegments);
    if( $r["total_points"]>0 && $r["total_points"]<30000){

        $query = "UPDATE `chats` SET gpx = 1, total_km=?, total_dev=? WHERE chatid=?;";
        $stmt_gpx = $mysqli->prepare($query);
        $stmt_gpx->bind_param("iii", $r["total_km"], $r["total_dev"], $chatid);
        $stmt_gpx->execute();

        lecho("GPX data",$r);
        gpx_geojson($chatid);
        ShortLivedMessage($brut_chatid,"Your GPX is online with ".$r["total_points"]." points.");
        return true;

    }else{

        lecho("Too much points in GPX ".$absfilepath);
        ShortLivedMessage($brut_chatid,"Error: your GPX has ".$r["total_points"]. "points, more than 30,000 points.\nYou gave to optimize your GPX first.");

        return false;
    }

}


function newGPX($chatid,$multisegments=true){
    global $fileManager, $mysqli;
    $base = 'gpx';
    $gpxFile =  $fileManager->chatgpx_source($chatid);
    $gpxNewFile = $fileManager->chatgpx($chatid);
    $chatid = round($chatid);

    //Purge base
    $query = "DELETE FROM $base WHERE chatid=$chatid";
    $result = $mysqli->query($query);

    $mygpx = new phpGPX();
    $file = $mygpx->load($gpxFile);

    //Delete waypoints
    foreach ($file->waypoints as $index => $waypoint){
        unset($file->waypoints[$index]);
    }

    unset($file->metadata);
        
    // Prepare the SQL statement
    $query = "INSERT INTO $base (`chatid`, `point`, `latitude`, `longitude`, `km`, `dev`, `track`) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) die("Error1: " . $mysqli->error);

    $minDist = 100; // minimum distance in meters between points
    $total_p = 0;
    $main_km = 0;
    $main_dev = 0;

    //Query data
    $p=0;
    $km=0;
    $dev=0;
    $long=0;
    $lat=0;
    $trackscount=0;
    $stmt->bind_param('iiddiii', $chatid, $p, $lat, $long, $km, $dev, $trackscount);

    foreach ($file->tracks as $tindex => $track){

        //Only one track if not $multisegments 
        if($trackscount>0 && !$multisegments){
            unset($file->tracks[$tindex]);
            continue;
        }

        unset($file->tracks[$tindex]->extensions);
        unset($file->tracks[$tindex]->description);

        // Start a transaction
        $mysqli->begin_transaction();

        foreach ($track->segments as $segment){
    
            $trackTotalDistance = 0;
            $trackTotalDev = 0;
            $segmentDistance = 0;
            $prevPoint = null;    

            foreach ($segment->points as $index => $point){

                if ($prevPoint != null) {
                    //Normal point on track
                    $segmentDistance += $point->difference;
                    $trackTotalDistance += $point->difference;
                    $trackTotalDev = gpxdev::devtotal($point);

                } else {
                    //First point in track
                    if($trackscount>0){
                        $row = nearest_point($point->latitude, $point->longitude, $chatid, 0);
                        $p = $row['point'] + 1;
                        $trackTotalDistance = round($row['km']);
                        gpxdev::set($row['dev']);
                        $trackTotalDev = round($row['dev']);
                    }else{
                        lecho($index);
                    }
                    $segmentDistance = $minDist; //On garde le premier point
                }

                if ($segmentDistance < $minDist) {
                    //On filtre ce point
                    unset($segment->points[$index]);
                }else{
                    //On garde le point
                    $segmentDistance = 0;
                    unset($segment->points[$index]->time);
                    unset($segment->points[$index]->extensions);
                    unset($segment->points[$index]->elevation);

                    $km=round($trackTotalDistance);
                    $dev=$trackTotalDev;
                    $lat=$point->latitude;
                    $long=$point->longitude;
        
                    $stmt->execute();
                    $p++;
                    $total_p++;
                }

                $prevPoint = $point;
            }
        }
        //End track
        $mysqli->commit();

        if($trackscount==0){
            $main_km = $km;
            $main_dev = $dev;
        }

        $trackscount++;
    
    }
    $stmt->close();

    if($total_p>30000){
        $query = "DELETE FROM $base WHERE chatid=$chatid";
        $result = $mysqli->query($query);
        lecho("New GPX $chatid not in database");
    }else{
        lecho("New GPX $chatid in database");
        $file->save($gpxNewFile, phpGPX::XML_FORMAT);
    }
    
    return array( "total_km" => intval($main_km), "total_dev" => intval($main_dev), "total_points" => $total_p, "total_tracks" => $trackscount );;
    
}

function nearest_point($latitude, $longitude, $chatid, $userid=0){
    global $stmt_distance,$stmt_lastpoint;

    //Dernier point sur la trace pour le user
    if($userid==0){
        $gpx_point=0;    
    }else{
        $stmt_lastpoint->bind_param("ss", $userid, $chatid);
        $stmt_lastpoint->execute();
        $gpx_point = $stmt_lastpoint->get_result()->fetch_assoc()['gpx_point'] ?? 0;
    }

    //Look after nearest point pafter gpx_point (cirular lookup)
    $stmt_distance->bind_param("ddii", $latitude, $longitude, $gpx_point, $chatid);
    $stmt_distance->execute();
    $result = $stmt_distance->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        lecho("No results found or query error.");
        return false;
    }
}

function gpx_geojson($chatid){
    global $fileManager;
    
    $gpx = new phpGPX();
    $file = $gpx->load($fileManager->chatgpx($chatid));
    
    $geojson = [
        'type' => 'FeatureCollection',
        'features' => []
    ];
    
    $trackIndex = 0;
    
    foreach ($file->tracks as $track) {
        foreach ($track->segments as $segment) {
            $feature = [
                'type' => 'Feature',
                'properties' => [
                    'name' => $track->name,
                    'stroke' => ($trackIndex === 0) ? '#0000FF' : '#8A2BE2', // Bleu pour le 1er, violet pour les suivants
                ],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => []
                ]
            ];
    
            foreach ($segment->points as $point) {
                $feature['geometry']['coordinates'][] = [
                    $point->longitude,
                    $point->latitude,
                ];
            }
    
            $geojson['features'][] = $feature;
        }    
        $trackIndex++;
    }
    
    $geojsonString = json_encode($geojson, JSON_PRETTY_PRINT);
    file_put_contents($fileManager->geojson($chatid), $geojsonString);
    
}

function city($latitude,$longitude){

    $url = "http://nominatim.openstreetmap.org/";
    $nominatim = new Nominatim($url);

    $reverse = $nominatim->newReverse()->latlon($latitude, $longitude);

    $result = $nominatim->find($reverse);
    if ($result === null) {
        //No call to API
        lecho("No API result");
        return false;
    }elseif (isset($result["address"])){
        return $result["address"];
    }else{
        return false;
    }
}

function weather($lat,$lon){

    $httpRequestFactory = new RequestFactory();
    $httpClient = GuzzleAdapter::createWithConfig([]);
    $owm = new OpenWeatherMap(WEATHER_API, $httpClient, $httpRequestFactory);
    
    try {
        //$weather = $owm->getWeather('Berlin', 'metric', 'de');
        $weather = $owm->getWeather(['lat' => $lat, 'lon' => $lon], 'metric', 'en');
    } catch(OWMException $e) {
        lecho('OpenWeatherMap exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').');
        return false;
    } catch(\Exception $e) {
        lecho('General exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').');
        return false;
    }
    
    return $weather;
}

function citiesUpdate(){
    global $mysqli;
    
    $query = " SELECT * FROM logs  WHERE JSON_EXTRACT(comment, '$.city') IS NULL;";

    $result = $mysqli->query($query);
    if(!$result){
        lecho("Error sql");
        return false;
    }

    $up=0;
    foreach ( $result as $row) {

        $city = json_encode( city($row["latitude"],$row["longitude"]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
        $weather =  json_encode( weather($row["latitude"],$row["longitude"]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);

        $query = "UPDATE logs SET comment = JSON_SET(comment, '$.city', '".$city."', '$.weather','".$weather."') WHERE id = ".$row['id'];
        $cityr = $mysqli->query($query);
        if(!$cityr){
            lecho("Error sql 4: $query");
        }
        $up++;

    }
    //echo "$up city upade\n";
}

// function chats_test_exists(){
//     global $mysqli,$telegram;

//     $query="SELECT chatid FROM `chats`";
//     $result = $mysqli->query($query);
//     if(!$result) return false;
    
//     $del=0;
//     foreach($result as $row){
//         if (!test_one_chat_exists($row["chatid"])){
//             $del++;
//             //delete_one_chat($row["chatid"]);
//         }
//     }

//     exit("Chatdeleted $del OK\n");
// }

// function test_one_chat_exists($chatid){
//     global $telegram;
//     $chat = $telegram->getChat(['chat_id' => $chatid]);
//     dump($chat);
//     return $chat["ok"];
// }


function lostchat($newchatid,$chat_title){
    global $mysqli;
    $basetitle = format_chatitle($chat_title);
    $query="SELECT chatid FROM `chats` WHERE `chatname` LIKE ? ORDER BY `last_update` DESC LIMIT 1";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $basetitle);
    $stmt->execute();

    $result = $stmt->get_result();    
    if ($result && $row = $result->fetch_row()) {

        //Found
        lecho("No lost anymore", $newchatid, $row[0], $basetitle);
        return update_chatid($row[0], $newchatid);

    }else{
        lecho("Lost",$basetitle, $newchatid);
        return false;
    }
}


function archive_chats(){
    global $mysqli;

    lecho("Archive chats");

    $query="SELECT DISTINCT l.chatid
    FROM logs l
    JOIN chats c ON l.chatid = c.chatid
    WHERE (c.link IS NULL OR c.link = '')
    AND c.stop = 0
    AND l.chatid NOT IN (
    SELECT DISTINCT l2.chatid FROM logs l2 WHERE l2.timestamp > UNIX_TIMESTAMP(NOW() - INTERVAL 31 DAY)
    )";
    $chats = $mysqli->query($query);

    if($chats){
        foreach($chats as $chat){
            set_stop($chat['chatid'],time());
        }
    }

}

//Delete chat without logs
function clean_chats(){
    global $mysqli;

    lecho("Clean chats");

    $query="SELECT c.chatid
    FROM chats c
    WHERE NOT EXISTS (
      SELECT 1
      FROM logs l
      WHERE l.chatid = c.chatid
    )
    AND c.last_update < NOW() - INTERVAL 2 WEEK;";
    $chats = $mysqli->query($query);

    if($chats){
        foreach($chats as $chat){
            delete_one_chat($chat['chatid']);
        }
    }
    
}

function get_username($from){
    if( isset($from["username"]) ){
        $username = $from["username"];
    }elseif( isset($from["first_name"]) && isset($from["last_name"]) ){
        $username = $from["first_name"]." ".$from["last_name"];
    }elseif( isset($from["first_name"]) ){
        $username = $from["first_name"];
    }elseif( isset($from["last_name"]) ){
        $username = $from["last_name"];
    }
    return trim($username);
}


function todelete($chat_obj,$message_id,$level=1){
    global $telegram;

    lecho("To delete",$level);

    if($chat_obj['mode']<$level){
        $telegram->deleteMessage(['chat_id' => $chat_obj['chatid'], 'message_id' => $message_id]);
    }

}

function mycron($cronFile="cron.txt"){
    $lastRun = filemtime($cronFile);
    if (time() - $lastRun >= 3600) {
        lmicrotime("Cron start");

        citiesUpdate();
        archive_chats();
        clean_chats();

        touch($cronFile);
        lmicrotime("Cron end");
        flushLogBuffer();
    }

}
?>