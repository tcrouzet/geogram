<?php
html_header($archives);
menu();
baseline($archives);
?>

<div id="containerText">

<?php

if($archives=="archives"){

    $query = "SELECT c.*, l.*
    FROM chats c
    LEFT JOIN logs l ON l.chatid = c.chatid
    WHERE c.stop > 0
    AND l.timestamp = (
        SELECT MAX(timestamp)
        FROM logs
        WHERE chatid = c.chatid
    )
    ORDER BY l.timestamp DESC";

}else{

    $query = "SELECT c.*, l.*
    FROM chats c
    LEFT JOIN logs l ON l.chatid = c.chatid
    WHERE c.stop = 0
    AND l.timestamp = (
        SELECT MAX(timestamp)
        FROM logs
        WHERE chatid = c.chatid
    )
    ORDER BY l.timestamp DESC";
}

$result = $mysqli->query($query);
if($result){

//    dump($result);
    foreach ( $result as $row) {
        //dump($row);

        $chatObj=$row;

        echo '<div class="une_chat">';

        echo '<a href="'.$row["chatname"].'">';
        if(file_exists($fileManager->chatphoto($chatObj['chatid']))){
            echo '<div class="round" style="background-image: url(\'' .$fileManager->chatphotoWeb($chatObj,true, false). '\')"></div>';
        }else{
            echo '<div class="round" style="background-color:'.getDarkColorCode($row["chatid"]).'"><div class="text">'.format_chatname($row["chatname"]).'</div></div>';
        }
        echo '</a>';


        echo '<div class="text">';
        echo '<h3><a href="'.$row["chatname"].'">'.format_chatname($row["chatname"]).'</a></h3>';
        $desc="";
        if(!empty($row["description"])) $desc.=$row["description"]."<br/>";
        if($row["total_km"]>0) $desc.=meters_to_distance( $row["total_km"], $chatObj );
        if($row["total_dev"]>0) $desc.=" ● ".meters_to_dev( $row["total_dev"], $chatObj );
        if(!empty($desc)) echo "<p>".$desc."</p>";

        if($row["start"]>0){
            echo "<p>Start: ".MyDateFormat( $row["start"] ).'</p>';
        }

        if($row["stop"]>0){
            echo '<p>Ended</p>';
        }

        echo '<p><a href="'.$row["chatname"].'/user/'.$row["userid"].'">Last update: '.MyDateFormat( $row["timestamp"] ).'</a></p>';


        if(!empty($row["link"]))
            echo '<p><a href="'.$row["link"].'" target="_blank">Join this public group</a></p>';

        echo '</div>';

        echo '</div>';

    }
}
?>
    </div>