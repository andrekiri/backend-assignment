<?php 

namespace api\model;
use PDO;

class Database {
    
    protected $conn;
    protected $sqlresult;

    // DB Connect
    protected function connect($dbsettings) {
        try{
            $this->conn = new PDO('mysql:host=' . $dbsettings['host'] . ';dbname=' . $dbsettings['name'], $dbsettings['username'], $dbsettings['password']);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
        } catch(\PDOException $e) {
            $this->conn = null;
            error_log($e->getMessage());
        }
        return;
    }

    // Send query
    protected function execute($query) {
        // TODO - check sql injection for all API calls
        try { 
            $this->sqlresult = $this->conn->prepare($query);
            $this->sqlresult->execute();
        } catch(\PDOException $e) {
            $this->sqlresult = null;
            error_log($e->getMessage());
        }
    }
}