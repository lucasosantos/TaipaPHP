<?php
namespace App\Security;

use App\Core\Config;

/**
 * SecurityService - Base Comum
 * 
 * Métodos compartilhados entre WEB e API
 */
class Security
{
    /**
     * Aplica headers de segurança (comum para WEB e API)
     */
    public static function applySecurityHeaders(): void
    {
        // Headers comuns
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // HSTS (apenas em HTTPS)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Headers específicos por contexto
        $config = Config::getInstance();
        $mode = $config->get('app.mode', 'web');
        
        if ($mode === 'api' || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            self::applyApiHeaders();
        } else {
            self::applyWebHeaders();
        }
    }
    
    /**
     * Headers específicos de API
     */
    private static function applyApiHeaders(): void
    {
        $config = Config::getInstance();
        
        // CORS (se habilitado)
        if ($config->get('api.cors_enabled', false)) {
            $allowedOrigins = $config->get('api.cors_origins', ['*']);
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            
            if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
                header('Access-Control-Max-Age: 86400'); // 24 horas
            }
        }
        
        // Sem cache em API
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    
    /**
     * Headers específicos de WEB
     */
    private static function applyWebHeaders(): void
    {
        // XSS Protection (browsers antigos)
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'"
        ]);
        header("Content-Security-Policy: {$csp}");
        
        // Cache apenas em páginas públicas
        if (!self::isAuthenticatedPage()) {
            header('Cache-Control: public, max-age=3600');
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
    }
    
    /**
     * Sanitização (comum)
     */
    public static function sanitizeInput(string $input, string $type = 'string'): string
    {
        $input = trim($input);

        return match($type) {
            'email' => filter_var($input, FILTER_SANITIZE_EMAIL),
            'url' => filter_var($input, FILTER_SANITIZE_URL),
            'int' => filter_var($input, FILTER_SANITIZE_NUMBER_INT),
            'float' => filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            default => htmlspecialchars($input, ENT_QUOTES, 'UTF-8'),
        };
    }
    
    /**
     * Validação de email (comum)
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Hash de senha (comum)
     */
    public static function hashPassword(string $password): string
    {
        $config = Config::getInstance();
        $algorithm = $config->get('security.password_algorithm');
        
        return password_hash($password, $algorithm);
    }
    
    /**
     * Verificação de senha (comum)
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Validação de força de senha (comum)
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Senha deve ter no mínimo 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra maiúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos um número';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos um caractere especial';
        }

        return $errors;
    }
    
    /**
     * Verifica se precisa rehash (comum)
     */
    public static function needsRehash(string $hash): bool
    {
        $config = Config::getInstance();
        $algorithm = $config->get('security.password_algorithm');
        
        return password_needs_rehash($hash, $algorithm);
    }
    
    /**
     * Rate limiting (comum)
     */
    public static function rateLimit(
        string $key, 
        int $maxAttempts = 5, 
        int $windowSeconds = 300,
        string $storage = 'session' // 'session' ou 'cache'
    ): bool {
        if ($storage === 'session') {
            return self::rateLimitSession($key, $maxAttempts, $windowSeconds);
        }
        
        // Futuramente: cache (Redis, Memcached)
        return self::rateLimitSession($key, $maxAttempts, $windowSeconds);
    }
    
    /**
     * Rate limiting via sessão
     */
    private static function rateLimitSession(
        string $key, 
        int $maxAttempts, 
        int $windowSeconds
    ): bool {
        $storageKey = 'rate_limit_' . md5($key);
        $now = time();

        if (!isset($_SESSION[$storageKey])) {
            $_SESSION[$storageKey] = ['attempts' => [], 'blocked_until' => null];
        }

        $data = &$_SESSION[$storageKey];

        if ($data['blocked_until'] !== null && $now < $data['blocked_until']) {
            return false;
        }

        $data['attempts'] = array_filter(
            $data['attempts'],
            fn($timestamp) => $timestamp > ($now - $windowSeconds)
        );

        if (count($data['attempts']) >= $maxAttempts) {
            $data['blocked_until'] = $now + $windowSeconds;
            return false;
        }

        $data['attempts'][] = $now;
        return true;
    }
    
    /**
     * Limpa rate limit
     */
    public static function clearRateLimit(string $key): void
    {
        $storageKey = 'rate_limit_' . md5($key);
        unset($_SESSION[$storageKey]);
    }
    
    /**
     * Verifica se é página autenticada
     */
    private static function isAuthenticatedPage(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $config = Config::getInstance();
        
        $protectedPaths = [
            $config->get('routes.dashboard'),
            '/admin',
            '/painel',
            '/perfil',
        ];

        foreach ($protectedPaths as $path) {
            if (str_contains($uri, $path)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Delega para WebSecurity ou ApiSecurity baseado no contexto
     */
    public static function authenticate(): bool
    {
        if (self::isApiContext()) {
            return ApiSecurity::authenticate();
        }
        return WebSecurity::isAuthenticated();
    }
    
    /**
     * Verifica se está em contexto API
     */
    private static function isApiContext(): bool
    {
        $config = Config::getInstance();
        $mode = $config->get('app.mode', 'web');
        
        return $mode === 'api' || 
               str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/') ||
               str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}