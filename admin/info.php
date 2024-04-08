<?php
html_header($group." overview");
menu();

echo '<div id="containerText" style="margin-top:1rem;text-align:center">';

echo '<a href="/'.$chatObj["chatname"].'">';
if(file_exists($fileManager->chatphoto($chatObj['chatid']))){
    echo '<div class="round roundmax" style="background-image: url(\'' .$fileManager->chatphotoWeb($chatObj,true). '\')"></div>';
}else{
    echo '<div class="round roundmax" style="background-color:'.getDarkColorCode($chatObj["chatid"]).'"><div class="text">'.format_chatname($chatObj["chatname"]).'</div></div>';
}
echo '</a>';

echo '<h1><a href="/'.$group.'/">'. format_chatname($group).'</a></h1>';

$desc="";
if(!empty($chatObj["description"])) $desc.=$chatObj["description"]."<br/>";
if($chatObj["total_km"]>0) $desc.=" ".meters_to_distance( $chatObj["total_km"], $chatObj );
if($chatObj["total_dev"]>0) $desc.=" ● ".meters_to_dev( $chatObj["total_dev"], $chatObj );
if(!empty($desc)) echo "<p>".$desc."</p>";
start_date_text();

$query = "SELECT COUNT(DISTINCT userid) as nb FROM logs WHERE chatid = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $chatObj["chatid"] );
$stmt->execute();
$nb = $stmt->get_result()->fetch_assoc()["nb"];

if ($nb==1){
    echo '<p>'.$nb.' adventurer</p>';
}elseif( $nb>1 ){
    echo '<p>'.$nb.' adventurers</p>';
}

if( $chatObj["stop"]>0 ){

    echo '<p><a href="/'.$group.'/">This adventure has ended.</a></p>';

}else{
    echo '<p><a href="/'.$group.'/">Follow the adventure >>></a></p>';

    if( !empty($chatObj["link"]) ) {
        echo '<p><a href="'.$chatObj["link"].'/">Join the Telegram group and geolocate for the first time >>></a></p>';
    }else {
        //echo '<p>Goup owner: here publish affiliation link with "/link" command.</p>';

        //echo '<p><a href="https://t.me/'.$chatObj["adminid"].'/">Join the adventure by contacting the group owner >>></a></p>';
    }
}

echo '</div>';
?>