<?php
namespace App\Database;

use PDO;
use PDOException;
use App\Core\Config;

class Database
{
    private static ?PDO $connection = null;
    private static int $retryAttempts = 3;
    private static int $retryDelay = 100; // ms

    /**
     * Obtém conexão singleton com retry
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }

        return self::$connection;
    }

    /**
     * Cria nova conexão com retry logic
     */
    private static function createConnection(): PDO
    {
        $config = Config::getInstance();
        
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config->get('database.driver'),
            $config->get('database.host'),
            $config->get('database.port'),
            $config->get('database.database'),
            $config->get('database.charset')
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false, // Mudar para true se usar PHP-FPM
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        $lastException = null;
        
        for ($attempt = 1; $attempt <= self::$retryAttempts; $attempt++) {
            try {
                $pdo = new PDO(
                    $dsn,
                    $config->get('database.username'),
                    $config->get('database.password'),
                    $options
                );

                // Log de sucesso (apenas primeira vez)
                if ($attempt > 1) {
                    self::logInfo("Conexão estabelecida após {$attempt} tentativas");
                }

                return $pdo;

            } catch (PDOException $e) {
                $lastException = $e;
                
                // Não tenta novamente em erros de credencial
                if (self::isCredentialError($e)) {
                    self::logError('Erro de autenticação no banco de dados', $e);
                    throw new DatabaseException(
                        'Não foi possível conectar ao banco de dados. Verifique as credenciais.',
                        0,
                        $e
                    );
                }

                // Aguarda antes de tentar novamente
                if ($attempt < self::$retryAttempts) {
                    usleep(self::$retryDelay * 1000);
                    self::logWarning("Tentativa {$attempt} falhou. Tentando novamente...");
                }
            }
        }

        // Todas as tentativas falharam
        self::logError('Falha ao conectar após múltiplas tentativas', $lastException);
        
        throw new DatabaseException(
            'Não foi possível conectar ao banco de dados após ' . self::$retryAttempts . ' tentativas.',
            0,
            $lastException
        );
    }

    /**
     * Verifica se é erro de credencial
     */
    private static function isCredentialError(PDOException $e): bool
    {
        $credentialErrors = [
            1045, // Access denied
            1044, // Access denied for user to database
            1049, // Unknown database
        ];

        $code = (int) $e->getCode();
        return in_array($code, $credentialErrors, true);
    }

    /**
     * Testa se a conexão está ativa
     */
    public static function isConnected(): bool
    {
        if (self::$connection === null) {
            return false;
        }

        try {
            self::$connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Reconecta se necessário
     */
    public static function reconnectIfNeeded(): void
    {
        if (!self::isConnected()) {
            self::$connection = null;
            self::getConnection();
        }
    }

    /**
     * Fecha conexão (útil para testes)
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }

    /**
     * Inicia transação
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit de transação
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback de transação
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Executa callback dentro de transação
     */
    public static function transaction(callable $callback)
    {
        try {
            self::beginTransaction();
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Logging seguro (não expõe credenciais)
     */
    private static function logError(string $message, ?\Exception $e = null): void
    {
        $config = Config::getInstance();
        
        if ($config->isDebug()) {
            error_log("[Database Error] {$message}");
            if ($e) {
                error_log("[Database Error] " . $e->getMessage());
            }
        } else {
            // Em produção, log genérico
            error_log("[Database Error] Erro de conexão com banco de dados");
        }
    }

    private static function logWarning(string $message): void
    {
        error_log("[Database Warning] {$message}");
    }

    private static function logInfo(string $message): void
    {
        error_log("[Database Info] {$message}");
    }
}

/**
 * Exceção customizada para erros de banco
 */
class DatabaseException extends \RuntimeException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        // Remove informações sensíveis da mensagem
        $safeMessage = $this->sanitizeMessage($message);
        parent::__construct($safeMessage, $code, $previous);
    }

    private function sanitizeMessage(string $message): string
    {
        // Remove possíveis credenciais ou informações sensíveis
        $patterns = [
            '/password[\'"]?\s*[:=]\s*[\'"]?[^\s\'"]+/i',
            '/IDENTIFIED BY[^;]+/i',
        ];

        foreach ($patterns as $pattern) {
            $message = preg_replace($pattern, '[REDACTED]', $message);
        }

        return $message;
    }
}