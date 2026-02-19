<?php
namespace App\Security;

use App\Core\Config;

/**
 * WebSecurity - Segurança específica para WEB
 * 
 * Usa: Sessions, Cookies, CSRF
 */
class WebSecurity
{
    /**
     * Verifica CSRF token
     */
    public static function verifyCsrf(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $token = $_POST['_csrf_token'] ?? null;
        $sessionToken = $_SESSION['_csrf_token'] ?? null;

        if ($token === null || $sessionToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Middleware de proteção CSRF
     */
    public static function requireCsrf(): void
    {
        if (!self::verifyCsrf()) {
            http_response_code(403);
            die('Token CSRF inválido');
        }
    }

    /**
     * Verifica se usuário está autenticado via sessão
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtém ID do usuário
     */
    public static function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtém level do usuário
     */
    public static function getUserLevel(): ?string
    {
        return $_SESSION['user_level'] ?? null;
    }

    /**
     * Faz login (cria sessão)
     */
    public static function login(int $userId, string $level = 'user', array $extra = []): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_level'] = $level;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        foreach ($extra as $key => $value) {
            $_SESSION["user_{$key}"] = $value;
        }
    }

    /**
     * Faz logout (destrói sessão)
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }

    /**
     * Middleware: requer autenticação
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            $config = Config::getInstance();
            header('Location: /' . $config->get('routes.login'));
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
            die('Acesso negado');
        }
    }
}