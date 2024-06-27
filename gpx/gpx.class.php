<?php

require __DIR__.'/vendor/autoload.php';
use phpGPX\phpGPX;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;
use phpGPX\Models\Extensions;
use phpGPX\Models\Extensions\TrackPointExtension;
//use phpGPX\Models\Extensions\VisuGPXExtension;
use phpGPX\Parsers\TrackParser;
use phpGPX\Helpers\GeoHelper;
use phpGPX\Helpers\DistanceCalculator;

//require_once("test.php");
//use phpGPX\Models\Extensions\VisuGPXExtension;

class gpx{

    private static $timestamp=0;

    private static $stages = [];
    private static $splits = [];

    private static $description = "";
    private static $kmtotal = 0;

    private static $seuil = 20;

    private static $stage_distance = 200;

    private function __construct() {
    }

    public static function convertPOI($placemarks,$service="visu"){
        
        $wp = [];
        if(empty($placemarks)) return array();
        foreach ($placemarks as $placemark) {
    
            $style = (string)$placemark->styleUrl;
            $description = (string)$placemark->description;
            //dump($description);exit;
            $description = str_replace("description: <br>name: ","",$description);
            $description = strip_tags($description);
            //$description = str_replace(array("<![CDATA[","]]>"),"",$description);
            $description = trim($description);

    
            $coordinates = $placemark->Point->coordinates;
            list($longitude,$latitude,$elevation)=explode(",",$coordinates);
    
            //dump($placemark->name);exit;
    
            $point = new Point(Point::WAYPOINT);
            //$point->name = (string)$placemark->name." ".$style;

            if($service=="garmin"){
                $tmpname=str_replace("&","",(string)$placemark->name);
                $point->name = mb_substr($tmpname,0, 30);
            }else{
                $point->name = (string)$placemark->name;
            }

            $point->latitude = floatval($latitude);
            $point->longitude = floatval($longitude);
            $point->elevation = floatval($elevation);

            $generic_point = self::kml_POI($style);
            if($service=="visu"){
                $point->type = self::visu_POI($generic_point);
            }elseif($service=="garmin"){
                $point->type = 'user';
                $point->symbol = self::garmin_POI($generic_point);
            }else{
                $point->type = 'Dot';
                $point->symbol = self::ride_POI($generic_point);
            }
    
            //if(!empty($description)) $point->description = '<![CDATA['.$description.']]>';
            if( !empty($description) && $service!="garmin" ) $point->description = $description;

            if($point->type=="stage"){
                self::$stages[] = $point;
            }elseif($point->type=="split"){
                self::$splits[] = $point;
            }else{
                $wp[] = $point;
            }
        
        }
        return $wp;
    }

    public static function convertTrace($placemarks,$speed=9.9,$split=""){

        self::$timestamp=strtotime('today midnight');
        
        $newtracks=array();
        foreach ($placemarks as $placemark) {
    
            //dump($placemark);
            $basename=(string)$placemark->name;
            $segment_index=1;
            $point_index=0;
            $oldPoint="";
            $newtrack="";
            $newsegment="";
            $stage=0;
            $splitNow=false;

            $coords=explode("\n",$placemark->LineString->coordinates);
            if(count($coords)<2){
                exit("Wrong trace");
            }
            $SegmentsNeeded=intval(ceil(count($coords)/8000));
            $PointsNeeded=intval(ceil(count($coords)/$SegmentsNeeded))+1;
            //echo($PointsNeeded."<br>");
            if(empty($split) || $split=="flag") $PointsNeeded="10000000";
            //exit($PointsNeeded);

            foreach($coords as $coord){

                if($point_index % $PointsNeeded == 0 || $splitNow){
                    if($segment_index>1){
                        $newtrack->segments[] = $newsegment;
                        $newtracks[]=$newtrack;
                    }

                    $newtrack = new Track();
                    if(!empty($split))
                        $newtrack->name = $basename." ".$segment_index;
                    else
                        $newtrack->name = $basename;

                    //dump($newtrack);exit;

                    $newsegment = new Segment();
                    $segment_index++;
                    $splitNow=false;
                }

                $localcoord=trim($coord);
                if(empty($localcoord)) {
                    $point_index++;
                    continue;
                }
                list($longitude,$latitude,$elevation)=explode(",",$localcoord);

                $newpoint = new Point(Point::TRACKPOINT);
                $newpoint->latitude = $latitude;
                $newpoint->longitude = $longitude;
                $newpoint->elevation = round($elevation);
                $newpoint->time=self::gpx_speed_date($oldPoint,$newpoint,$speed);

                if(isset(self::$stages[$stage]) && self::getDistanceMeters($newpoint,self::$stages[$stage])< self::$stage_distance ){
                    //Stage
                    $stage++;
                    //dump($stage);exit;
                    $extensions = new Extensions();
                    //J'ai modifié
                    //vendor/sibyx/phpgpx/src/phpGPX/Parsers/Extensions/TrackPointExtensionParser.php
                    //vendor/sibyx/phpgpx/src/phpGPX/Models/Extensions/TrackPointExtension.php
                    $trackPointExtension = new TrackPointExtension();
                    $trackPointExtension->etape = "";
                    $extensions->trackPointExtension = $trackPointExtension;
                    $newpoint->extensions=$extensions;
                }

                if( $split=="flag" && isset(self::$splits[$segment_index-2]) && self::getDistanceMeters($newpoint,self::$splits[$segment_index-2])<50 ){
                    //split
                    $splitNow=true;
                }

                $newsegment->points[] = $newpoint;
                $oldPoint=$newpoint;
                $point_index++;

            }

            $newtrack->segments[] = $newsegment;

            //Statitisics
            $newtrack->recalculateStats();
            //dump($newtrack->stats);exit;
            $distance=round($newtrack->stats->distance/1000,0);
            $dev=round($newtrack->stats->cumulativeElevationGain);
            $avgSpeed=round($distance*3600/$newtrack->stats->duration,1);
            $maxAltitude=round($newtrack->stats->maxAltitude);
            $newtrack->description="Distance: ".$distance."km\n";
            $newtrack->description.="Elevation gain: ".$dev."m\n";
            $newtrack->description.="Max altitude: ".$maxAltitude."m\n";
            $newtrack->description.="Average speed: ".$avgSpeed."km/h\n";
            $newtrack->description.="Points: ".$point_index;

            self::$description.=$newtrack->description;

            $newtracks[]=$newtrack;

        }
        return $newtracks;
    }

    public static function convertGPX($tracks,$speed=9.9,$split=""){

        self::$description="";
        self::$timestamp=strtotime('today midnight');
        
        $newtracks=array();
        foreach ($tracks as $track) {
    
            $basename=(string)$track->name;
            $segment_index=1;
            $point_index=0;
            $oldPoint="";
            $newtrack="";
            $newsegment="";
            $stage=0;
            $splitNow=false;

            foreach ($track->trkseg as $segment){

                $SegmentsNeeded=intval(ceil(count($segment->trkpt)/8000));
                $PointsNeeded=intval(ceil(count($segment->trkpt)/$SegmentsNeeded))+1;
                if(empty($split) || $split=="flag") $PointsNeeded="10000000";

                foreach ($segment->trkpt as $point){

                    //dump($point);

                    if($point_index % $PointsNeeded == 0 || $splitNow){
                        if($segment_index>1){
                            $newtrack->segments[] = $newsegment;
                            $newtracks[]=$newtrack;
                        }

                        $newtrack = new Track();
                        if(!empty($split))
                            $newtrack->name = $basename." ".$segment_index;
                        else
                            $newtrack->name = $basename;

                        //dump($newtrack);exit;

                        $newsegment = new Segment();
                        $segment_index++;
                        $splitNow=false;
                    }

                    $newpoint=self::formatPoint($point);
                    if($speed>0.4){
                        $newpoint->time=self::gpx_speed_date($oldPoint,$newpoint,$speed);
                    }else{
                        $newpoint->time=null;
                        $newpoint->elevation=null;
                    }

                    if(isset(self::$stages[$stage]) && self::getDistanceMeters($newpoint,self::$stages[$stage])< self::$stage_distance){
                        //Stage
                        $stage++;
                        $extensions = new Extensions();
                        //J'ai modifié
                        //vendor/sibyx/phpgpx/src/phpGPX/Parsers/Extensions/TrackPointExtensionParser.php
                        //vendor/sibyx/phpgpx/src/phpGPX/Models/Extensions/TrackPointExtension.php
                        $trackPointExtension = new TrackPointExtension();
                        $trackPointExtension->etape = "";
                        $extensions->trackPointExtension = $trackPointExtension;
                        $newpoint->extensions=$extensions;
                    }

                    if( $split=="flag" && isset(self::$splits[$segment_index-2]) && self::getDistanceMeters($newpoint,self::$splits[$segment_index-2])<50 ){
                        //split
                        $splitNow=true;
                    }

                    $newsegment->points[] = $newpoint;
                    $oldPoint=$newpoint;
                    $point_index++;

                }
            }

            $newtrack->segments[] = $newsegment;

            //Statitisics
            $newtrack->recalculateStats();
            //dump($newtrack->stats);exit;
            $distance=round($newtrack->stats->distance/1000,0);
            $dev=round($newtrack->stats->cumulativeElevationGain);
            if($newtrack->stats->duration>0)
                $avgSpeed=round($distance*3600/$newtrack->stats->duration,1);
            else
                $avgSpeed=0;
            $maxAltitude=round($newtrack->stats->maxAltitude);
            $newtrack->description="Distance: ".$distance."km\n";
            $newtrack->description.="Elevation gain: ".$dev."m\n";
            $newtrack->description.="Max altitude: ".$maxAltitude."m\n";
            $newtrack->description.="Average speed: ".$avgSpeed."km/h\n";
            $newtrack->description.="Points: ".$point_index;

            self::$description.=$newtrack->description;

            $newtracks[]=$newtrack;

        }
        return $newtracks;
    }

    public static function statGPX($tracks){

        self::$description="";
        foreach ($tracks as $track) {
    
            $basename=(string)$track->name;
            $segment_index=1;
            $point_index=0;
            $newtrack="";
            $newsegment="";

            foreach ($track->trkseg as $segment){

                foreach ($segment->trkpt as $point){

                    //dump($point);

                    if($point_index == 0){
                        $newtrack = new Track();
                        $newtrack->name = $basename." ".$segment_index;

                        $newsegment = new Segment();
                        $segment_index++;
                    }

                    /*$newpoint = new Point(Point::TRACKPOINT);
                    $newpoint->latitude = (float) $point->attributes()->lat;
                    $newpoint->longitude = (float) $point->attributes()->lon;
                    $newpoint->elevation = round($point->ele);*/

                    $newpoint=self::formatPoint($point);
                    $newsegment->points[] = $newpoint;
                    $point_index++;

                }
            }

            $newtrack->segments[] = $newsegment;

            //Statitisics
            $newtrack->recalculateStats();
            //dump($newtrack->stats);exit;
            $distance=round($newtrack->stats->distance/1000,0);
            $dev=round($newtrack->stats->cumulativeElevationGain);
            if($newtrack->stats->duration>0){
                $avgSpeed=round($distance*3600/$newtrack->stats->duration,1);
            }else{
                $avgSpeed=0;
            }
            $maxAltitude=round($newtrack->stats->maxAltitude);
            $newtrack->description="Distance: ".$distance."km\n";
            $newtrack->description.="Elevation gain: ".$dev."m\n";
            $newtrack->description.="Max altitude: ".$maxAltitude."m\n";
            $newtrack->description.="Average speed: ".$avgSpeed."km/h\n";
            $newtrack->description.="Points: ".$point_index;

            self::$description.=$newtrack->description;

        }
        return self::$description;
    }

    public static function compareGPX($newgpx,$oldgpx){

        $newstat=gpx::statGPX($newgpx);
        $oldstat=gpx::statGPX($oldgpx);
        $newtracks=array();
        $newsegment=array();
        $segment_index=1;
        $oldpoint="";

        foreach ($newgpx as $newgpxtrack) {
    
            $formerpoint=true;
            $point_index=0;
            $nbp = 0;
            $search_index=0;

            foreach ($newgpxtrack->trkseg as $segment){

                foreach ($segment->trkpt as $point){

                    $searchpoint=self::formatPoint($point);
                    //dump($searchpoint);
                    $dist=self::searchGPX($searchpoint,$oldgpx,$search_index);
                    if($dist===false){

                        //Not fond
                        //echo "not found";dump($dist);dump($nbp);dump($point_index);exit;
                        if($formerpoint) {
                            //Create new track
                            $formerpoint=false;
                            $newsegment = new Segment();
                            if(!empty($oldpoint)) $newsegment->points[] = $oldpoint;
                            //dump($point_index);exit;
                        }
                        $newsegment->points[] = $searchpoint;
                        $point_index++;

                    }else{

                        //Found
                        //dump($dist);dump($nbp);dump($point_index);exit;
                        $search_index=$dist;
                        if($formerpoint) {
                            $oldpoint=$searchpoint;
                            continue;
                        }
                        $formerpoint=true;

                        //Close current track
                        $newsegment->points[] = $searchpoint;
                        $temptrack=self::buildTrack($newsegment,$segment_index,$point_index);
                        if(!empty($temptrack)){
                            $newtracks[]=$temptrack;
                            $segment_index++;
                        }
                        $point_index=0;

                    }

                    $oldpoint=$searchpoint;
                    $nbp++;

                }
            }

            //Close current tack
            $temptrack=self::buildTrack($newsegment,$segment_index,$point_index);
            if(!empty($temptrack)) $newtracks[]=$temptrack;

        }

        $newgpx_file = new GpxFile();
        $newgpx_file->metadata = new Metadata();
        foreach($newtracks as $newtrack){
            $newgpx_file->tracks[] = $newtrack;
        }
        $newgpx_file->metadata->name="compare";
        $newgpx_file->metadata->description="GPX 1\n".$newstat."\n\nGPX 2\n".$oldstat."\n\nTotal diff: ".self::$kmtotal."km";

        //Toutes les lignes en rouges sur Visu
        $xml=simplexml_load_string($newgpx_file->toXML()->saveXML());
        $ns_line = 'http://www.topografix.com/GPX/gpx_style/0/2';
        foreach ($xml->trk as $trk) {
            if (!isset($trk->extensions)) {
                $trk->addChild('extensions');
            }
            // Ajouter l'élément <line> avec l'espace de noms et les attributs de couleur et de largeur
            $line = $trk->extensions->addChild('line', null, $ns_line);
            $line->addChild('color', 'FF0000'); //Red
            $line->addChild('width', '4');
        }

        return $xml;

    }

    private static function buildTrack($newsegment,$segment_index,$point_index){
        if($point_index<3) return "";
        $newtrack = new Track();
        $newtrack->segments[] = $newsegment;
        $newtrack->recalculateStats();
        if($newtrack->stats->distance>8*self::$seuil){
            //Diff significatives
            $dist=round($newtrack->stats->distance/1000,2);
            self::$kmtotal+=$dist;
            $newtrack->name = $segment_index." / ".$point_index." / ".$dist."km";
            return $newtrack;
        }
        return "";
    }

    public static function searchGPX($searchpoint,$tracks,$startpoint=0){

        $formerpoint="";
        $nbp=0;
        foreach ($tracks as $track) {
    
            foreach ($track->trkseg as $segment){

                foreach ($segment->trkpt as $point){

                    $nbp++;
                    if($nbp<$startpoint) continue;

                    $newpoint=self::formatPoint($point);

                    if(!empty($formerpoint)){
                        $perpendicular = self::distance_perpendicular($formerpoint,$newpoint,$searchpoint);

                        if( $perpendicular <= self::$seuil ) {

                            /*dump(self::getDistanceMeters($formerpoint,$searchpoint));
                            dump(self::getDistanceMeters($newpoint,$searchpoint));                
                            dump($formerpoint);dump($newpoint);dump($searchpoint);dump($perpendicular);exit;*/
                            return $nbp;
                        }

                    }

                    $formerpoint = $newpoint;

                }
            }
        }
        return false;

    }

    private static function formatPoint($point){
        $newpoint = new Point(Point::TRACKPOINT);
        $newpoint->latitude = (float) $point->attributes()->lat;
        $newpoint->longitude = (float) $point->attributes()->lon;
        $newpoint->elevation = round($point->ele);
        return $newpoint;
    }

    public static function kml_POI($kml_style){
        if(strpos($kml_style,"-1508-")>0) return 'refuge';
        if(strpos($kml_style,"-1522-")>0) return 'velo'; //Bike shop
        if(strpos($kml_style,"-1660-")>0) return 'danger';
        if(strpos($kml_style,"-1899-")>0) return 'danger';
        if(strpos($kml_style,"-1765-")>0) return 'camping';
        if(strpos($kml_style,"-1535-")>0) return 'vue';
        if(strpos($kml_style,"-1716-")>0) return 'gare';
        if(strpos($kml_style,"-1644-")>0) return 'park';  //Parking
        if(strpos($kml_style,"-1841-")>0) return 'pharmacie';
        if(strpos($kml_style,"-1602-")>0) return 'hotel';
        if(strpos($kml_style,"-1634-")>0) return 'sommet';
        if(strpos($kml_style,"-1762-")>0) return 'boulangerie'; //Boulangerie
        if(strpos($kml_style,"-1651-")>0) return 'patisserie'; //Pâtisserie
        if(strpos($kml_style,"-1578-")>0) return 'commerce'; //Épicerie
        if(strpos($kml_style,"-1703-")>0) return 'drink';
        if(strpos($kml_style,"-1577-")>0) return 'restaurant';
        if(strpos($kml_style,"-1517")>0) return 'bar';

        if(strpos($kml_style,"-1500-")>0) return 'stage'; //Bivouac
        if(strpos($kml_style,"-1898-")>0) return 'split'; //Split here
        
        return "alarme";
    }

    public static function garmin_POI($style){
        if(in_array( $style, array('drink') )) return 'Drinking Water';
        if(in_array( $style, array('camping') )) return 'Campground';
        if(in_array( $style, array('danger') )) return 'Skull and Crossbones';
        if(in_array( $style, array('hotel') )) return 'Lodging';
        if(in_array( $style, array('park') )) return 'Parking Area';
        if(in_array( $style, array('restaurant') )) return 'Restaurant';
        if(in_array( $style, array('sommet') )) return 'Summit';
        if(in_array( $style, array('velo') )) return 'Bike Trail';
        if(in_array( $style, array('vue') )) return 'Scenic Area';
        if(in_array( $style, array('boulangerie') )) return 'Fast Food';
        if(in_array( $style, array('patisserie') )) return 'Pizza';
        if(in_array( $style, array('bar') )) return 'Bar';
        if(in_array( $style, array('pharmacie') )) return 'Pharmacy';
        if(in_array( $style, array('commerce') )) return 'Department Store';
        if(in_array( $style, array('refuge') )) return 'Lodge';
        
        return 'Favorite';
    }

    public static function visu_POI($style){
        if(in_array( $style, array('bar') )) return 'restaurant';
        if(in_array( $style, array('patisserie') )) return 'boulangerie';
        return $style;
    }
    
    /*
    <sym>lodging<sym>       //Icone lit   
    <sym>camping<sym>       //Icone tente
    <sym>biking<sym>        //Icone i (generic)
    <sym>trail<sym>         //Icone Trail Head
    <sym>food<sym>          //Icone restaunt
    <sym>store<sym>         //Icone restaunt
    <sym>navigation<sym>    //Icone texte (control)
    <sym>bike shop<sym>     //Icone velo

    Not working: caution, moutain, bar, drink, coffee, summit, shopping, viewpoint, water, winery, stop
    */
    public static function ride_POI($style){
        if(in_array( $style, array('hotel') )) return 'lodging';
        if(in_array( $style, array('refuge','camping') )) return 'camping';
        if(in_array( $style, array('commerce','drink','restaurant','boulangerie','patisserie','bar') )) return 'food';
        if(in_array( $style, array('danger') )) return 'biking';
        if(in_array( $style, array('velo') )) return 'bike shop';
        return 'navigation';
    }

    public static function description(){
        return self::$description;
    }

    public static function gpx_speed_date($p1,$p2,$speed=10){
        if(!empty($p1)&&!empty($p2)){
            $seconds=ceil(self::getDistanceMeters($p1,$p2)*3.6/$speed);
            self::$timestamp+=$seconds;
        }
        //echo("<p>".getDistanceMeters($p1,$p2)." $seconds</p>");
        return new \DateTime('@' . self::$timestamp);
    }

    public static function getDistanceMeters($p1,$p2) {

        return self::getDistanceMetersBrut($p1->latitude,$p1->longitude,$p2->latitude,$p2->longitude);

    }

    public static function getDistanceMetersBrut($lat1,$lon1,$lat2,$lon2) {

        //https://fr.wikipedia.org/wiki/Formule_de_haversine
        $earth_radius = 6371;
    
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
    
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
    
        return intval(ceil($d*1000));
    }

    public static function distance_perpendicular($p1,$p2,$p3) {

        // Calculer les coordonnées du point le plus proche du point (lat3, lon3) sur la ligne
        $dLat = $p2->latitude - $p1->latitude;
        $dLon = $p2->longitude - $p1->longitude;

        if ($dLat == 0 && $dLon == 0) {

            // Les deux points sont identiques, la distance est 0
            return 0;

        } else {

            if($dLon==0){
                $lonp = $p1->longitude;
                $latp = $p3->latitude;
            }elseif($dLat==0){
                $lonp = $p3->longitude;
                $latp = $p1->latitude;
            }else{
                $delta = $dLat/$dLon;
                $b = $p1->latitude-$delta*$p1->longitude;
                $bp = $p3->latitude+$delta*$p3->longitude;
                $lonp = ($bp-$b)/(2*$delta);
                $latp = $delta*$lonp + $b;
            }

            if(self::is_on_segment($p1->latitude,$p1->longitude,$p2->latitude,$p2->longitude,$latp,$lonp)){
                
                $distp= self::getDistanceMetersBRUT($latp,$lonp,$p3->latitude,$p3->longitude);
                return $distp;
            }

            $dist1 = self::getDistanceMeters($p1,$p3);
            $dist2 = self::getDistanceMeters($p2,$p3);

            return min($dist1, $dist2);
        }

    }

    public static function distance_perpendicularOLD($p1,$p2,$p3) {
        // Convertir les coordonnées en radians
        $lat1 = deg2rad($p1->latitude);
        $lon1 = deg2rad($p1->longitude);
        $lat2 = deg2rad($p2->latitude);
        $lon2 = deg2rad($p2->longitude);
        $lat3 = deg2rad($p3->latitude);
        $lon3 = deg2rad($p3->longitude);

        // Calculer les coordonnées du point le plus proche du point (lat3, lon3) sur la ligne
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        if ($dLat == 0 && $dLon == 0) {

            // Les deux points sont identiques, la distance est 0
            return 0;

        } else {

            if($dLon==0){
                $lonp = $lon1;
                $latp = $lat3;
            }elseif($dLat==0){
                $lonp = $lon3;
                $latp = $lat1;
            }else{
                $delta = $dLat/$dLon;
                $b = $lat1-$delta*$lon1;
                $bp = $lat3+$delta*$lon3;
                $lonp = ($bp-$b)/(2*$delta);
                $latp = $delta*$lonp + $b;
            }

            if(self::is_on_segment($lat1,$lon1,$lat2,$lon2,$latp,$lonp)){
                
                $distp= self::getDistanceMetersBRUT(rad2deg($latp),rad2deg($lonp),$p3->latitude,$p3->longitude);
                return $distp;
            }

            $dist1 = self::getDistanceMeters($p1,$p3);
            $dist2 = self::getDistanceMeters($p2,$p3);

            return min($dist1, $dist2);
        }

    }

    //Is P3 on segment [P1,P2]
    private static function is_on_segment($lat1,$lon1,$lat2,$lon2,$lat3,$lon3) {

        // Calculer les distances
        $dP1P3 = self::getDistanceMetersBrut($lat1,$lon1,$lat3,$lon3);
        $dP2P3 = self::getDistanceMetersBrut($lat2,$lon2,$lat3,$lon3);
        $dP1P2 = self::getDistanceMetersBrut($lat1,$lon1,$lat2,$lon2);
      
        // Vérifier si P3 est sur le segment entre P1 et P2
        if (abs($dP1P3 + $dP2P3 - $dP1P2) < self::$seuil) {
            return true;
        } else {
            return false;
        }
    }

    private static function is_on_segment_test($p1,$p2,$p3) {

        // Calculer les distances
        $dP1P3 = self::getDistanceMeters($p1,$p3);
        $dP2P3 = self::getDistanceMeters($p2,$p3);
        $dP1P2 = self::getDistanceMeters($p1,$p2);
      
        // Vérifier si P3 est sur le segment entre P1 et P2
        if (abs($dP1P3 + $dP2P3 - $dP1P2) < self::$seuil) {
            return true;
        } else {
            return false;
        }
    }
      
    public static function htmlHead() {
        echo '<html><head><meta http-equiv="content-type" content="text/html;charset=UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '</head><body>';
    }

    public static function htmlFooter() {
        echo '</body></html>';
    }

}

?>