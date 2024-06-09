<?php
html_header( $group." Map" );
menu();
?>
    <div id="container" >

    <div id="left">
	    <div id="map" ></div>
    </div>

	<script>
		// Initialize the map
        var mymap = L.map('map').setView([0, 0], 13);

		// Add the base map layer
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
			maxZoom: 18,
		}).addTo(mymap);

        var startIcon = L.icon({
            iconUrl: 'images/start.png',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
        });

<?php

$usernames = "";
$usercolors = "";
$userinitials = "";
$userimgs = "";
$userimg_val = "";

if($page=="user" && isset($id)){

    //User profil
    //$query="SELECT * FROM logs WHERE userid=? AND chatid=? AND timestamp > ? ORDER BY timestamp ASC;";
    $query="SELECT * FROM logs WHERE userid=? AND chatid=? ORDER BY timestamp ASC;";
    $stmt = $mysqli->prepare($query);
    //$stmt->bind_param("iii", $id, $group_id, $start);
    $stmt->bind_param("ii", $id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result){

        $i=0;
        $sides=array();
        $oldrow="";
        $total=0;
        echo "var cursors = [\n";
        foreach ( $result as $row){

            echo "L.marker([".$row["latitude"].", ".$row["longitude"]."]),\n";

            $duree = $row["timestamp"] - $start;
            $usernames.="'".meters_to_distance( $row["km"], $chatObj)."<br>". MyDateFormat( $row["timestamp"] ) ."<br>id: ".$row["id"]."',";
            $usercolors.="'".getDarkColorCode($row["userid"])."',";
            $userinitials.="'".initial($row["username"])."',";

            if($i==0){
                //Première fois
                if($fileManager->avatarExists($chatObj["chatid"],$row["userid"])){
                    $userimg_val="'".$fileManager->avatarWeb($chatObj,$row["userid"],true)."',";
                }else{
                    $userimg_val="false,";
                }    

            }
            $userimgs.=$userimg_val;

            if($i %2 == 1) $color=' class="line"'; else $color='';
            
            $sidebar='<tr'.$color.' id="tr'.$i.'">';
            $sidebar.='<td>';
            $sidebar.='<p class="action" data-cursor="'.$i.'"><b>'.  MyDateFormatLong( $row["timestamp"] )."</b></br>";

            if($row["gpx_point"]>-1){
                $temps=heureminutes($duree);
                $sidebar.="Route: ".meters_to_distance( $row["km"], $chatObj )."/".meters_to_dev($row["dev"],$chatObj);
                if($start>0 ) $sidebar.=" ".SPACE2. speed_unit($row["km"],$duree) . SPACE2.$temps;
                $sidebar.="</br>";

                //$dt=$row["timestamp"]-$oldrow["timestamp"];
                //$sidebar.=$row["timestamp"]." ".$oldrow["timestamp"]." DT:$dt"." S:".$start." D:$duree<br>";

                if(!empty($oldrow)){
                    $etape=$row["km"]-$oldrow["km"];
                    $etape_dev=$row["dev"]-$oldrow["dev"];

                    if( $etape<0 ){
                        if($total==0){
                            $query="SELECT km,dev FROM `gpx` WHERE chatid = ? ORDER BY `km` DESC LIMIT 1";
                            $stmt_total = $mysqli->prepare($query);
                            $stmt_total->bind_param("i", $group_id);
                            $stmt_total->execute();
                            $iresult = $stmt_total->get_result();

                            if ($iresult->num_rows > 0) {
                                $irow = $iresult->fetch_assoc();
                                $total = $irow['km'];
                                $total_dev = $irow['dev'];
                            } else {
                                $total = 0;
                                $total_dev = 0;
                            }
                        }
                        $etape+=$total;
                        $etape_dev+=$total_dev;
                    }
 
                    //$km=intval($etape/1000);
                    $duree = $row["timestamp"] - $oldrow["timestamp"];
                    $temps=heureminutes($duree);
                    $sidebar.="Segment: ".meters_to_distance( $etape, $chatObj)."/".meters_to_dev($etape_dev,$chatObj).SPACE2.speed_unit($etape,$duree).SPACE2.$temps."</br>";

                    //$dt=$row["timestamp"]-$oldrow["timestamp"];
                    //$sidebar.=$row["timestamp"]." ".$oldrow["timestamp"]." DT:$dt"." S:".$start." D:$duree<br>";

                }

                $oldrow=$row;

            }else{
                $sidebar.="Off route</br>";
            }

            $json = json_decode($row["comment"], true);
            $keys = array_keys($json);
            $values = array_values($json);
            $pictures="";
            foreach($keys as $index => $key){
                if (substr($key, 0, 1)=="T"){
                    //Text
                    $sidebar.=guillemets($values[$index])."</br>";
                }elseif (substr($key, 0, 1)=="P"){
                    $pictures.='<img class="photo" src="'.$fileManager->relative($values[$index]).'"></br>';
                }elseif ($key=="city"){
                    $sidebar .= ' '.city_string(json_decode($values[$index]))."</br>";
                }elseif ($key=="weather"){
                    $sidebar .= weather_string(json_decode($values[$index]));
                }
            }
            $sidebar.="</p>".$pictures;
            
            $sidebar.="</td>";
            $sidebar.='</tr>';

            $sides[]=$sidebar;

            $i++;
        }
        echo "];\n";

        $sidebar ='<table id="bikers">';
        $sidebar.='<tr class="lineH">';
        if(empty($row["username"])){
            $sidebar.='<td><p>No history yet</p></td>';
        }else{
            $sidebar.='<td><p>'.fName($row["username"]).' history</p></td>';
        }
        $sidebar.='</tr>';

        foreach($sides as $k => $side){
            $sidebar .= $sides[$i-$k-1];    
        }
    
        $sidebar .= "</table>";
    }

}else{

    //Main listing
    if(!empty($chatObj["link"]) && $chatObj["stop"]==0){
        $start = time()-86400*7;
        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? AND timestamp> ? GROUP BY userid) ORDER BY km DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("iii", $group_id, $group_id, $start);
    }elseif($start>0){
        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY km DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $group_id, $group_id);
    }else{
        $query = "SELECT * FROM logs WHERE chatid=? AND (userid, timestamp) IN (SELECT userid, MAX(timestamp) FROM logs WHERE chatid=? GROUP BY userid) ORDER BY timestamp DESC,username ASC;";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $group_id, $group_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if($result){

        $bikerT="adventurer";
        $bikers = mysqli_num_rows($result);
        if($bikers>1) $bikerT.="s";
        $sidebar ='<table id="bikers_list">';
        $sidebar.='<tr class="lineH">';
        $sidebar.='<td class="spacer"></td>';
        $sidebar.='<td></td>';
        $sidebar.='<td>'.$bikers.' '.$bikerT.'</td>';
        if($chatObj["unit"]==1){
            $sidebar.='<td>mi</td>';
            $sidebar.='<td>ft+</td>';
        }else{
            $sidebar.='<td>km</td>';
            $sidebar.='<td>m+</td>';
        }
        $sidebar.='<td class="spacer"></td>';
        $sidebar.='</tr>';
            
        $i=0;
        echo "var cursors = [";
        foreach ( $result as $row){

            echo "L.marker([".$row["latitude"].", ".$row["longitude"]."]),\n";
            
            $usernames.="'".fName($row["username"])."<br>". MyDateFormat( $row["timestamp"] ) ."<br>".meters_to_distance( $row["km"],$chatObj)."',";
            $usercolors.="'".getDarkColorCode($row["userid"])."',";
            $userinitials.="'".initial($row["username"])."',";

            if($fileManager->avatarExists($chatObj["chatid"],$row["userid"])){
                $userimgs .="'".$fileManager->avatarWeb($chatObj,$row["userid"],true)."',";
                //$userimgs.="false,";
            }else{
                $userimgs.="false,";
            }

            if($i %2 == 1) $color=' line'; else $color=' lineW';
            $sidebar.='<tr class="lh '.$color.'" id="tr'.$i.'">';
            $sidebar.='<td></td>';
            $url='<a href="'.$group.'/user/'.$row["userid"].'">';
            $sidebar.='<td class="plus">'.$url.'+</a></td>';

            $symbols="";
            if(strpos($row["comment"], "P167") !== false){
                $symbols.='<img class="symb" data-symb="'.$row["userid"].'" src="images/photo.svg">';
            }
            if(strpos($row["comment"], "T167") !== false){
                $symbols.='<img class="symb" data-symb="'.$row["userid"].'" src="images/text.svg">';
            }


            $sidebar.='<td class="action" data-cursor="'.$i.'">'.fName($row["username"]).$symbols."</td>";
            $sidebar.='<td class="km">'.meters_to_distance( $row["km"], $chatObj, 0)."</td>";
            $sidebar.='<td class="dplus">'.meters_to_dev( $row["dev"], $chatObj, 0 )."</td>";
            $sidebar.='<td></td>';
            $sidebar.='</tr>';

            $i++;
        }
        echo "];\n";
    
        $sidebar .= "</table>";
    }
    //End main listing

}

echo("var usernames = [".trim($usernames,",")."];\n");
echo("var usercolors = [".trim($usercolors,",")."];\n");
echo("var userinitials = [".trim($userinitials,",")."];\n");
echo("var userimgs = [".trim($userimgs,",")."];\n");

?>

        for (var i = 0; i < cursors.length; i++) {
            setCursorDiv(cursors[i],usercolors[i],userinitials[i],userimgs[i]);
        }

        // Create an empty bounds object to store the bounds of the markers
        var markerBounds = new L.LatLngBounds();

        // Add the cursors to the map
        cursors.forEach(function(cursor, index) {
            var marker = cursor.addTo(mymap);
            //marker.bindPopup(usernames[index]);
            marker.bindTooltip(usernames[index]);

            // Add the marker's coordinates to the bounds object
            markerBounds.extend(marker.getLatLng());

            marker.on("click", function() {

                cursors.forEach(function(cursor, index2) {
                    var textElement = document.getElementById("tr"+index2);
                    if(index2 % 2)
                        textElement.className = "line";
                    else
                        textElement.className = "lineW";

                    if(index2 == index){
                        highlightCursorDiv(cursor,usercolors[index2],userinitials[index2],userimgs[index2],mymap)

                        //Scrolling
                        var container = document.getElementById("sidebar");
                        var containerRect = container.getBoundingClientRect();
                        var elementRect = textElement.getBoundingClientRect();
                        var scrollTop = container.scrollTop + elementRect.top - containerRect.top - container.clientHeight / 2;
                        container.scrollTo({top: scrollTop,behavior: 'smooth'});

                    }else{
                        //resetCursor(cursor);
                        setCursorDiv(cursor,usercolors[index2],userinitials[index2],userimgs[index2]);
                    }

                });

                var textElement = document.getElementById("tr"+index);
                textElement.className="lineG";
            });

        });

<?php
    if($chatObj["gpx"]){
?>
		// Load the GPX track and add it to the map
        var isFirstTrack = true;
        fetch('<?php echo($fileManager->geojsonWeb($chatObj))?>')
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            var geoJsonLayer = L.geoJSON(data, {
                style: function(feature) {
                    return { color: feature.properties.stroke };
                },
                onEachFeature: function(feature, layer) {
                    var firstPointLatLng = [feature.geometry.coordinates[0][1], feature.geometry.coordinates[0][0]];
                    if (isFirstTrack) {
                        L.marker(firstPointLatLng, {icon: startIcon}).addTo(mymap);
                        isFirstTrack = false;
                    }
                }
            }).addTo(mymap);

            mymap.fitBounds(geoJsonLayer.getBounds(), {padding: [0, 0]});

        })
        .catch(function(error) {
            console.log('Erreur lors du chargement du GeoJSON:', error);
        });

        
<?php }else{
        //No gpx
        echo "mymap.fitBounds(markerBounds,{maxZoom: 10});\n";
}
    ?>

        function createCircleMarker(latlng, color) {
            return L.circle(latlng, {
                color: color,
                fillColor: color,
                fillOpacity: 1.0,
                radius: 600
            }).addTo(mymap);
        }

        function setCursorDiv(cursor,color,initials,img){
            if(img){
                var customMarker = L.divIcon({
                    className: 'marker',
                    html: '<div class="marker" style="width:30px;height:30px;border:2px solid white;background-size: cover;background-image: url(\'' + img + '\')"></div>',
                    iconAnchor: [16, 16],
                    iconSize: [34, 34]
                });
            }else{
                var customMarker = L.divIcon({
                    className: 'marker',
                    html: '<div class="marker" style="background-color: ' + color + '">' + initials + '</div>',
                    iconAnchor: [15, 15],
                    iconSize: [30, 30]
                });
            }
            cursor.setIcon(customMarker);
        }

        function highlightCursorDiv(cursor,color,initials,img,mymap){
            if(img){
                var customMarker = L.divIcon({
                    className: 'marker',
                    html: '<div class="marker" style="width:50px;height:50px;border:4px solid red;background-size: cover;background-image: url(\'' + img + '\')"></div>',
                    iconAnchor: [29, 29],
                    iconSize: [58, 58]
                });
            }else{
                var customMarker = L.divIcon({
                    className: 'marker',
                    html: '<div class="marker" style="width:50px;height:50px;border:4px solid red;background-color: ' + color + '">' + initials + '</div>',
                    iconAnchor: [29, 29],
                    iconSize: [58, 58]
                });
            }
            cursor.setIcon(customMarker);
            var latlng = cursor.getLatLng();
            mymap.setView(latlng, 11);
        }

	</script>

    <div id="sidebar"><?php echo($sidebar); ?></div>

    <script>
<?php
if($page=="user" && isset($id)){
        echo "var links = document.querySelectorAll('table td p.action');\n";
}else{
        echo "var links = document.querySelectorAll('table td.action');\n";
}
?>
        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var cursorCode = event.target.getAttribute('data-cursor');

                cursors.forEach(function(cursor, index) {
                    //console.log(index+" "+cursorCode);
                    var textElement = document.getElementById("tr"+index);
                    if (index == cursorCode) {

                        textElement.className="lineG";
                        highlightCursorDiv(cursor,usercolors[index],userinitials[index],userimgs[index],mymap);

                    } else {
                        if(index %2 )
                            textElement.className="line";
                        else
                            textElement.className="lineW";
                        setCursorDiv(cursor,usercolors[index],userinitials[index],userimgs[index])
                    }
                });

            });
        }

        var symbs = document.querySelectorAll('img.symb');
        for (var i = 0; i < symbs.length; i++) {
            var link = symbs[i];
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var ui = event.target.getAttribute('data-symb');
                if (ui == null) return true;
                window.location.href=window.location.href+'/user/'+ui;
            });
        }

    </script>
    <?php //dump($chatObj);?>
    </div>