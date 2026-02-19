<?php
namespace App\Database;

use PDO;
use PDOStatement;

class QueryBuilder
{
    /**
     * Cria string de colunas para INSERT
     * 
     * @param array<string> $columns
     * @return string
     */
    public static function buildColumnList(array $columns): string
    {
        if (empty($columns)) {
            throw new \InvalidArgumentException('Lista de colunas não pode estar vazia');
        }

        // Valida e escapa nomes de colunas
        $sanitized = array_map(
            fn($col) => self::sanitizeIdentifier($col),
            $columns
        );

        return implode(', ', $sanitized);
    }

    /**
     * Cria string de placeholders para INSERT
     * 
     * @param int $count Número de valores
     * @return string Ex: "?, ?, ?"
     */
    public static function buildPlaceholders(int $count): string
    {
        if ($count <= 0) {
            throw new \InvalidArgumentException('Contagem de placeholders deve ser maior que zero');
        }

        return implode(', ', array_fill(0, $count, '?'));
    }

    /**
     * Vincula valores ao statement (CORRIGIDO - usa bindValue)
     * 
     * @param PDOStatement $stmt
     * @param array $values
     * @return PDOStatement
     */
    public static function bindValues(PDOStatement $stmt, array $values): PDOStatement
    {
        foreach ($values as $index => $value) {
            $position = $index + 1; // PDO usa índice 1-based
            $type = self::getPdoType($value);
            
            // USA bindValue ao invés de bindParam (corrige bug!)
            $stmt->bindValue($position, $value, $type);
        }

        return $stmt;
    }

    /**
     * Cria SET clause para UPDATE
     * 
     * @param array<string> $columns
     * @return string Ex: "name = ?, email = ?"
     */
    public static function buildUpdateSet(array $columns): string
    {
        if (empty($columns)) {
            throw new \InvalidArgumentException('Lista de colunas para UPDATE não pode estar vazia');
        }

        $sanitized = array_map(
            fn($col) => self::sanitizeIdentifier($col) . ' = ?',
            $columns
        );

        return implode(', ', $sanitized);
    }

    /**
     * Determina o tipo PDO apropriado para um valor
     * 
     * @param mixed $value
     * @return int
     */
    private static function getPdoType($value): int
    {
        return match (true) {
            is_null($value) => PDO::PARAM_NULL,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value) => PDO::PARAM_INT,
            default => PDO::PARAM_STR,
        };
    }

    /**
     * Sanitiza identificadores (tabelas, colunas)
     * Protege contra SQL injection em nomes
     * 
     * @param string $identifier
     * @return string
     */
    public static function sanitizeIdentifier(string $identifier): string
    {
        // Remove caracteres perigosos
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
        
        if ($sanitized === '') {
            throw new \InvalidArgumentException(
                "Identificador inválido: {$identifier}"
            );
        }

        // Escapa com backticks para MySQL
        return "`{$sanitized}`";
    }

    /**
     * Valida operador WHERE
     * 
     * @param string $operator
     * @return string
     */
    public static function sanitizeOperator(string $operator): string
    {
        $allowed = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
        
        $operator = strtoupper(trim($operator));
        
        if (!in_array($operator, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Operador não permitido: {$operator}"
            );
        }

        return $operator;
    }

    /**
     * Constrói cláusula WHERE segura
     * 
     * @param string $column
     * @param string $operator
     * @return string
     */
    public static function buildWhere(string $column, string $operator = '='): string
    {
        $column = self::sanitizeIdentifier($column);
        $operator = self::sanitizeOperator($operator);
        
        return "{$column} {$operator} ?";
    }

    /**
     * Constrói ORDER BY seguro
     * 
     * @param string $column
     * @param string $direction
     * @return string
     */
    public static function buildOrderBy(string $column, string $direction = 'ASC'): string
    {
        $column = self::sanitizeIdentifier($column);
        
        $direction = strtoupper(trim($direction));
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException(
                "Direção inválida: {$direction}. Use ASC ou DESC."
            );
        }

        return "{$column} {$direction}";
    }

    /**
     * Valida e sanitiza nome de tabela
     * 
     * @param string $table
     * @return string
     */
    public static function sanitizeTable(string $table): string
    {
        return self::sanitizeIdentifier($table);
    }

    /**
     * Constrói LIMIT com validação
     * 
     * @param int $limit
     * @param int|null $offset
     * @return string
     */
    public static function buildLimit(int $limit, ?int $offset = null): string
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('LIMIT não pode ser negativo');
        }

        if ($offset !== null && $offset < 0) {
            throw new \InvalidArgumentException('OFFSET não pode ser negativo');
        }

        $sql = "LIMIT {$limit}";
        
        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        return $sql;
    }

    /**
     * Executa query com valores vinculados
     * 
     * @param PDOStatement $stmt
     * @param array $values
     * @return bool
     */
    public static function execute(PDOStatement $stmt, array $values = []): bool
    {
        if (!empty($values)) {
            self::bindValues($stmt, $values);
        }

        return $stmt->execute();
    }

    /**
     * Helper para debug de queries (apenas desenvolvimento)
     * 
     * @param string $query
     * @param array $values
     * @return string
     */
    public static function debugQuery(string $query, array $values = []): string
    {
        $debug = $query;
        
        foreach ($values as $value) {
            $quoted = is_string($value) ? "'{$value}'" : $value;
            $debug = preg_replace('/\?/', (string)$quoted, $debug, 1);
        }

        return $debug;
    }
}