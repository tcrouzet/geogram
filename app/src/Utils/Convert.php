<?php

namespace App\Utils;

use App\Services\Database;
use App\Services\FilesManager;
use App\Services\UserService;
use App\Services\RouteService;
use App\Services\MapService;
use App\Services\ContextService;

class Convert 
{
    private $db;
    private $fileManager;
    private $user;
    private $route;
    private $map;
    private $context;
    
    public function __construct() {
        set_time_limit(0);
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->user = new UserService();
        $this->route = new RouteService();
        $this->map =  new MapService();
        $this->context = new ContextService();
    }

    // -1001831273860 g727 2024
    // -1001669242626 tour magne

    public function import_logs($chatid=-1001669242626){

        $route = $this->route->get_route_by_telegram($chatid);
        if(!$route){
            exit("Unknown Telegram $chatid chatid");
        }

        $query = "SELECT * FROM `logs` WHERE chatid = ? ORDER BY userid";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $chatid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            $geoUser = false;
            $oldUser = false;
            while($row = $result->fetch_assoc()) {
                if($oldUser !=$row["userid"]){
                    //New user
                    $geoUser = $this->user->get_user_by_telegramid($row["userid"]);
                    if($geoUser)
                        $this->user->connect($geoUser["userid"],$route['routeid'],2);
                    $oldUser = $row["userid"];
                    lecho($geoUser['username']);
                }
                if(!$geoUser){
                    //Créer un user
                    $userInfo["email"] = $row["userid"]."@telegram.org";
                    $userInfo["name"] = $row["username"];
                    $userInfo['telegram'] = $row["userid"];
                    $userInfo['routeid'] = $route['routeid'];
                    $response = $this->user->createUser($userInfo);
                    if($response["status"] == "success"){
                        $geoUser = $response["user"];
                    } else{
                        exit("bad user");
                    }
                }
                $data = $this->extractCommentData($row['comment']);
                //dump($data);exit();

                if(!empty($data['weather'])){
                    $weather = $data['weather'];
                }else{
                    $weather = null;
                }
                //lecho("weather");

                if(!empty($data['city'])){
                    $city = $data['city'];
                }else{
                    $city = null;
                }

                if (empty($data['texts'])) {
                    $data['texts'] = ['T'.$row['timestamp'] => ''];
                }

                $first = true;
                foreach($data['texts'] as $key => $value) {
                    $timestamp = intval(trim($key,"T"));
                    $message = $value;
                    lecho($timestamp);

                    $photo = 0;
                    if($first){
                        $first = false;
                        foreach($data['photos'] as $pdate => $file) {
                            $photo++;
                            $target = $this->fileManager->user_route_photo($geoUser["userid"], $route['routeid'], $timestamp, $photo);
                            Tools::resizeImage(ROOT_PATH . "/" . $file, $target, 1200);
                        }
                    }

                    if($this->map->newlog($geoUser["userid"], $route['routeid'], $row['latitude'], $row['longitude'], $message, $photo, $timestamp, null, $weather, $city)){
                        echo("<pre>$message</pre>");
                    }else{
                        echo($this->map->getError());
                    };
                }
            }
        }
        echo("<p>Done</p>");
        lexit();
    }

    private function extractCommentData($comment){
        $data = json_decode($comment, true);
        if (!$data) return false;
        
        return [
            'texts' => array_filter($data, fn($key) => str_starts_with($key, 'T'), ARRAY_FILTER_USE_KEY),
            'photos' => array_filter($data, fn($key) => str_starts_with($key, 'P'), ARRAY_FILTER_USE_KEY),
            'city' => json_decode($data['city']) ?? null,
            'weather' => json_decode($data['weather']) ?? null
        ];
    }

}