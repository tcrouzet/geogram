<?php
namespace App\Services;

class Database {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = new \mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PSW, MYSQL_BASE);
        if ($this->db->connect_error) {
            throw new \Exception('Database connection failed: ' . $this->db->connect_error);
        }
        $this->db->set_charset("utf8mb4");
        $this->db->select_db(MYSQL_BASE);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->db;
    }

}
