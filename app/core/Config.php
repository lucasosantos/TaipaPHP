<?php
namespace App\Core;

class Config
{
    private static ?self $instance = null;
    private array $config = [];
    private string $environment;

    private function __construct()
    {
        $this->detectEnvironment();
        $this->loadConfig();
        $this->validateConfig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Detecta ambiente corretamente
     */
    private function detectEnvironment(): void
    {
        // Prioridade: ENV var > HTTP_HOST
        $this->environment = $_ENV['APP_ENV'] ?? 'production';

        // Fallback para detecção por host (corrigido)
        if ($this->environment === 'production') {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (
                strpos($host, 'localhost') !== false ||
                strpos($host, '127.0.0.1') !== false ||
                strpos($host, '.local') !== false
            ) {
                $this->environment = 'development';
            }
        }
    }

    /**
     * Carrega configurações do ambiente
     */
    public function loadConfig(): void
    {
        $this->config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Taipa PHP',
                'env' => $this->environment,
                'debug' => $_ENV['APP_DEBUG'] === 'true',
                'url' => $this->getAppUrl(),
            ],
            'security' => [
                'jwt_secret' => $this->getJwtSecret(),
                'jwt_algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
                'jwt_expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 3600),
                'password_algorithm' => PASSWORD_ARGON2ID,
                'csrf_token_name' => 'csrf_token',
                'session_name' => 'taipa_session',
            ],
            'database' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['DB_PORT'] ?? 3306),
                'database' => $_ENV['DB_NAME'] ?? '',
                'username' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'routes' => [
                'dashboard' => $_ENV['ROUTE_DASHBOARD'] ?? 'painel',
                'login' => $_ENV['ROUTE_LOGIN'] ?? 'login',
                'logout' => $_ENV['ROUTE_LOGOUT'] ?? 'logout',
                'register' => $_ENV['ROUTE_REGISTER'] ?? 'register',
            ],
            'paths' => [
                'root' => dirname(__DIR__, 2),
                'app' => dirname(__DIR__),
                'views' => dirname(__DIR__) . '/views',
                'controllers' => dirname(__DIR__) . '/controllers',
                'models' => dirname(__DIR__) . '/models',
            ],
        ];
    }

    /**
     * Gera ou recupera chave JWT segura
     */
    private function getJwtSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? null;

        // CRÍTICO: Nunca usar chave padrão em produção
        if (empty($secret)) {
            if ($this->environment === 'production') {
                throw new \RuntimeException(
                    'JWT_SECRET não configurado! Defina no .env'
                );
            }
            
            // Apenas em desenvolvimento, gera chave temporária
            $secret = bin2hex(random_bytes(32));
            
            // Aviso no log
            error_log(
                'AVISO: Usando JWT_SECRET gerado automaticamente. ' .
                'Configure JWT_SECRET no .env para produção!'
            );
        }

        // Validação de força mínima
        if (strlen($secret) < 32) {
            throw new \RuntimeException(
                'JWT_SECRET muito curto! Use no mínimo 32 caracteres.'
            );
        }

        return $secret;
    }

    /**
     * Constrói URL da aplicação de forma segura
     */
    private function getAppUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            ? 'https' 
            : 'http';
        
        // Em produção, sempre HTTPS
        if ($this->environment === 'production' && $protocol === 'http') {
            error_log('AVISO: Aplicação em produção sem HTTPS!');
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Sanitiza host
        $host = filter_var($host, FILTER_SANITIZE_URL);
        
        return "{$protocol}://{$host}";
    }

    /**
     * Valida configurações críticas
     */
    private function validateConfig(): void
    {
        $required = [
            'database.host',
            'database.database',
            'database.username',
        ];

        foreach ($required as $key) {
            if (empty($this->get($key))) {
                throw new \RuntimeException(
                    "Configuração obrigatória ausente: {$key}"
                );
            }
        }

        // Valida algoritmo JWT
        $validAlgorithms = ['HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512'];
        $algo = $this->get('security.jwt_algorithm');
        if (!in_array($algo, $validAlgorithms, true)) {
            throw new \RuntimeException(
                "Algoritmo JWT inválido: {$algo}"
            );
        }
    }

    /**
     * Obtém configuração usando notação de ponto
     * 
     * @param string $key Ex: 'database.host'
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Define configuração em runtime (útil para testes)
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Verifica se está em ambiente de desenvolvimento
     */
    public function isDebug(): bool
    {
        return $this->config['app']['debug'];
    }

    /**
     * Verifica se está em produção
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Retorna todo o array de configuração (use com cuidado)
     */
    public function all(): array
    {
        return $this->config;
    }
}

/**
 * Helper global para acessar configurações
 */
function config(?string $key = null, mixed $default = null): mixed
{
    $config = Config::getInstance();
    
    if ($key === null) {
        return $config;
    }
    
    return $config->get($key, $default);
}