<html>
    <head>
	   <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	   <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>

    <form action="" enctype="multipart/form-data" method="post">
    <p>Fichier GPX<br/><input type="file" name="gpxfile" size="80" style="width:80%"></p>
    <input type="submit" value="Analyser">
    </form>

<?php

/*
cd /home/hebergement/tcrouzet-lab/gpx/

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar require sibyx/phpgpx

https://github.com/Sibyx/phpGPX

Autre lib : https://github.com/cwarwicker/Waddle
*/

require __DIR__.'/vendor/autoload.php';
use phpGPX\phpGPX;

$gpx = new phpGPX();

if(!empty($_POST)) extract($_POST);
//upload_errors($_FILES['gpxfile']['error']);
if(!empty($_FILES['gpxfile']['tmp_name'])){
    //var_dump($_FILES);
    $gpxfile=$_FILES['gpxfile']['tmp_name'];
    echo("<h3>".$_FILES['gpxfile']['name']."</h3>");
}else{
    echo("<h3>gth.gpx</h3>");
    $gpxfile='gth.gpx';
}
	
$file = $gpx->load($gpxfile);
phpGPX::$PRETTY_PRINT = true;

$profil=array();
$totalDistance=0;
$totalPoints=0;
$realTotalDev=0;
	
foreach ($file->tracks as $track)
{
    // Statistics for whole track
    //var_dump($track->stats->toArray());
    
    $s=1;
    foreach ($track->segments as $segment)
    {
    	// Statistics for segment of track
        myecho("Segment: $s","",false);
        $s++;
    	$segmentStats=$segment->stats->toArray();
        //var_dump($segmentStats);
        $totalDistance+=$segmentStats['distance'];
        $totalPoints+=count($segment->points);
        //var_dump($segment->points);

        $p=0;
        $oldPoint="";
        $startPoint="";
        $distance=0;
        $oldDistance=0;
        $dev=0;
        $gradiant=0;
        $oldGradiant=0;
        $slope=0;
        $oldSlope=0;
        
        foreach ($segment->points as $point)
        {

            if($p==0) {
                $oldPoint=$point;
                $startPoint=$point;
            }
            
            if($point->difference>0){
                $oldGradiant=$gradiant;
                $gradiant=round(($point->elevation-$oldPoint->elevation)*100/$point->difference);
                if(abs($gradiant)<2) $gradiant=0; 
                //echo("$gradiant<br/>");

                $oldDistance=$distance;
                $distance+=$point->difference;

                if($distance>0){
                    $oldSlope=$slope;
                    $slope=round(($point->elevation-$startPoint->elevation)*100/$distance);
                }
                
                $dev=$oldPoint->elevation-$startPoint->elevation;

            }

            //myecho("oldG:$oldGradiant G:$gradiant S:$slope D:".round($distance)." Dev:$dev");

            
            if($gradiant>0 && $oldGradiant>0){
                //Climb continue
            }elseif($gradiant<0 && $oldGradiant<0){
                //Descent continue
            }elseif($gradiant==0 && $oldGradiant==0){
                //Flat continue
            }elseif($gradiant>0 || ($oldGradiant<0 && $gradiant==0)){
                //Start climbing
                if($oldGradiant<0){
                    //End descent
                    myecho("Descent: $oldDistance $oldSlope $dev","green",false);
                    $profil[]=segment($oldDistance,$oldSlope,$dev,$oldPoint->elevation,"Descent");
                }else{
                    //End flat, start climbing
                    myecho("FlatM: ".round($oldDistance),"black",false);
                    $profil[]=segment($oldDistance,0,0,$oldPoint->elevation,"Flat");
                }
                $distance=$point->difference;
                $startPoint=$oldPoint;
            }elseif($gradiant<0 || ($oldGradiant>0 && $gradiant==0)){
                //Start decending
                if($oldGradiant>0){
                    //End climbing
                    myecho("Climb: $oldDistance $oldSlope $dev","red",false);
                    $profil[]=segment($oldDistance,$oldSlope,$dev,$oldPoint->elevation,"Climb");
                    //$realTotalDev+=$dev;
                }else{
                    //End Flat, stat descent
                    myecho("FlatD: ".round($oldDistance),"black",false);
                    $profil[]=segment($oldDistance,0,0,$oldPoint->elevation,"Flat");
                }
                $distance=$point->difference;
                $startPoint=$oldPoint;
            }

            if($point->elevation>$oldPoint->elevation){
                $realTotalDev+=$point->elevation-$oldPoint->elevation;
            }
            $oldPoint=$point;
            $p++;
        }
        //var_dump($profil);
    }
    $s++;
}

//Filtrage
//dumpProfil($profil);
$filteredProfil=array();
$first=true;
$index=0;
foreach ($profil as $section)
{
    if($first) {
        $filteredProfil[$index]=$section;
        //var_dump($filteredProfil[$index]->description);
        $first=false;
        continue;
    }

    //var_dump($index);
    if( (abs($section->slope)<2 || abs(round($section->dev))==0) && $filteredProfil[$index]->description=="Flat"){
        $filteredProfil[$index]->distance+=$section->distance;
    }else{
        $index++;
        $filteredProfil[$index]=$section;
    }

}

myecho("Distance: ".nformat(round($totalDistance/1000))."km");
myecho("Points: ".nformat($totalPoints));
myecho("Points density: ".round($totalPoints*100/$totalDistance)."%");
myecho("Max total ascent: ".nformat(round($realTotalDev))."m (point to point)");
myecho("Real total ascent: ".nformat(devCalculator($profil))."m (more filtering)");
myecho("More real total ascent: ".nformat(devCalculator($filteredProfil))."m (more filtering)");
//myecho('<a href="https://www.climbbybike.com/climb_difficulty.asp">Climbike-index: '.difficultyIndex($filteredProfil)."</a>");

//dumpProfil($filteredProfil);

function difficultyIndex($profil){
    /*
    https://www.climbbybike.com/climb\_difficulty.asp
    CLIMBBYBIKE-INDEX
    (H*100/D)*2 + H²/D + D/1000 + (T-1000)/100
    Whereby: H = gradient; D = distance in meters; T = top of mountain in meters
    */
    $index=0;
    foreach ($profil as $section)
    {
        if($section->distance>0)
            $index+=($section->slope*100/$section->distance)*2 + pow($section->slope,2) + $section->distance/1000 + ($section->elevation-1000)/100;
    }
    return round($index/100);
}

function dumpProfil($profil){
    $dev=0;
    foreach ($profil as $section)
    {
        if($section->description=="Flat") $color="black";
        elseif($section->description=="Climb"){
            $color="red";
            $dev+=$section->dev;
        }else  $color="green";
        myecho("$section->description: ".round($section->distance)."m $section->slope% ".round($section->dev)."m",$color);
    }
    myecho("Dev: ".$dev);
}

function devCalculator($profil){
    $dev=0;
    foreach ($profil as $section)
    {
        if($section->description=="Climb") $dev+=$section->dev;
    }
    return round($dev);
}

function segment($distance,$slope,$dev,$elevation,$description=""){
    $segment["distance"]=$distance;
    $segment["slope"]=$slope;
    $segment["dev"]=$dev;
    $segment["elevation"]=$elevation;
    $segment["description"]=$description;
    return (object) $segment;
}

function getDistance($p1,$p2) {
    //https://fr.wikipedia.org/wiki/Formule_de_haversine
    $earth_radius = 6371;

    $dLat = deg2rad($p2->latitude - $p1->latitude);
    $dLon = deg2rad($p2->longitude - $p1->longitude);

    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($p1->latitude)) * cos(deg2rad($p2->latitude)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    $d = $earth_radius * $c;

    return $d;
}

function meter2km($d){
    return round($d/1000,2);
}

function myecho($msg,$color="",$display=true){
    if($display)
        echo("<p style='color:$color;padding:0;margin:0;margin-block:0'>$msg<p/>");   
}

function nformat($number,$decimales=0){
    return number_format($number, $decimales, ',', ' ');
} 

?>

</body>
</html>
