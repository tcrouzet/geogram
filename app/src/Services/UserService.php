<?php

namespace App\Services;

use App\Controllers\AuthController;
use App\Services\Database;
use App\Utils\Logger;


class UserService 
{
    private $db;
    private $fileManager;
    private $logger;
    private $auth;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->logger = Logger::getInstance();
        $this->auth = new AuthController();

    }

    public function createUser(){
    
        lecho("CreateUser");
    
        $result = $this->auth->login();
        if($result['status'] != 'not_found') return ['status' => "fail", 'message' => "User allready there…"];
    
        list($username, $domain) = explode('@', $result['email']);
        $userinitials = $this->initial($username);
        $usercolor = $this->getDarkColorCode(rand(0,10000));
    
        $hashedPassword = password_hash($result['password'], PASSWORD_DEFAULT);
        $isPasswordValid = password_verify($result['password'], $hashedPassword);
        if(!$isPasswordValid){
            return ['status' => "fail", 'message' => "Bad password"];
        }
    
        $userroute = TESTROUTE; // Connected to testroute by default
    
        $insertQuery = "INSERT INTO users (username, userinitials, usercolor, useremail, userpsw, userroute) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("sssssi", $username, $userinitials, $usercolor, $result['email'], $hashedPassword, $userroute);
        
        if ($insertStmt->execute()) {
    
            $userid = $this->db->insert_id;
    
            $this->connect($userid,$userroute,0);
    
            // Retourne les données du nouvel utilisateur
            $token = $this->auth->saveToken($userid);
            if ($token){
                $user =  $this->auth->get_user($userid);
                return [
                    'status' => "success",
                    'userdata' => $user,
                    'route' => $userroute
                ];
            } else {
                $message = "Bad token";
            }
            
        } else {
            $essage = "Can't insert in database";
        }
        return [
            'status' => "error",
            'message' => $message
        ];

    }
    
    public function initial($name){
        $r=substr(fName($name), 0, 2);
        $r=strtr($r, 'áàâãäåçéèêëìíîïñóòôõöøúùûüýÿ', 'aaaaaaceeeeiiiinoooooouuuuyy');
        return ucfirst($r);
    }

    public function getDarkColorCode($number) {
        $code = intval(substr(strval($number), -4));
        $code = $code % 20;
        //https://colorhunt.co/palettes/dark
        $colors =[
            '#712B75',  //violet
            '#533483',  //violet
            '#726A95',  //violet
            '#C147E9',  //violet
            '#790252',  //Violet 
            '#C74B50',
            '#D49B54',
            '#E94560',
            '#950101',
            '#87431D',
            '#2F58CD',  //jaune
            '#0F3460',  //bleu
            '#3282B8',  //bleu
            '#1597BB',  //bleu
            '#46B5D1',  //bleu
            '#346751',  //vert
            '#519872',  //vert
            '#03C4A1',  //vert
            '#6B728E',  //Gris
            '#7F8487'   //Gris
        ];
        //FF4C29 F10086 trop rouge
        return $colors[$code];
    }

    public function connect($userid,$routeid,$status=2){
        lecho("connect",$userid,$routeid);
        $insertQuery = "INSERT INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iii", $routeid, $userid, $status);
        return $insertStmt->execute();
    }
}
