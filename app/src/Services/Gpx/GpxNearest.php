<?php

namespace App\Services\Gpx;

use App\Services\Database;

class GpxNearest {

    private $db;
    private $stmt_lastpoint;
    private $stmt_distance;
    private $userid;
    private $routeid;

    public function __construct($routeid, $userid=0) {

        $this->db = Database::getInstance()->getConnection();

        $this->userid = $userid;
        $this->routeid = $routeid;

        $query = "SELECT logkm, loggpxpoint, logtime FROM `rlogs` WHERE logroute=? AND loguser=? AND loggpxpoint>-1 ORDER BY `logtime` DESC LIMIT 1;";
        $this->stmt_lastpoint = $this->db->prepare($query);

        $query = "SELECT gpxpoint, gpxkm, gpxdev, ST_Distance_Sphere(POINT(gpxlatitude, gpxlongitude), POINT(?, ?)) AS distance,
        CASE
            WHEN gpxpoint > ? THEN gpxpoint
            ELSE gpxpoint + 1000000
        END AS calcul
        FROM `rgpx`
        WHERE gpxroute = ?
        HAVING distance < 1000
        ORDER BY calcul ASC, distance ASC
        LIMIT 1";
        $this->stmt_distance = $this->db->prepare($query);
    }

    function user($latitude, $longitude){    

        //Dernier point sur la trace pour le user
        if($this->userid==0){
            $gpx_point=0;    
        }else{
            $this->stmt_lastpoint->bind_param("ii", $this->routeid, $this->userid);
            $this->stmt_lastpoint->execute();
            $gpx_point = $this->stmt_lastpoint->get_result()->fetch_assoc()['loggpxpoint'] ?? 0;
        }

        //Look after nearest point pafter gpx_point (cirular lookup)
        $this->stmt_distance->bind_param("ddii", $latitude, $longitude, $gpx_point, $this->routeid);
        $this->stmt_distance->execute();
        $result = $this->stmt_distance->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false;
        }
    }

}