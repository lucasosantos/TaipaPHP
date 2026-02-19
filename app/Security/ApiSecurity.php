<?php
namespace App\Security;

use App\Core\Config;

/**
 * ApiSecurity - Segurança específica para API
 * 
 * Usa: JWT, Bearer Token, API Keys
 */
class ApiSecurity
{
    /**
     * Usuário atual (via JWT)
     */
    private static ?array $currentUser = null;
    
    /**
     * Flag para controlar se já tentou autenticar
     */
    private static bool $authenticated = false;

    /**
     * Gera token JWT
     */
    public static function generateJWT(array $payload): string
    {
        $config = Config::getInstance();
        
        $header = [
            'typ' => 'JWT',
            'alg' => $config->get('security.jwt_algorithm', 'HS256')
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $config->get('security.jwt_expiration', 3600);

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "{$base64Header}.{$base64Payload}",
            $config->get('security.jwt_secret'),
            true
        );
        
        $base64Signature = self::base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    /**
     * Valida e decodifica JWT
     */
    public static function validateJWT(string $token): ?array
    {
        $config = Config::getInstance();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $signature = hash_hmac(
            'sha256',
            "{$base64Header}.{$base64Payload}",
            $config->get('security.jwt_secret'),
            true
        );

        $expectedSignature = self::base64UrlEncode($signature);

        if (!hash_equals($expectedSignature, $base64Signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
    
    /**
     * Extrai Bearer token do header Authorization
     */
    public static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Autentica via JWT (Bearer token)
     * 
     * ✅ Agora é silenciosa - não lança exceção, apenas retorna bool
     */
    public static function authenticate(): bool
    {
        // Evita reautenticar múltiplas vezes na mesma requisição
        if (self::$authenticated) {
            return self::$currentUser !== null;
        }
        
        self::$authenticated = true;
        
        $token = self::getBearerToken();
        
        if (!$token) {
            return false;
        }
        
        $payload = self::validateJWT($token);
        
        if (!$payload) {
            return false;
        }
        
        // Armazena dados do usuário
        self::$currentUser = $payload;
        
        return true;
    }
    
    /**
     * Obtém ID do usuário autenticado
     * Tenta autenticar automaticamente se ainda não autenticou
     */
    public static function getUserId(): ?int
    {
        // Se ainda não tentou autenticar, tenta agora
        if (!self::$authenticated) {
            self::authenticate();
        }
        
        return self::$currentUser['id'] ?? null;
    }
    
    /**
     * Obtém level do usuário
     */
    public static function getUserLevel(): ?string
    {
        if (!self::$authenticated) {
            self::authenticate();
        }
        
        return self::$currentUser['role'] ?? self::$currentUser['level'] ?? null;
    }
    
    /**
     * Obtém payload completo do JWT
     */
    public static function getUser(): ?array
    {
        if (!self::$authenticated) {
            self::authenticate();
        }
        
        return self::$currentUser;
    }
    
    /**
     * Verifica se usuário está autenticado
     */
    public static function isAuthenticated(): bool
    {
        if (!self::$authenticated) {
            self::authenticate();
        }
        
        return self::$currentUser !== null;
    }
    
    /**
     * Verifica se usuário tem determinada role/level
     */
    public static function hasRole(string $role): bool
    {
        $userRole = self::getUserLevel();
        return $userRole === $role;
    }
    
    /**
     * Verifica se usuário tem uma das roles fornecidas
     */
    public static function hasAnyRole(array $roles): bool
    {
        $userRole = self::getUserLevel();
        return in_array($userRole, $roles, true);
    }
    
    /**
     * Middleware: requer autenticação JWT
     */
    public static function requireAuth(): void
    {
        if (!self::authenticate()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Token inválido ou ausente',
                'message' => 'Autenticação requerida'
            ]);
            exit;
        }
    }
    
    /**
     * Middleware: requer level específico
     */
    public static function requireLevel(string $requiredLevel): void
    {
        self::requireAuth();
        
        if (self::getUserLevel() !== $requiredLevel) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Acesso negado',
                'message' => 'Permissão insuficiente'
            ]);
            exit;
        }
    }
    
    /**
     * Middleware: requer uma das roles fornecidas
     */
    public static function requireAnyRole(array $roles): void
    {
        self::requireAuth();
        
        if (!self::hasAnyRole($roles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Acesso negado',
                'message' => 'Permissão insuficiente'
            ]);
            exit;
        }
    }
    
    /**
     * Valida API Key
     */
    public static function validateApiKey(string $apiKey): bool
    {
        $config = Config::getInstance();
        $validKeys = $config->get('api.keys', []);
        
        return in_array($apiKey, $validKeys, true);
    }
    
    /**
     * Obtém API Key do header
     */
    public static function getApiKey(): ?string
    {
        return $_SERVER['HTTP_X_API_KEY'] ?? null;
    }
    
    /**
     * Middleware: requer API Key
     */
    public static function requireApiKey(): void
    {
        $apiKey = self::getApiKey();
        
        if (!$apiKey || !self::validateApiKey($apiKey)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'API Key inválida ou ausente'
            ]);
            exit;
        }
    }
    
    /**
     * Codifica base64 URL-safe
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica base64 URL-safe
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}