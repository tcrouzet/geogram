<?php
// cd /var/www/html/geogram/
// /usr/bin/php admin/tools_gpx.php

set_time_limit(60);
ini_set('display_errors', 1);

include (__DIR__ . '/../vendor/autoload.php');
// require_once(__DIR__ . '/admin/filemanager.php');
// require_once(__DIR__ . '/admin/functions.php');

use phpGPX\phpGPX;

function gpx_minimise($gpxFile,$minDist=200,$multisegments=true){

    if (!file_exists($gpxFile)) {
        throw new Exception("Le fichier spécifié n'existe pas: {$gpxFile}");
    }

    $mygpx = new phpGPX();
    $file = $mygpx->load($gpxFile);

    //Delete waypoints
    foreach ($file->waypoints as $index => $waypoint){
        unset($file->waypoints[$index]);
    }

    unset($file->metadata);

    //Query data
    $trackscount=0;

    foreach ($file->tracks as $tindex => $track){

        //Only one track if not $multisegments 
        if($trackscount>0 && !$multisegments){
            unset($file->tracks[$tindex]);
            continue;
        }

        unset($file->tracks[$tindex]->extensions);
        unset($file->tracks[$tindex]->description);

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

                } else {
                    //First point in track
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

                }

                $prevPoint = $point;
            }
        }

        $trackscount++;
    
    }

    $newFileName = pathinfo($gpxFile, PATHINFO_FILENAME) . '_mini.gpx';
    $newFilePath = pathinfo($gpxFile, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $newFileName;
    
    // Enregistrer le fichier GPX modifié
    $file->save($newFilePath, phpGPX::XML_FORMAT);
    return $newFilePath;
}

function gpx_geojson($gpxFile){
    
    $gpx = new phpGPX();
    $file = $gpx->load($gpxFile);
    
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

    $newFileName = str_replace("_mini","",pathinfo($gpxFile, PATHINFO_FILENAME)) . '.geojson';
    $newFilePath = pathinfo($gpxFile, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $newFileName;

    file_put_contents($newFilePath, $geojsonString);
    
}

$gpx_file = "/var/www/html/geogram/_assets/g727.gpx";
$gpx_mini_file = gpx_minimise($gpx_file);
gpx_geojson($gpx_mini_file);

?>