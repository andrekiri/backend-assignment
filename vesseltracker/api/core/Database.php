<?php 

namespace api\core;
use PDO;

class Database {

    // DB Connect
    public static function connect($dbsettings) {
        $conn = new PDO('mysql:host=' . $dbsettings['host'] . ';dbname=' . $dbsettings['name'], $dbsettings['username'], $dbsettings['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;     
    }

    // Send query
    public static function execute($conn, $query) {
        $sqlresult = $conn->prepare($query);
        $sqlresult->execute();
        return $sqlresult;
    }
}