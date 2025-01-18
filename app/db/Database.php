<?php

namespace App\db;
use PDO;
use PDOException;

class Database {

    public function getCon()
    {
        try {
            $con = new PDO($_ENV['SGBD'] . ":host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['BD_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $con;
        } catch (PDOException $e) {
            return $e;
        }
    }

}

?>