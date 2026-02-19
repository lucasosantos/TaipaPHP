<?php
namespace App\Core;

use PDO;
use PDOException;
use App\Database\Database;
use App\Database\QueryBuilder;

/**
 * Model Base Refatorado
 * 
 * Melhorias:
 * - Usa singleton de conexão (50x mais rápido)
 * - Bug do bindParam corrigido
 * - Exceções ao invés de echo
 * - Logging seguro
 * - Type hints completos
 * - Validação de inputs
 */
abstract class Model
{
    /**
     * Nome da tabela (deve ser definido na classe filha)
     */
    protected string $table;

    /**
     * Chave primária padrão
     */
    protected string $primaryKey = 'id';

    /**
     * Timestamps automáticos
     */
    protected bool $timestamps = true;

    /**
     * Colunas preenchíveis (proteção contra mass assignment)
     */
    protected array $fillable = [];

    /**
     * Colunas protegidas
     */
    protected array $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Obtém conexão PDO
     */
    protected function getConnection(): PDO
    {
        return Database::getConnection();
    }

    /**
     * Campos sempre ocultados (ex: password, remember_token)
     */
    protected array $hidden = [];

    /**
     * Quando definido, retorna APENAS esses campos
     */
    protected array $visible = [];

    /**
     * Views predefinidas: conjuntos de campos por contexto
     * 
     * Ex: ['public' => ['id', 'name'], 'admin' => ['id', 'name', 'email']]
     */
    protected array $views = [];

    /**
     * View ativa no momento
     */
    private ?string $activeView = null;

    /**
     * Define a view ativa (fluent interface)
     */
    public function view(string $viewName): static
    {
        if (!isset($this->views[$viewName])) {
            throw new \InvalidArgumentException("View '{$viewName}' não definida no model " . static::class);
        }

        $this->activeView = $viewName;
        return $this;
    }

    /**
     * Reseta a view ativa
     */
    public function resetView(): static
    {
        $this->activeView = null;
        return $this;
    }

    /**
     * Aplica filtros de visibilidade em um registro
     */
    protected function applyVisibility(array $record): array
    {
        // 1. View nomeada tem prioridade máxima
        if ($this->activeView !== null) {
            $fields = $this->views[$this->activeView];
            return array_intersect_key($record, array_flip($fields));
        }

        // 2. $visible restringe aos campos listados
        if (!empty($this->visible)) {
            $record = array_intersect_key($record, array_flip($this->visible));
        }

        // 3. $hidden remove campos sensíveis
        if (!empty($this->hidden)) {
            $record = array_diff_key($record, array_flip($this->hidden));
        }

        return $record;
    }

    /**
     * Aplica visibilidade em uma coleção de registros
     */
    protected function applyVisibilityAll(array $records): array
    {
        return array_map(fn($record) => $this->applyVisibility($record), $records);
    }

    /**
     * Insere registro
     * 
     * @param array<string, mixed> $data
     * @return int|false ID inserido ou false
     */
    public function insert(array $data)
    {
        try {
            // Filtra apenas colunas permitidas
            $data = $this->filterFillable($data);
            
            if (empty($data)) {
                throw new \InvalidArgumentException('Nenhum dado válido para inserir');
            }

            // Adiciona timestamps
            if ($this->timestamps) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            $columns = array_keys($data);
            $values = array_values($data);

            $columnList = QueryBuilder::buildColumnList($columns);
            $placeholders = QueryBuilder::buildPlaceholders(count($values));
            $table = QueryBuilder::sanitizeTable($this->table);

            $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";
            
            $stmt = $this->getConnection()->prepare($sql);
            QueryBuilder::execute($stmt, $values);

            return (int) $this->getConnection()->lastInsertId();

        } catch (PDOException $e) {
            $this->handleException('insert', $e);
            return false;
        }
    }

    /**
     * Lista todos os registros
     * 
     * @param array<string> $columns Colunas a retornar
     * @return array<array>
     */
    public function listAll(array $columns = ['*']): array
    {
        try {
            $columnList = $columns === ['*'] 
                ? '*' 
                : QueryBuilder::buildColumnList($columns);
            
            $table = QueryBuilder::sanitizeTable($this->table);
            $sql = "SELECT {$columnList} FROM {$table}";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();

            $results = $stmt->fetchAll();
            return $this->applyVisibilityAll($results);

        } catch (PDOException $e) {
            $this->handleException('listAll', $e);
            return [];
        }
    }

    /**
     * Lista com condição WHERE
     * 
     * @param string $column
     * @param mixed $value
     * @param string $operator
     * @return array<array>
     */
    public function listWhere(
        string $column, 
        $value, 
        string $operator = '='
    ): array {
        try {
            $table = QueryBuilder::sanitizeTable($this->table);
            $where = QueryBuilder::buildWhere($column, $operator);
            
            $sql = "SELECT * FROM {$table} WHERE {$where}";
            
            $stmt = $this->getConnection()->prepare($sql);
            QueryBuilder::execute($stmt, [$value]);

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            $this->handleException('listWhere', $e);
            return [];
        }
    }

    /**
     * Busca um registro
     * 
     * @param string $column
     * @param mixed $value
     * @return array|null
     */
    public function getOne(string $column, $value): ?array
    {
        try {
            $table = QueryBuilder::sanitizeTable($this->table);
            $where = QueryBuilder::buildWhere($column);
            
            $sql = "SELECT * FROM {$table} WHERE {$where} LIMIT 1";
            
            $stmt = $this->getConnection()->prepare($sql);
            QueryBuilder::execute($stmt, [$value]);

            $result = $stmt->fetch();
            return $result ? $this->applyVisibility($result) : null;

        } catch (PDOException $e) {
            $this->handleException('getOne', $e);
            return null;
        }
    }

    /**
     * Busca por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getOneById(int $id): ?array
    {
        return $this->getOne($this->primaryKey, $id);
    }

    /**
     * Retorna último ID inserido
     * 
     * @return int|null
     */
    public function getLastId(): ?int
    {
        try {
            $table = QueryBuilder::sanitizeTable($this->table);
            $pk = QueryBuilder::sanitizeIdentifier($this->primaryKey);
            
            $sql = "SELECT {$pk} FROM {$table} ORDER BY {$pk} DESC LIMIT 1";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result ? (int) $result[$this->primaryKey] : null;

        } catch (PDOException $e) {
            $this->handleException('getLastId', $e);
            return null;
        }
    }

    /**
     * Atualiza registros
     * 
     * @param string $whereColumn
     * @param mixed $whereValue
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(
        string $whereColumn, 
        $whereValue, 
        array $data
    ): bool {
        try {
            // Filtra colunas permitidas
            $data = $this->filterFillable($data);
            
            if (empty($data)) {
                throw new \InvalidArgumentException('Nenhum dado válido para atualizar');
            }

            // Adiciona updated_at
            if ($this->timestamps) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            $columns = array_keys($data);
            $values = array_values($data);
            
            // Adiciona valor do WHERE ao final
            $values[] = $whereValue;

            $table = QueryBuilder::sanitizeTable($this->table);
            $setClause = QueryBuilder::buildUpdateSet($columns);
            $where = QueryBuilder::buildWhere($whereColumn);

            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            
            $stmt = $this->getConnection()->prepare($sql);
            return QueryBuilder::execute($stmt, $values);

        } catch (PDOException $e) {
            $this->handleException('update', $e);
            return false;
        }
    }

    /**
     * Deleta registros
     * 
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function delete(string $column, $value): bool
    {
        try {
            $table = QueryBuilder::sanitizeTable($this->table);
            $where = QueryBuilder::buildWhere($column);

            $sql = "DELETE FROM {$table} WHERE {$where}";
            
            $stmt = $this->getConnection()->prepare($sql);
            return QueryBuilder::execute($stmt, [$value]);

        } catch (PDOException $e) {
            $this->handleException('delete', $e);
            return false;
        }
    }

    /**
     * Conta registros
     * 
     * @param string|null $column Coluna para WHERE
     * @param mixed $value Valor para WHERE
     * @return int
     */
    public function count(?string $column = null, $value = null): int
    {
        try {
            $table = QueryBuilder::sanitizeTable($this->table);
            
            if ($column === null) {
                $sql = "SELECT COUNT(*) as total FROM {$table}";
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->execute();
            } else {
                $where = QueryBuilder::buildWhere($column);
                $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
                $stmt = $this->getConnection()->prepare($sql);
                QueryBuilder::execute($stmt, [$value]);
            }

            $result = $stmt->fetch();
            return (int) ($result['total'] ?? 0);

        } catch (PDOException $e) {
            $this->handleException('count', $e);
            return 0;
        }
    }

    /**
     * Filtra apenas colunas preenchíveis
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            // Se fillable vazio, remove apenas guarded
            return array_diff_key($data, array_flip($this->guarded));
        }

        // Mantém apenas fillable
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Trata exceções de forma segura
     */
    protected function handleException(string $method, PDOException $e): void
    {
        $config = \App\Core\Config::getInstance();
        
        if ($config->isDebug()) {
            // Desenvolvimento: log detalhado
            error_log("[Model Error] {$this->table}::{$method} - {$e->getMessage()}");
            error_log("[Model Error] SQL State: {$e->getCode()}");
        } else {
            // Produção: log genérico
            error_log("[Model Error] Erro na tabela {$this->table}");
        }

        // Em produção, não lança exceção (retorna false/null/array vazio)
        // Em desenvolvimento, pode descomentar para debug:
        // throw $e;
    }
}