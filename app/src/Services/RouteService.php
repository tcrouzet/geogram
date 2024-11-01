<?php

namespace App\Services;

use App\Controllers\AuthController;
use App\Services\Database;


class RouteService 
{
    private $db;
    private $fileManager;
    private $logger;
    private $auth;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        //$this->fileManager = new FilesManager();
        //$this->logger = Logger::getInstance();
        //$this->auth = new AuthController();
    }

    public function get_route_by_id($routeid){
    
        $query="SELECT * FROM `routes` WHERE routeid = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result){
            return $result->fetch_assoc();
        }else{
            return false;
        }
    }
    
}