<?php

namespace App\Services\Gpx;

use App\Services\Database;
use App\Services\FilesManager;
use App\Services\Gpx\GpxNearest;
use App\Services\Gpx\GpxClimb;
use phpGPX\phpGPX;

class GpxService {

    private $db;
    private $routeid;
    private $fileManager;

    public function __construct($routeid) {

        $this->db = Database::getInstance()->getConnection();
        $this->routeid = $routeid;
        $this->fileManager = new FilesManager();
    }

    /* Delete route GPX data */
    function purge_GPX(){
        $stmt = $this->db->prepare("DELETE FROM rgpx WHERE gpxroute = ?");
        $stmt->bind_param("i", $this->routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }
    
    function new_GPX($multisegments=true, $minDist=100){    
        lecho("NewGPX");
    
        $gpxFile =  $this->fileManager->gpx_source($this->routeid);
    
        $this->purge_GPX();
    
        $nearest = new GpxNearest($this->routeid);
    
        $mygpx = new phpGPX();
        $file = $mygpx->load($gpxFile);
            
        // Prepare the SQL statement
        $query = "INSERT INTO rgpx (`gpxroute`, `gpxpoint`, `gpxlatitude`, `gpxlongitude`, `gpxkm`, `gpxdev`, `gpxtrack`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        if (!$stmt) die("Error1: " . $this->db->error);
    
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
        $stmt->bind_param('iiddiii', $routeid, $p, $lat, $long, $km, $dev, $trackscount);
    
        foreach ($file->tracks as $tindex => $track){
    
            //Only one track if not $multisegments 
            if($trackscount>0 && !$multisegments){
                continue;
            }
    
            // Start a transaction
            $this->db->begin_transaction();
    
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
                        $trackTotalDev = GpxClimb::devtotal($point);
    
                    } else {
                        //First point in track
                        if($trackscount>0){
                            $row = $nearest->user($point->latitude, $point->longitude);
                            $p = $row['gpxpoint'] + 1;
                            $trackTotalDistance = round($row['gpxkm']);
                            GpxClimb::set($row['gpxdev']);
                            $trackTotalDev = round($row['gpxdev']);
                        }
                        $segmentDistance = $minDist; //On garde le premier point
                    }
    
                    if ($segmentDistance >= $minDist) {
                        //On garde le point
                        $segmentDistance = 0;
    
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
            $this->db->commit();
    
            if($trackscount==0){
                $main_km = $km;
                $main_dev = $dev;
            }
    
            $trackscount++;
        
        }
        $stmt->close();
        
        return array( "total_km" => intval($main_km), "total_dev" => intval($main_dev), "total_points" => $total_p, "total_tracks" => $trackscount );;
        
    }
    

}
