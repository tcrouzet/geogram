<?php

/*

http://localhost:8888/tcrouzet-lab/gpx/index.php


cd /Users/thierrycrouzet/Documents/tcrouzet/tcrouzet-lab/gpx
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

php composer.phar require symfony/var-dumper
php composer.phar require sibyx/phpgpx

https://github.com/Sibyx/phpGPX

Autre lib : https://github.com/cwarwicker/Waddle
*/

require_once('gpx.class.php');

include (__DIR__ . '/../vendor/autoload.php');

use phpGPX\phpGPX;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;

phpGPX::$PRETTY_PRINT = true;

if(!empty($_POST)) extract($_POST);
if(!isset($service)) $service="visu";
if(!isset($split)) $split="";
if(!isset($speed)) $speed=9.9; else $speed+=0.4;

$save=false;
$newgpx_file = new GpxFile();
$newgpx_file->metadata = new Metadata();
//dump($newgpx_file->metadata);exit;

if(!empty($_FILES['kmlfile1']['tmp_name'])){
    //var_dump($_FILES);exit;
    if(strpos($_FILES['kmlfile1']['type'],".kml+xml")===0) exit("File 1: KML needed");
    $kmlfile1=$_FILES['kmlfile1']['tmp_name'];
    $kml1 = simplexml_load_file($kmlfile1);
    //dump($kml);exit;

    $export_mame = (string)$kml1->Document->name;

    $wp1=gpx::convertPOI($kml1->Document->Placemark,$service);
    $save=true;
}

if(!empty($_FILES['kmlfile2']['tmp_name'])){
    if(strpos($_FILES['kmlfile2']['type'],".kml+xml")===0) exit("File 2: KML needed");
    $kmlfile2=$_FILES['kmlfile2']['tmp_name'];
    $kml2 = @simplexml_load_file($kmlfile2);
    if($kml2){
        $wp2=gpx::convertPOI($kml2->Document->Placemark,$service);
        $save=true;
    }
}

if(isset($wp1)&&isset($wp2)){
    $wp=array_merge($wp1,$wp2);
    //exit("WP: ".count($wp));
    $newgpx_file->waypoints = $wp;
}elseif(isset($wp1)){
    $newgpx_file->waypoints = $wp1;
}elseif(isset($wp2)){
    $newgpx_file->waypoints = $wp2;
}

if(!empty($_FILES['route']['tmp_name'])){
    //dump($_FILES['route']);

    if( strpos($_FILES['route']['type'],".kml+xml")>0 ){

        $routefile=$_FILES['route']['tmp_name'];
        $route = simplexml_load_file($routefile);
        //dump($kml3);exit;

        //dump($kml3->Document);
        $export_mame = (string)$route->Document->name;

        //dump($kml3->Document->Placemark[0]);
        //dump($kml3->Document->Placemark[1]);exit;

        $newtracks=gpx::convertTrace($route->Document->Placemark,$speed,$split);
        foreach($newtracks as $newtrack){
            $newgpx_file->tracks[] = $newtrack;
        }
        $save=true;

    }elseif( strpos($_FILES['route']['type'],"pplication/octet-stream")>0 ){

        $routefile=$_FILES['route']['tmp_name'];
        //$routefile="/Users/thierrycrouzet/Documents/tcrouzet/tcrouzet-lab/gpx/ronde.gpx";

        $route = simplexml_load_file($routefile);
        //Remove all <extensions> tags and their children
        //foreach ($route->xpath('//extensions') as $extension) {
        //    unset($extension[0]);
        //}

        $export_mame = $route->metadata->name;

        $newtracks=gpx::convertGPX($route->trk,$speed,$split);
        foreach($newtracks as $newtrack){
            $newgpx_file->tracks[] = $newtrack;
        }
        $save=true;

    }else{

        dump($_FILES['route']);
        exit("Route in not KML or GPX");
    }

}

if($save){
    //dump($newgpx_file->toXML()->saveXML());exit;

    $newgpx_file->metadata->name=$export_mame;
    $newgpx_file->metadata->description=gpx::description();

    $export_mame.="_convert.gpx";
    $export_mame=str_replace(array(" ",","),"_",$export_mame);

    header("Content-Type: application/gpx+xml");
    header("Content-Disposition: attachment; filename=".$export_mame);

    echo $newgpx_file->toXML()->saveXML();
    exit();
}

gpx::htmlHead();
?>
    <p><a href="compare.php">Compare GPX files >>></a></p>
    <p><a href="count_pois.php">POIs manager >>></a></p>
    <h3>Select at least one KML/GPX file</h3>
    <form action="" enctype="multipart/form-data" method="post">
    KML POI file 1<br/>
    <input type="file" name="kmlfile1" size="80" style="width:80%"><br/>
    KML POI file 2<br/>
    <input type="file" name="kmlfile2" size="80" style="width:80%"><br/>

    <label for="service-select">Choose a target for POIs:</label><br/>
    <select name="service" id="service-select">
    <option value="visu">VisuGPX</option>
    <option value="garmin">Garmin Basecamp</option>
    <option value="ride">RideWithGPS</option>
    </select>
    <br/><br/>


    KML/GPX route file<br/>
    <input type="file" name="route" size="80" style="width:80%"><br/>
    <br/>


    <label for="split-select">Choose a split method for Garmin limitation to 8000 points:</label><br/>
    <select name="split" id="split-select">
    <option value="" checked>No splitting</option>
    <option value="auto">Automatic</option>
    <option value="flag">Cross symbol on GoogleMap</option>
    </select>
    <br/><br/>

    <label for="speed-select">Choose a speed (important for Garmin Connect):</label><br/>
    <select name="speed" id="speed-select">
    <option value="9.5" selected>9.5 km/h</option>
    <option value="10">10 km/h</option>
    <option value="20">20 km/h</option>
    <option value="20">30 km/h</option>
    <option value="0">Minimalist</option>
    </select>
    <br/><br/>

    <input type="submit" value="Run">
    </form>

<?php

gpx::htmlFooter();

?>