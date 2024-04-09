<?php

function init(){
    global $mysqli;

    mb_internal_encoding("UTF-8");

    $mysqli = @mysqli_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PSW);

    if ($mysqli -> connect_errno) {
        lexit("Failed to connect to MySQL:",$mysqli -> connect_error);
    }

    $mysqli -> set_charset("utf8mb4");
    $mysqli -> select_db(MYSQL_BASE);
}

function prepare_queries(){
    global $mysqli,$stmt_distance,$stmt_lastpoint,$stmt_firstpoint,$stmt_lastpointbefore,$stmt_getchatid,$stmt_insertlog;
    
    $query = "SELECT point, km, dev, ST_Distance_Sphere(POINT(latitude, longitude), POINT(?, ?)) AS distance,
    CASE
        WHEN point > ? THEN point
        ELSE point + 1000000
    END AS calcul
    FROM gpx
    WHERE chatid = ?
    HAVING distance < 1000
    ORDER BY calcul ASC, distance ASC
    LIMIT 1";
    $stmt_distance = $mysqli->prepare($query);

    //Last point on route
    $query = "SELECT km, gpx_point, timestamp FROM `logs` WHERE userid=? AND chatid=? AND gpx_point>-1 ORDER BY `timestamp` DESC LIMIT 1;";
    $stmt_lastpoint = $mysqli->prepare($query);
    //if (!$stmt_lastpoint) die("Error: " . $mysqli->error);

    //First point on route
    $query = "SELECT km, gpx_point, timestamp FROM `logs` WHERE userid=? AND chatid=? AND gpx_point>-1 ORDER BY `km` ASC LIMIT 1;";
    $stmt_firstpoint = $mysqli->prepare($query);

    //Last point on route before
    $query = "SELECT km, gpx_point, timestamp FROM `logs` WHERE userid=? AND chatid=? AND gpx_point>-1 AND timestamp<? ORDER BY `timestamp` DESC LIMIT 1;";
    $stmt_lastpointbefore = $mysqli->prepare($query);

    //GetChatid
    $query = "SELECT * FROM `chats` WHERE chatid = ?";
    $stmt_getchatid = $mysqli->prepare($query);

    //InsertLog
    $query = "INSERT INTO `logs` (`chatid`, `userid`, `username`, `timestamp`, `latitude`, `longitude`, `gpx_point`, `km`, `dev`, `comment`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, JSON_OBJECT());";
    $stmt_insertlog = $mysqli->prepare($query);
    if (!$stmt_insertlog) die("Error: " . $mysqli->error);

}

function getOption($name){
    global $mysqli;

    $query = "SELECT * FROM options WHERE name='$name';";
    $result = $mysqli->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return json_decode(($row["value"]));
    }

    return false;

}

function saveOption($name,$value){
    global $mysqli;

    $query = "INSERT INTO options (name, value) VALUES ('$name', '".json_encode($value)."') 
          ON DUPLICATE KEY UPDATE value = '".json_encode($value)."'";

    $result = $mysqli->query($query);

    return true;

}

function city_string($json){
    $city=array();

    if(isset($json->tourism)) $city[]=$json->tourism;
    if(isset($json->square)) $city[]=$json->square;
    if(isset($json->road)) $city[]=$json->road;
    if(isset($json->isolated_dwelling)) $city[]=$json->isolated_dwelling;    
    if(isset($json->locality)) $city[]=$json->locality;
    if(isset($json->hamlet)) $city[]=$json->hamlet;
    if(isset($json->neighbourhood)) $city[]=$json->neighbourhood;
    if(isset($json->quarter)) $city[]=$json->quarter;    
    if(isset($json->village)) $city[]=$json->village;
    if(isset($json->town)) $city[]=$json->town;
    if(isset($json->city)) $city[]=$json->city;
    //if(isset($json->county)) $city[]=$json->county;    

    $r="";
    foreach($city as $name){
        if(!empty($r)) $r.=", ";
        $r.=$name;
    }
    //dump($json);
    return cleanText($r);

}

function weather_string($json){
    $weather="";
    $weather.=round($json->temperature->now->value).$json->temperature->now->unit;
    $weather.=" ".round($json->humidity->value).$json->humidity->unit." humidity";

    $weather.='<img src="https://openweathermap.org/img/wn/'.$json->weather->icon.'@2x.png" class="weather" title="'.$json->weather->description.'" />';

/*    switch ($json->weather->description) {
        case 'clear sky':
            $weather.='<img src="images/weather/day.svg" class="weather" />';
            break;
        case 'broken clouds':
            $weather.='<img src="images/weather/cloudy-day-3.svg" class="weather" />';
            break;
        case 'overcast clouds':
            $weather.='<img src="images/weather/cloudy.svg" class="weather" />';
            break;
        default:
            $weather.=" ".$json->clouds->description;
    }*/

    //dump($json);

    return $weather;

}

function guillemets($msg){
    return "«".SPACE1.trim($msg).SPACE1."»";
}

function cleanCom($msg){
    $com=trim(trim(trim($msg),"-"));
    return "«".SPACE1.$com.SPACE1."»";
}

function cleanText($msg){
    $msg=str_replace("u0027","’",$msg);
    return $msg;
}

function html_header($pagename=""){
    global $group,$page,$id,$chatObj,$fileManager;

    //dump($chatObj);
    //$version=time();
    $version="A1";

    $url=BASE_URL;
    if(!empty($group)) $url.=$group."/";
    if(!empty($page)) $url.=$page."/";
    if(!empty($id)) $url.=$id;

    echo '<!DOCTYPE html>';
    echo '<html lang="fr" xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml">';
    echo '<head profile="http://gmpg.org/xfn/11">';
    echo '<title>Geogram - '.$pagename.'</title>';
    echo '<link rel="shortcut icon" href="/favicon.ico" >';
    echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="canonical" href="'.$url.'">'."\n";

    $description="Geogram tracks adventures with Telegram Messenger. No Spot or Garmin tracker, just your phone, your photos, your comments.";
    echo '<meta name="keywords" content="Tracker, GPS, GPX, Garmin, Spot, Trail, Bikepacking, Bike, telegram">';
    echo '<meta name="description" content="'.$description.'">';
    echo '<meta name="author" content="Thierry Crouzet">'."\n";

    echo '<meta property="og:description" content="'.$description.'">';
    echo '<meta property="og:url" content="'.$url.'">';
    echo '<meta property="og:site_name" content="Geogram">';
    echo '<meta property="article:publisher" content="https://www.facebook.com/ThierryCrouzetAuteur/">'."\n";
    echo '<meta property="article:author" content="https://www.facebook.com/ThierryCrouzetAuteur/">'."\n";

    if( isset($chatObj["creationdate"]) ){
        echo '<meta property="article:published_time" content="'.date('Y-m-d\TH:i:s\+00:00', strtotime($chatObj["creationdate"])).'">';
    }else{
        echo '<meta property="article:published_time" content="2023-05-02T15:04:27+00:00">';
    }
    echo '<meta property="article:modified_time" content="'.date('Y-m-d\TH:i:s\+00:00', time()).'">';

    if( isset($chatObj["photo"]) && $chatObj["photo"]==1 ){
        echo '<meta property="og:image" content="'.$fileManager->chatphotoWeb($chatObj,false,true).'">';
        echo '<meta property="og:image:width" content="640">';
        echo '<meta property="og:image:height" content="640">';
    }else{
        echo '<meta property="og:image" content="'.BASE_URL.'images/cover.jpeg">';
        echo '<meta property="og:image:width" content="768">';
        echo '<meta property="og:image:height" content="768">';
    }
    echo '<meta property="og:image:type" content="image/jpeg">';
    echo '<meta name="author" content="Thierry Crouzet">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:creator" content="@https://twitter.com/crouzet">';
    echo '<meta name="twitter:site" content="@crouzet">'."\n";

    if( (strpos( $pagename , " Map") !== false ) ){
        echo '<meta http-equiv="refresh" content="600">';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />."\n"';
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>'."\n";
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.4.0/gpx.min.js"></script>'."\n";
    }

    echo "\n<link rel='stylesheet' id='style-css' href='/geogram.css?$version' type='text/css' media='screen' />\n";

    echo '</head>';
    echo '<body>';
    
}

function menu(){
    global $group,$group_id;

    if(!empty($group_id)){
        echo '<div id="menu"><div id="menu_left"><a href="/'.$group.'" class="first">'.format_chatname($group).'</a><a href="/'.$group.'/story" class="last">Story</a><a href="/'.$group.'/info" class="last">Info'.SPACE1.'</a></div>';
    }else{
        echo '<div id="menu"><div id="menu_center"><a href="/help#add" class="center">Add your own adventure!!!</a></div>';
    }
    echo menu_button();
    echo '</div>';
}

function menu_button(){
    global $group;

    $home="";
    $help="";
    $contact="";
    $archives="";
    $news="";
    if (empty($group)) $home=' class="current"';
    if ($group=="help") $help=' class="current"';
    if ($group=="contact") $contact=' class="current"';
    if ($group=="archives") $archives=' class="current"';
    if ($group=="news_fr") $news=' class="current"';

    $msg='
    <div id="m">
    <button class="button" onclick="window.location.href=\'/\'">&#9776;</button>
    <ul>
      <li'.$home.'><a href="/">Adventures</a></li>
      <li'.$archives.'><a href="/archives">Archives</a></li>
      <li'.$help.'><a href="/help">Features</a></li>
      <li'.$news.'><a href="/news_fr">News</a></li>
      <li'.$contact.'><a href="/contact">About/Contact</a></li>
      <li class="donate"><a href="https://www.paypal.com/donate/?business=MCZTJGYPGXXCW&no_recurring=0&currency_code=EUR">DONATE</a></li>
    </ul>
  </div>';
  return $msg;    
}

function baseline(){
    global $group;

    if (empty($group)) {
        echo '<h1>Geogram tracks adventures with Telegram Messenger</h1>';
        echo '<p class="underh1">No Spot or Garmin tracker, just your phone, your photos, your comments…</p>';
    }elseif( $group=="help"){
        echo '<h1>Geogram features</h1>';
    }elseif( $group=="news_fr"){
        echo '<h1>Geogram news</h1>';
    }elseif($group=="contact"){
        echo '<h1>Geogram about/contact</h1>';
    }elseif($group=="archives"){
        echo '<h1>Geogram archives</h1>';
    }elseif($group=="404"){
        echo '<h1>Page not found</h1>';
    }

}

function fName($name){
    return format_chatitle($name);
}

function format_chatitle($message){
    $title = iconv('UTF-8', 'ASCII//TRANSLIT', trim($message));
    $title = str_replace(" ","_",$title);
    $title = preg_replace('/[^A-Za-z0-9_\-]/', '', $title);        
    return $title;
}

function getDarkColorCode($number) {
    $code = intval(substr(strval($number), -4));
    $code = $code % 20;
    //https://colorhunt.co/palettes/dark
    $colors =[
        '#712B75',  //violet
        '#533483',  //violet
        '#726A95',  //violet
        '#C147E9',  //violet
        '#790252',  //Violet 
        '#C74B50',
        '#D49B54',
        '#E94560',
        '#950101',
        '#87431D',
        '#2F58CD',  //jaune
        '#0F3460',  //bleu
        '#3282B8',  //bleu
        '#1597BB',  //bleu
        '#46B5D1',  //bleu
        '#346751',  //vert
        '#519872',  //vert
        '#03C4A1',  //vert
        '#6B728E',  //Gris
        '#7F8487'   //Gris
    ];
    //FF4C29 F10086 trop rouge
    return $colors[$code];
}

function heureminutes($duree){

    if($duree < 0) return "000:00";

    // Calcul des heures et minutes
    $heures = floor($duree / 3600);
    $minutes = floor(($duree - $heures * 3600) / 60);

    // Formatage des heures et minutes
    //$formatted_heures = sprintf('%03d', $heures);
    $formatted_minutes = sprintf('%02d', $minutes);

    // Concaténation des heures et minutes
    return $heures . ':' . $formatted_minutes;
}

function initial($name){
    $r=substr(fName($name), 0, 2);
    $r=strtr($r, 'áàâãäåçéèêëìíîïñóòôõöøúùûüýÿ', 'aaaaaaceeeeiiiinoooooouuuuyy');
    return ucfirst($r);
}

function frenchDate($format, $date) {
    $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $french_days = array('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche');
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
    $new_date = str_replace($english_months, $french_months, str_replace($english_days, $french_days, date($format, $date ) ) );
    return str_replace(" 1 "," 1er ",$new_date);
}

function MyDate($date){
    if(false){
        return frenchDate('l j F', $date);
    }else{
        return date("l F j, Y", $date);
    }
}

function meters_to_distance($meters,$chatObj,$display=1){

    if($chatObj["unit"]==1){
        $r = number_format(intval($meters*0.621371/1000));
        if($display)
            return $r."mi";
        else
            return $r;
    }else{
        $r = intval($meters/1000);
        if($display)
            return $r."km";
        else
            return $r;
    }

}

function meters_to_dev($meters,$chatObj,$display=1){

    if($chatObj["unit"]==1){
        $r = number_format( roundNumber($meters*3.2808399) );
        if($display)
            return $r."ft";
        else
            return $r;
    }else{
        $r = number_format( roundNumber($meters) , 0, ',', ' ');
        if($display)
            return $r."m";
        else
            return $r;
    }

}

function speed_unit($meters,$seconds){
    global $chatObj;

    if($chatObj["unit"]==1){
        if($seconds>0)
            return round($meters*2.23694/$seconds,1)."mi/h";
        else
            return "0mi/h";
    }else{
        if($seconds>0)
            return round($meters*3.6/$seconds,1)."km/h";
        else
            return "0km/h";
    }
}

function roundNumber($num){
    $l=strlen(intval($num));
    if($l>4)
        $cut=3-$l;
    else
        $cut=0;
    return round($num, $cut);
}

function MyDateFormat($timestamp,$justhour=false){
    global $chatObj;

    if($chatObj["unit"]==1){
        $format="g:ia";
    }else{
        $format="G:i";
    }

    if(!$justhour) $format.=" Y/n/j";
    
    return date( $format, timezone($timestamp,$chatObj["timediff"]) );
}

function MyDateFormatLong($timestamp,$hour=true){
    global $chatObj;

    $format="l F jS, Y";

    if($hour){
        if($chatObj["unit"]==1){
            $format.=" g:ia";
        }else{
            $format.=" G:i";
        }
    }
    
    return date( $format, timezone($timestamp,$chatObj["timediff"]) );
}

function timezone($timestamp,$zone=0){
    return $timestamp-$zone*3600;
}

function validate_point($userid,$chatid,$gpx,$timestamp){
    return true;
    global $stmt_lastpointbefore;

    $stmt_lastpointbefore->bind_param("ssi", $userid, $chatid, $timestamp);
    $stmt_lastpointbefore->execute();
    $last = $stmt_lastpointbefore->get_result()->fetch_assoc() ?? false;
    if (!$last) {
        echo "First point\n";
        return true; // First point
    }

    $current=array("km" => $gpx["km"], "timestamp" => $timestamp);
    //dump($current);
    return test_speed($last,$current);
}

function test_speed($last,$current){
    
    $total=1000;
    $duree = $current["timestamp"] - $last["timestamp"];
    dump($duree);
    if($current["km"] > $last["km"]){
        $dist = $current["km"] - $last["km"];
    }else{
        $dist = $current["km"] +$total - $last["km"];
    }
    $v = $dist/$duree;

    if($v > 25) {
        echo "Too fast\n";
        return false;
    }

    return true;
}

function getDistanceMetersBrut($lat1,$lon1,$lat2,$lon2) {

    //https://fr.wikipedia.org/wiki/Formule_de_haversine
    $earth_radius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    $d = $earth_radius * $c;

    return intval(ceil($d*1000));
}


function format_chatname($name){
    return str_replace("_"," ",$name);
}


function start_date_text(){
    global $start;
    
    if($start==0){
        echo "<p>Individual Time Trial mode (go when you want).</p>";
    }elseif($start>time()){
        echo "<p>The adventure will start the ".MyDate( $start ).".</p>";
        //echo "<p>La randonnée commencera le ".frenchDate('l j F Y à H\hi', $start).". En attendant, messages d'entraînement.</p>";
        $start=0;
    }else{
        echo "<p>The adventure has started the ".MyDate( $start )."</p>";
        //echo "<p>La randonné a commencé le ".MyDate('l j F Y à H\hi', $start)."</p>";
    }
    
}


?>