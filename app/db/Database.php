<?php

namespace App\db;
use PDO;
use PDOException;

class Database {

    public function getCon()
    {
        try {
            $con = new PDO(SGBD . ":host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $con;
        } catch (PDOException $e) {
            return $e;
        }
    }

}

?>