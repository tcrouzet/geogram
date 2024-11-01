<?php
namespace App\Services;

class Database {
    private static $instance = null;
    private $mysqli;
    
    private function __construct() {
        $this->mysqli = new \mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PSW, MYSQL_BASE);
        if ($this->mysqli->connect_error) {
            throw new \Exception('Database connection failed: ' . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8mb4");
        $this->mysqli->select_db(MYSQL_BASE);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->mysqli;
    }
}
