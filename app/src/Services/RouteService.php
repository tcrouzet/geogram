<?php

namespace App\Services;

use App\Services\Database;


class RouteService 
{
    private $db;
    private $fileManager;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
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

    public function get_route_by_id($routeid): array
    {
    
        $query="SELECT * FROM `routes` WHERE routeid = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_slug($slug): array
    {
    
        $query="SELECT * FROM `routes` WHERE routeslug = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    private function supercharge($route): array
    {
        $route['photopath'] = $this->fileManager->route_photo_web($route);
        return $route;
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
}