<?php
// app/src/Services/MapService.php

namespace App\Services;

use App\Services\Database;
use App\Utils\Tools;

use maxh\Nominatim\Nominatim;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;
use Http\Factory\Guzzle\RequestFactory;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;


class ContextService 
{
    private $db;
    
    public function __construct($user=null) 
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function weather($lat,$lon){

        $httpRequestFactory = new RequestFactory();
        $httpClient = GuzzleAdapter::createWithConfig([]);
        $owm = new OpenWeatherMap(WEATHER_API, $httpClient, $httpRequestFactory);
        
        try {
            //$weather = $owm->getWeather('Berlin', 'metric', 'de');
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
        $query = " SELECT * FROM logs  WHERE JSON_EXTRACT(comment, '$.city') IS NULL;";
    
        $result = $this->db->query($query);
        if(!$result){
            lecho("Error sql");
            return false;
        }
    
        $up=0;
        foreach ( $result as $row) {
    
            $city = json_encode( $this->city($row["latitude"],$row["longitude"]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
            $weather =  json_encode( $this->weather($row["latitude"],$row["longitude"]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
    
            $query = "UPDATE logs SET comment = JSON_SET(comment, '$.city', '".$city."', '$.weather','".$weather."') WHERE id = ".$row['id'];
            $cityr = $this->db->query($query);
            if(!$cityr){
                lecho("Error sql 4: $query");
            }
            $up++;
    
        }
        //echo "$up city upade\n";
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
    
    public function weather_string($data){
        $weather="";
        $weather.=round($data->temperature->now->value).$data->temperature->now->unit;
        $weather.=", ".round($data->humidity->value).$data->humidity->unit." humidity";
        $weather.=", ".$data->weather->description;
        $weather.=", ".$data->wind->speed->description." ". $data->wind->direction->description;
        return $weather;    
    }

    private function cleanText($msg){
        $msg=str_replace("u0027","’",$msg);
        return $msg;
    }
    
    
    
    
}
