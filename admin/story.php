<?php
html_header($group." story");
menu();
echo '<h1>'.format_chatname($group).'</h1>';

?>
    <div id="containerText" >

<?php

start_date_text();

echo('<p style="text-align:center;"><a href="'.$group.'/story?photos=1">Photos only</a></p>');

$photos = $_GET['photos'] ?? '0';

$query="SELECT * FROM logs WHERE chatid='$group_id' AND timestamp>$start ORDER BY timestamp ASC;";
$result = $mysqli->query($query);
if($result){

    $day="";
    $oldhead="";
    $pictures="";
    $com="";
    $city="";
    foreach ( $result as $row) {

        $newday=ucfirst(MyDateFormatLong( $row["timestamp"], false ));
        if($newday!=$day){
            //New day
            if(!empty($com)) $oldhead=str_replace("</p>"," ".cleanCom($com)."</p>",$oldhead);
            if(!empty($city)) $oldhead=str_replace("</p>","$city</p>",$oldhead);
            if(!$photos) echo $oldhead;
            echo $pictures;
            $oldhead="";
            $pictures="";
            $com="";
            $day=$newday;
            echo "<h2>$day</h2>";
        }

        $head="<p>".MyDateFormat( $row["timestamp"], true ). ' <b><a href="/'.$group.'/user/'.$row["userid"].'">'.fName($row["username"])."</a></b>";
        if($row["gpx_point"]>-1)
            $head.=" ".meters_to_distance($row["km"], $row )."/".meters_to_dev($row["dev"], $row );
        else
        $head.=" Off route";
        $head.="</p>";

        if(empty($oldhead)){
            $oldhead=$head;
        }

        if($head!=$oldhead){
            //$msg.=$head;
            //if(!empty($com)) $oldhead=str_replace("</p>"," «".$space1.$com.$space1."»</p>",$oldhead);
            if(!empty($com)) $oldhead=str_replace("</p>"," </br>".cleanCom($com)."</p>",$oldhead);
            if(!empty($city)) $oldhead=str_replace("</p>","</br>$city</p>",$oldhead);
            if(!$photos) echo $oldhead;
            echo $pictures;
            $oldhead=$head;
            $pictures="";
            $com="";
        }

        $json = json_decode($row["comment"], true);
        $keys = array_keys($json);
        $values = array_values($json);
        $city = "";
        foreach($keys as $index => $key){

            if ($key=="city"){
                $city.=' <a href="https://www.google.com/maps?q='.$row["latitude"].",".$row["longitude"].'" target="_blank">'.city_string(json_decode($values[$index]))."</a>";
            }elseif (substr($key, 0, 1)=="T"){
                //Text
                $com.=" ".$values[$index];
            }elseif (substr($key, 0, 1)=="P"){
                $pictures.='<img class="photo" src="'.$fileManager->relative($values[$index]).'"/></br>';
            }elseif ($key=="weather"){
                $city .= " ".weather_string(json_decode($values[$index]));
            }

        }

    }
    if(!empty($com)) $oldhead=str_replace("</p>"," </br>".cleanCom($com)."</p>",$oldhead);
    if(!empty($city)) $oldhead=str_replace("</p>","</br>$city</p>",$oldhead);
    if(!$photos) echo $oldhead;
    echo $pictures;
}
?>
    </div>