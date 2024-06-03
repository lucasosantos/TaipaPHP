<?php

namespace App\core;
use App\db\database;
use PDOException;
use App\helpers\SqlHelper;
use PDO;

class Model {

    public $table;
    protected $columns = array();

    private function getCon(){
        $con = new Database;
        return $con->getCon();
    }

    public function insert(array $colunas, array $valores) {

        $str_colunas = SqlHelper::Sql_concat($colunas);
        $str_interro = SqlHelper::Sql_interro($valores);

        try {
            $stmt = $this->getCon()->prepare("INSERT INTO " . $this->table . " (" . $str_colunas . ") VALUES (" . $str_interro . ")");
            $stmt = SqlHelper::Sql_prep($stmt, $valores);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }

    }

    public function listAll()
    {
        try {
            $stmt = $this->getCon()->prepare("SELECT * FROM " . $this->table);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }
    }

    public function listWhere(string $coluna, $condicao)
    {
        try {
            $stmt = $this->getCon()->prepare("SELECT * FROM " . $this->table . " WHERE " . $coluna . " = ?");
            $stmt = SqlHelper::Sql_prep($stmt, array($condicao));
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }
    }

    public function getOne(string $coluna, $condicao) {
        try {
            $stmt = $this->getCon()->prepare("SELECT * FROM " . $this->table . " WHERE " . $coluna . " = ?");
            $stmt = SqlHelper::Sql_prep($stmt, array($condicao));
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }
    }

    public function getOneById(int $id) {
        try {
            $stmt = $this->getCon()->prepare("SELECT * FROM " . $this->table . " WHERE id = ?");
            $stmt = SqlHelper::Sql_prep($stmt, array($id));
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }
    }

    public function delete(string $coluna, $condicao) {
        try {
            $stmt = $this->getCon()->prepare("DELETE FROM " . $this->table . " WHERE " . $coluna . " = ?");
            $stmt = SqlHelper::Sql_prep($stmt, array($condicao));
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }
    }

    public function update(string $coluna, $condicao, array $coluna_alteracao, array $novo_valor) {
        $str_updete_col = SqlHelper::Sql_prep_updete($coluna_alteracao);
        array_push($novo_valor, $condicao);
        try {
            $stmt = $this->getCon()->prepare("UPDATE " . $this->table . " SET " . $str_updete_col ." WHERE ". $coluna . " = ?");
            $stmt = SqlHelper::Sql_prep($stmt, $novo_valor);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Erro: ".$e->getMessage();
            return false;
        }
    }
}

?>