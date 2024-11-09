<?php

namespace App\Services;

use App\Services\Database;
use App\Services\UserService;
use App\Services\Gpx\GpxService;
use App\Services\Gpx\GpxTools;
use App\Utils\Tools;

class RouteService 
{
    private $db;
    private $fileManager;
    private $error = false;
    private $userService;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->userService = new UserService();
    }

    public function getError() {
        return $this->error;
    }

    public function getroutes(): array
    {    
        lecho("getroutes");
    
        $userid = $_POST['userid'] ?? '';
    
        $query = "SELECT * FROM connectors c INNER JOIN routes r ON c.conrouteid = r.routeid WHERE c.conuserid = ?;";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();
        $routes = $result->fetch_all(MYSQLI_ASSOC);
        
        $superchargedRoutes = array_map(
            function($route) {
                $supercharged = $this->supercharge($route);
                return $supercharged;
            },
            $routes
        );
        
        return $superchargedRoutes;
    }

    public function get_route_by_id($routeid){
        $query="SELECT * FROM `routes` WHERE routeid = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_slug($slug){
        $query="SELECT * FROM `routes` WHERE routeslug = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_telegram($chatID){
        $query="SELECT * FROM `routes` WHERE routetelegram = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $chatID);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_link($link){
        $query="SELECT * FROM `routes` WHERE routepublisherlink = ? OR 	routeviewerlink = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $link, $link);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    private function supercharge($route): array {
        $route['photopath'] = $this->fileManager->route_photo_web($route);
        $route['invitpath'] = $this->make_invitation_link($route);
        $route['publishpath'] = $this->make_publish_link($route);
        return $route;
    }

    private function make_invitation_link($route){
        $link = BASE_URL . "/login?link=" . $route["routeviewerlink"];
        if($route["routetelegram"]){
            $link .= "&telegram=" . $route["routetelegram"];
        }
        return $link;
    }

    private function make_publish_link($route){
        $link = BASE_URL . "/login?link=" . $route["routepublisherlink"];
        if($route["routetelegram"]){
            $link .= "&telegram=" . $route["routetelegram"];
        }
        return $link;
    }

    function routeAction(){
        lecho("routeAction");
    
        $userid = $_POST['userid'] ?? '';    
        $routeid = intval($_POST['routeid'] ?? '');
        $action = $_POST['action'] ?? '';
    
        // lecho($_POST);
        // lecho($action);
    
        if($action == "purgeroute"){
            $message = $this->delete_all_logs($routeid);
        }else if($action == "purgephotos"){
            $message = $this->delete_logs_and_photos($routeid);
        }else{
            return ['status' => 'error', 'message' => "Unknown action: $action"];        
        }
    
        if($message)
            return ['status' => 'success', 'message' => "Action $action done"];
        else
            return ['status' => 'error', 'message' => "Action $action fail"];
    }
    
    public function delete_all_logs($routeid){
        $stmt = $this->db->prepare("DELETE FROM rlogs WHERE logroute=?");
        $stmt->bind_param("i", $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }

    public function delete_logs_and_photos($routeid){
        $stmt = $this->db->prepare("SELECT * FROM rlogs WHERE logroute = ? AND logphoto >0");
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        if( $result = $stmt->get_result()){
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            foreach($logs as $log){
                $this->fileManager->purgeUserRouteData($log['loguser'], $routeid);
            }
            return $this->delete_all_logs($routeid);
        }
        return false;
    }

    public function newRoute(){
    
        $userid = $_POST['userid'] ?? '';
        $routename = $_POST['routename'] ?? '';
        if(empty($routename)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }
    
        $slug = Tools::slugify($routename);
        $initials = Tools::initial($routename);
    
        $query = "SELECT * FROM routes WHERE routename=? OR routeslug=?;";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $routename, $slug);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            return ['status' => 'error', 'message' => 'Route already exists'];
        } else {
            $insertQuery = "INSERT INTO routes (routename, routeinitials, routeslug, routeuserid) VALUES (?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("sssi", $routename, $initials, $slug, $userid);
            
            if ($insertStmt->execute()) {
                // Retourne les données du nouvel utilisateur
                $routeid = $this->db->insert_id;
                $this->updateUserRoute($userid,$routeid);
                $routeviewerlink = $this->generateInvitation($routeid, 1);
                $routepublisherlink = $this->generateInvitation($routeid, 2);
                $this->updateRouteInvitation($routeid, $routeviewerlink, $routepublisherlink);
            
                $this->connect($userid,$routeid,3);
                $route = [
                    'routeid' => $routeid,
                    'routename' => $routename,
                    'routeslug' => $slug,
                    'routeuserid' => $userid,
                ];
                return [
                    'status' => "success",
                    'routedata' => $route
                ];
            } else {
                return [
                    'status' => "fail",
                    'message' => "Can't add route"
                ];
            }
        
        }
    
    }

    public function updateUserRoute($userid,$routeid){
        $stmt = $this->db->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
        $stmt->bind_param("si", $routeid, $userid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }

    public function updateRouteInvitation($routeid,$viewer,$publisher){
        $stmt = $this->db->prepare("UPDATE routes SET routepublisherlink = ?, routeviewerlink = ? WHERE routeid = ?");
        $stmt->bind_param("ssi", $publisher, $viewer, $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }
    
    public function generateInvitation($routeid, $status) {
        $randomString = bin2hex(random_bytes(8));
        $data = $routeid . '|' . $status . '|' . $randomString;
    
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', JWT_SECRET, 0, $iv);
        $token = base64_encode($iv . $encryptedData);
    
        return $token;
    }
    
    public function decodeInvitation($token) {
        $decoded = base64_decode($token);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($decoded, 0, $ivLength);
        $encryptedData = substr($decoded, $ivLength);
    
        $data = openssl_decrypt($encryptedData, 'aes-256-cbc', JWT_SECRET, 0, $iv);
        if ($data === false) {
            return false;
        }
    
        list($routeid, $status, $randomString) = explode('|', $data);
        return ['routeid' => $routeid, 'status' => $status];
    }

    public function connect($userid,$routeid,$status=2){
        lecho("connect",$userid,$routeid);
        $insertQuery = "INSERT INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iii", $routeid, $userid, $status);
        return $insertStmt->execute();
    }
    
    public function updateroute(){
        lecho("updateroute");
    
        $routeid = $_POST['routeid'] ?? '';
        if(empty($routeid)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }
    
        $routename = $_POST['routename'] ?? '';
        if(empty($routename)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }

        $telegram = $_POST['telegram'] ?? '';
        lecho("telegram",$telegram);
    
        $routestatus = $_POST['routestatus'] ?? '';
        $routerem = $_POST['routerem'] ?? '';
        $routemode = $_POST['routemode'] ?? '';
        $routeclosed = $_POST['routeclosed'] === 'true' ? 1 : 0;
    
        $query = "SELECT * FROM routes WHERE routeid=?;";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
    
            $stmt = $this->db->prepare("UPDATE routes SET routename = ?, routerem = ?, routestatus = ?, routetelegram = ?, routemode = ?, routeclosed = ? WHERE routeid = ?");
            $stmt->bind_param("sssiiii", $routename, $routerem, $routestatus, $telegram, $routemode, $routeclosed, $routeid);
            if ($stmt->execute())
               return ['status' => 'success', 'message' => 'Update fail'];
            else
                return ['status' => 'error', 'message' => 'Update fail'];
    
        } else {
            return ['status' => 'error', 'message' => 'Unknown route'];    
        }
    
    }

    public function routeconnect(){
        lecho("routeconnect");
    
        $userid = $_POST['userid'] ?? '';
        $routeid = intval($_POST['routeid'] ?? '');
        //lecho($_POST);
    
        if ($user = $this->userService->get_user($userid)) {
    
            $stmt = $this->db->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
            $stmt->bind_param("ii", $routeid, $userid);
            if ($stmt->execute()){
                $user = $this->userService->get_user($userid);
                unset($user['userpsw']);
                return ['status' => 'success', 'user' => $user ];
            }else
                return ['status' => 'error', 'message' => 'Update fail'];
    
        }
    
        return ['status' => 'error', 'message' => 'Unknown user'];
    }
    
    public function gpxupload(){
        lecho("gpxUpload");
    
        $userid = $_POST['userid'] ?? '';
    
        if (!isset($_FILES['gpxfile']) || $_FILES['gpxfile']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'Bad gpx file'];
        }
    
        $routeid = $_POST['routeid'] ?? '';
        if(empty($routeid)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }
    
        $source = $this->fileManager->gpx_source($routeid);
    
        if($source){
            if (move_uploaded_file($_FILES['gpxfile']['tmp_name'], $source)) {
    
                $nimigpx = GpxTools::gpx_minimise($source);
                $geojson = GpxTools::gpx_geojson($nimigpx);
                if($geojson){
                    $gpxService = new GpxService($routeid);
                    if ($gpxdata = $gpxService->new_GPX()){
                        $this->routeGPX($routeid,1);
                        return ['status' => 'success', 'gpx' => $gpxdata];
                    }
                }
            }
        }
    
        $this->routeGPX($routeid,0);
        return ['status' => 'error', 'message' => 'Upload fail'];
    
    }

    public function routeGPX($routeid,$value){
        $stmt = $this->db->prepare("UPDATE routes SET gpx = ? WHERE routeid = ?");
        $stmt->bind_param("ii", $value, $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }

    public function routephoto(){
        lecho("Route Photo Upload");
        
        if (!isset($_FILES['photofile']) || $_FILES['photofile']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
    
        $routeid = $_POST['routeid'] ?? '';
        if(empty($routeid)){
            return ['status' => 'error', 'message' => 'Empty route'];
        }
    
       $target = $this->fileManager->route_photo($routeid);
    
        if($target){
            if(Tools::resizeImage($_FILES['photofile']['tmp_name'], $target, 500)){
                $this->set_route_photo($routeid,1);
                return ['status' => 'success', 'message' => 'File uploaded successfully'];
            }else{
                $this->set_route_photo($routeid,0);
            }
        }
    
        return ['status' => 'error', 'message' => 'Upload fail'];
    
    }
    
    public function set_route_photo($routeid,$value){
        $stmt = $this->db->prepare("UPDATE routes SET routephoto = ? WHERE routeid = ?");
        $stmt->bind_param("ii", $value, $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }
    
}