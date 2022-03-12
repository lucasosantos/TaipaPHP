<?php

namespace App\helpers;

use PDO;

class SqlHelper {

    static function Sql_concat(array $elementos) {
        $str_sql = '';
        foreach ($elementos as $key => $item) {
            $str_sql = $str_sql . $item . ",";
        }
        $str_sql = rtrim($str_sql, ',');
        return $str_sql;
    }

    static function Sql_interro(array $elementos) {
        $str_sql = '';
        foreach ($elementos as $key => $item) {
            $str_sql = $str_sql . "?" . ",";
        }
        $str_sql = rtrim($str_sql, ',');
        return $str_sql;
    }

    static function Sql_prep($stmt, array $valores) {
        foreach ($valores as $key => $item) {
            $stmt->bindParam(($key+1),$valores[$key]);
        }
        return $stmt;
    }

    static function Sql_prep_updete(array $colunas) {
        $str_sql = '';
        foreach ($colunas as $key => $item) {
            $str_sql = $str_sql . $item . "=?,";
        }
        $str_sql = rtrim($str_sql, ',');
        return $str_sql;
    }

}

?>