<?php

use phpGPX\phpGPX;

function purge_GPX($routeid){
    global $mysqli;

    $stmt = $mysqli->prepare("DELETE FROM rgpx WHERE gpxroute = ?");
    $stmt->bind_param("i", $routeid);
    if ($stmt->execute())
        return true;
    else
        return false;
}

function new_GPX($routeid, $multisegments=true, $minDist=100){
    global $mysqli;

    lecho("NewGPX");

    $fileManager = New FileManager();
    $gpxFile =  $fileManager->gpx_source($routeid);

    purge_GPX($routeid);

    $nearest = new gpxnearest($routeid);

    $mygpx = new phpGPX();
    $file = $mygpx->load($gpxFile);
        
    // Prepare the SQL statement
    $query = "INSERT INTO rgpx (`gpxroute`, `gpxpoint`, `gpxlatitude`, `gpxlongitude`, `gpxkm`, `gpxdev`, `gpxtrack`) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) die("Error1: " . $mysqli->error);

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
        $mysqli->begin_transaction();

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
                    $trackTotalDev = gpxdev::devtotal($point);

                } else {
                    //First point in track
                    if($trackscount>0){
                        $row = $nearest->user($point->latitude, $point->longitude);
                        $p = $row['gpxpoint'] + 1;
                        $trackTotalDistance = round($row['gpxkm']);
                        gpxdev::set($row['gpxdev']);
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
        $mysqli->commit();

        if($trackscount==0){
            $main_km = $km;
            $main_dev = $dev;
        }

        $trackscount++;
    
    }
    $stmt->close();
    
    return array( "total_km" => intval($main_km), "total_dev" => intval($main_dev), "total_points" => $total_p, "total_tracks" => $trackscount );;
    
}

class gpxnearest {

    public $stmt_lastpoint;
    public $stmt_distance;
    public $userid;
    public $routeid;

    public function __construct($routeid, $userid=0) {
        global $mysqli;

        $this->userid = $userid;
        $this->routeid = $routeid;

        $query = "SELECT logkm, loggpxpoint, logtime FROM `rlogs` WHERE logroute=? AND loguser=? AND loggpxpoint>-1 ORDER BY `logtime` DESC LIMIT 1;";
        $this->stmt_lastpoint = $mysqli->prepare($query);
    
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
        $this->stmt_distance = $mysqli->prepare($query);
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


class gpxdev{

    private static $points = [];
    private static $distances = [0];
    private static $windowSize = 200; // Fenêtre de lissage en mètres.
    private static $devtotal=0;
    private static $old_elevation=0;
    //private static $i=0;

    public static function devtotal($point){

        if (!is_object($point) || !property_exists($point, 'elevation') || !property_exists($point, 'difference')) {
            throw new Exception('Expected $point to be an gpx object.');
        }

        $lastIndex = count(self::$points) - 1;
        self::$points[] = $point;
        if ($lastIndex >= 0) {
            self::$distances[] = self::$distances[$lastIndex] + $point->difference;
        }else{
            self::$old_elevation = $point->elevation;
            return self::$devtotal;
        }

        // Maintient uniquement les points nécessaires dans la fenêtre.
        while (end(self::$distances) - self::$distances[0] > self::$windowSize && count(self::$points) > 1) {
            array_shift(self::$points);
            array_shift(self::$distances);
        }

        $verticalGain = 0;
        $n=0;
        foreach (self::$points as $p) {
            //lecho($p->elevation);
            if($p->elevation){
                $verticalGain += $p->elevation;
                $n++;
            }
        }
        //Average elevation on windows
        if($n>0)
            $new_elevation = $verticalGain/$n;
        else
            $new_elevation = self::$old_elevation;
        
        if($new_elevation > self::$old_elevation){
            self::$devtotal += $new_elevation - self::$old_elevation;
        }

        // lecho(self::$i, "point",round($point->elevation,2), "total",round(self::$devtotal,2), "new", round($new_elevation,2), "old",round(self::$old_elevation,2));
        // self::$i++;

        self::$old_elevation = $new_elevation;
        return round(self::$devtotal);
    }

    public static function set($dev){
        self::$devtotal = $dev;
        self::$old_elevation = $dev;
        self::$points = [];
        self::$distances = [0];
    }
}

?>