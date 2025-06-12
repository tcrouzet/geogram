<?php
// app/src/Services/MapService.php

namespace App\Services;

use App\Services\Database;
// use App\Services\RouteService;
// use App\Utils\Tools;

use maxh\Nominatim\Nominatim;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;
use Http\Factory\Guzzle\RequestFactory;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;


class ContextService {
    private $db;
    // private $route;
    
    public function __construct($user=null) {
        $this->db = Database::getInstance()->getConnection();
        // $this->route = new RouteService();
    }

    public function cron(){
        $logs = $this->lastNlogs();
        foreach($logs as $log){
            $context = $this->findContext($log['loglatitude'],$log['loglongitude']);
            $this->updateLogContext($log['logid'], $context);
            sleep(1);
        }
        $this->purgeContext();
    }

    public function weather($lat,$lon){

        $httpRequestFactory = new RequestFactory();
        $httpClient = GuzzleAdapter::createWithConfig([]);
    
        $owm = new OpenWeatherMap(WEATHER_API, $httpClient, $httpRequestFactory);
        
        try {
            // $weather = $owm->getWeather('Paris', 'metric', 'de');
            $weather = $owm->getWeather(['lat' => $lat, 'lon' => $lon], 'metric', 'en');
        } catch(OWMException $e) {
            lecho('OpenWeatherMap exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').');
            return false;
        } catch(\Exception $e) {
            lecho('General exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').');
            return false;
        }
        
        return $weather;
    }

    public function newContext($lat_grid, $lon_grid, $city_name, $weather_data){
        
        $weather_json = json_encode($weather_data, JSON_UNESCAPED_UNICODE);

        $insertQuery = "INSERT IGNORE INTO context (lat_grid, lon_grid, city_name, weather_data) VALUES (?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("ddss", $lat_grid, $lon_grid, $city_name, $weather_json);
        if ($insertStmt->execute()) {
            return true;
        }else{
            lecho("Error inserting context: " . $insertStmt->error);
            return false;
        }

    }

    public function findContext($lat,$lon){
        // Grille fixe 0.02° = ~2.2km
        $gridLat = round($lat / 0.02) * 0.02;
        $gridLon = round($lon / 0.02) * 0.02;
    
        // Recherche existante
        $query = "SELECT * FROM context WHERE lat_grid = ? AND lon_grid = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("dd", $gridLat, $gridLon);

        if($stmt->execute()){
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
        
            if ($existing) {
                lecho("Context existing");
                return json_decode($existing['weather_data']);;
            }

            if ($weather = $this->weather($lat,$lon)){
                $this->newContext($gridLat, $gridLon, $weather->city->name, $weather);
                return json_decode(json_encode($weather));
            }
        }

        return false;
    
    }

    public function updateLogContext($logid, $weather){
        lecho("Update log context $logid");

        // dump($weather->temperature->now);

        $contextData = [
            'city' => $weather->city->name,
            'country' => $weather->city->country,
            'temp' => round($weather->temperature->now->value),
            'humidity' => round($weather->humidity->value),
            'pressure' => round($weather->pressure->value),
            'wind' => $weather->wind->speed->value,
            'windd' => $weather->wind->direction->unit,
            'cloud' => round($weather->clouds->value),
            'cloudd' => $weather->clouds->description,
            'rain' => round($weather->precipitation->value),
            'raind' => $weather->precipitation->description,
            'icon' => $weather->weather->icon
        ];

        $context_json = json_encode($contextData, JSON_UNESCAPED_UNICODE);

        $updateQuery = "UPDATE rlogs SET logcontext = ? WHERE logid = ?";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bind_param("si", $context_json, $logid);
        
        return $updateStmt->execute();
    }

    public function lastNlogs($limit=0){
        lecho("LastLog $limit");

        $logs = [];

        $query = "SELECT * FROM rlogs 
            WHERE logcontext IS NULL 
            AND logtime >= DATE_SUB(NOW(), INTERVAL 5 HOUR)
            ORDER BY logtime DESC";

        if($limit>0){
            $query .= " LIMIT ?";
        }
            

        $stmt = $this->db->prepare($query);
        if($limit>0){
           $stmt->bind_param("i", $limit);
        }
        if ($stmt->execute()) {

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            lecho("LogTOContextFound: ".count($logs) );
            
        }else{
            lecho("Error lastNlogs");
        }

        return $logs;
    }

    public function purgeContext() {
        lecho("Purging contexts older than 5 hours");
 
        $deleteQuery = "DELETE FROM context WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 HOUR)";
        $stmt = $this->db->prepare($deleteQuery);

        if ($stmt->execute()) {
            $deletedRows = $stmt->affected_rows;
            lecho("Purged $deletedRows old contexts");
            return $deletedRows;
        } else {
            lecho("Error purging contexts: " . $stmt->error);
            return false;
        }
    }

    function city($latitude,$longitude){

        $url = "http://nominatim.openstreetmap.org/";
        $nominatim = new Nominatim($url);
    
        $reverse = $nominatim->newReverse()->latlon($latitude, $longitude);
    
        $result = $nominatim->find($reverse);
        if ($result === null) {
            //No call to API
            lecho("No API result");
            return false;
        }elseif (isset($result["address"])){
            return $result["address"];
        }else{
            return false;
        }
    }
    
    function citiesUpdate(){
    }

    public function city_string($json){
        $city=array();
    
        if(isset($json->tourism)) $city[]=$json->tourism;
        if(isset($json->square)) $city[]=$json->square;
        if(isset($json->road)) $city[]=$json->road;
        if(isset($json->isolated_dwelling)) $city[]=$json->isolated_dwelling;    
        if(isset($json->locality)) $city[]=$json->locality;
        if(isset($json->hamlet)) $city[]=$json->hamlet;
        if(isset($json->neighbourhood)) $city[]=$json->neighbourhood;
        if(isset($json->quarter)) $city[]=$json->quarter;    
        if(isset($json->village)) $city[]=$json->village;
        if(isset($json->town)) $city[]=$json->town;
        if(isset($json->city)) $city[]=$json->city;
        //if(isset($json->county)) $city[]=$json->county;    
    
        $r="";
        foreach($city as $name){
            if(!empty($r)) $r.=", ";
            $r.=$name;
        }
        //dump($json);
        return $this->cleanText($r);
    
    }

    private function cleanText($msg){
        $msg=str_replace("u0027","’",$msg);
        return $msg;
    }
    
}
