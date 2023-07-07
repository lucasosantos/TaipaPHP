<?php

namespace App\db;
use PDO;
use PDOException;

class Database {

    public function getCon()
    {
        try {
            $con = new PDO(getenv('SGBD') . ":host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $con;
        } catch (PDOException $e) {
            return $e;
        }
    }

}

?>