<?php

/*
https://lab.tcrouzet.com/gpx/overpass.php
https://wiki.openstreetmap.org/wiki/Overpass_turbo
https://help.openstreetmap.org/questions/48449/how-to-get-area-type-given-latlon-coordinates

Tuto
https://wiki.cartocite.fr/doku.php?id=tutoverpass:tutoriel_overpass_api
https://board.phpbuilder.com/d/10396225-a-little-osm-overpass-api-example-with-php-simplexml-refining-the-request
https://wiki.openstreetmap.org/wiki/Overpass_API/Installation

Request
OSM how to get area type given lat/lon coordinates ?
*/

require __DIR__.'/vendor/autoload.php';


//$data='[out:json][timeout:5];(node(around:35,50.08417,14.35882);way(around:35,50.08417,14.35882););out tags geom(50.0824805209559,14.355810284614563,50.08604657192704,14.362789392471315);relation(around:22.5,50.08417,14.35882);out body geom(50.0824805209559,14.355810284614563,50.08604657192704,14.362789392471315);';
//$data='[out:json];area(3600046663)->.searchArea;(node["amenity"="drinking_water"](area.searchArea););out;';
//$data='[out:json][timeout:5];is_in(50.08417,14.35882)->.a;way(pivot.a);out tags geom(50.081571755126,14.357349872589,50.084366843517,14.374194145203);relation(pivot.a);out tags bb;';
$data='[out:json][timeout:5];is_in(50.08417,14.35882)->.a;way(pivot.a);out tags;relation(pivot.a);out tags bb;';

//Single
$data='way(around:10,43.45485,3.64494);out;way(around:10,43.44975,3.64417);out;way(around:10,43.45258,3.64738);out;';
$data='way(around:10,43.45485,3.64494);out;';


//track
//$data='[out:json][timeout:5];(way(around:10,43.45485,3.64494););out body;';

//Bush
//$data='[out:json][timeout:5];is_in(43.45258,3.64738)->.a;way(pivot.a);out tags;relation(pivot.a);out tags bb;';

// http://www.openstreetmap.org/browse/way/42257410
// http://www.openstreetmap.org/browse/way/277812534

//Eau
//$data='[out:json][timeout:5];(node(around:10,43.4322,3.65072);way(around:43.4322,43.45258,3.65072););out tags;out body;';

$endpoint = 'http://overpass-api.de/api/interpreter';

if(false){

    $prefix="[out:json][timeout:5];";
    $overpass = $endpoint."?data=".urlencode($prefix.$data);
    $html = file_get_contents($overpass);
    $result = json_decode($html, true); // "true" to get PHP array instead of an object
    dump($result);
    exit;

    // elements key contains the array of all required elements
    $data = $result['elements'];

    foreach($data as $key => $row) {

        // latitude
        $lat = $row['lat'];

        // longitude
        $lng = $row['lon'];
    }

}

$context = stream_context_create(['http' => [
    'method'  => 'POST',
    'header' => ['Content-Type: application/x-www-form-urlencoded'],
    'content' => 'data=' . urlencode($data),
]]);

libxml_set_streams_context($context);
$start = microtime(true);

$result = simplexml_load_file($endpoint);

dump($result);
//exit("toto");

$xpath = '/note';
$ways = $result->xpath($xpath);
foreach ($ways as $index => $way){
   dump($way);
}


?>
