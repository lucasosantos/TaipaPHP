<?php
/**
 * Bootstrap - Taipa PHP (Sistema Dual API/WEB)
 */

use Dotenv\Dotenv;
use App\Core\Config;
use App\Core\Application;
use App\Security\Security;
use App\Security\WebSecurity;

// ==========================================
// CONSTANTES E AUTOLOAD
// ==========================================

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');

require_once ROOT_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/helpers/helpers.php';

// ==========================================
// DETECÇÃO DE CONTEXTO (API vs WEB)
// ==========================================

/**
 * Detecta se requisição é API ou WEB
 */
function isApiContext(): bool
{
    // 1. Por URL (/api/*)
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (str_starts_with($uri, '/api/')) {
        return true;
    }
    
    // 2. Por Accept header
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json') && 
        !str_contains($accept, 'text/html')) {
        return true;
    }
    
    // 3. Por Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        return true;
    }
    
    return false;
}

$isApi = isApiContext();

// ==========================================
// CARREGAMENTO DE AMBIENTE
// ==========================================

try {
    // Detecta ambiente
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = (
        str_contains($host, 'localhost') ||
        str_contains($host, '127.0.0.1') ||
        str_contains($host, '.local')
    );

    $envFile = ($isLocal && file_exists(ROOT_PATH . '/.env.dev')) 
        ? '.env.dev' 
        : '.env';

    // Carrega .env
    $dotenv = Dotenv::createImmutable(ROOT_PATH, $envFile);
    $dotenv->load();

    // Valida variáveis obrigatórias
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER']);

} catch (Exception $e) {
    error_log('[Bootstrap] Erro ao carregar .env: ' . $e->getMessage());
    
    if ($isApi) {
        // Resposta JSON para API
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Erro de configuração do servidor'
        ]);
    } else {
        // Resposta HTML para WEB
        http_response_code(500);
        echo 'Erro interno do servidor. Contate o administrador.';
    }
    exit(1);
}

// ==========================================
// CONFIGURAÇÃO DE ERROS
// ==========================================

$config = Config::getInstance();

if ($config->isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// Handler de erros
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($config, $isApi) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $error = "[Error] {$errstr} in {$errfile}:{$errline}";
    error_log($error);

    if ($config->isDebug()) {
        if ($isApi) {
            // Não exibe erro em JSON durante request
            // Será capturado pelo exception handler
        } else {
            echo "<pre style='background:#f00;color:#fff;padding:10px'>{$error}</pre>";
        }
    }

    return true;
});

// Handler de exceções
set_exception_handler(function($exception) use ($config, $isApi) {
    error_log('[Exception] ' . $exception->getMessage());
    error_log('[Trace] ' . $exception->getTraceAsString());

    http_response_code(500);

    if ($isApi) {
        // Resposta JSON para API
        header('Content-Type: application/json');
        
        if ($config->isDebug()) {
            echo json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString())
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    } else {
        // Resposta HTML para WEB
        if ($config->isDebug()) {
            echo '<pre style="background:#000;color:#0f0;padding:20px">';
            echo 'Exceção: ' . $exception->getMessage() . "\n\n";
            echo 'Arquivo: ' . $exception->getFile() . ':' . $exception->getLine() . "\n\n";
            echo $exception->getTraceAsString();
            echo '</pre>';
        } else {
            echo 'Erro interno do servidor. Tente novamente mais tarde.';
        }
    }

    exit(1);
});

// ==========================================
// CONFIGURAÇÃO DE SESSÃO
// ==========================================

if (!$isApi && session_status() === PHP_SESSION_NONE) {
    // Configurações de sessão seguras
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    // HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    // Nome da sessão
    session_name($config->get('security.session_name', 'taipa_session'));

    // Inicia sessão
    session_start();

    // Session fixation prevention
    if (!isset($_SESSION['_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['_initiated'] = true;
        $_SESSION['_created'] = time();
    }

    // Session timeout (30 minutos)
    $sessionTimeout = 1800;
    if (isset($_SESSION['_last_activity']) && 
        (time() - $_SESSION['_last_activity'] > $sessionTimeout)) {
        
        WebSecurity::logout();
        
        // Redireciona para login
        $loginRoute = $config->get('routes.login', 'login');
        header('Location: /' . $loginRoute);
        exit;
    }
    $_SESSION['_last_activity'] = time();
}

// ==========================================
// HEADERS DE SEGURANÇA
// ==========================================

Security::applySecurityHeaders();

// ==========================================
// APPLICATION CLASS
// ==========================================

if (class_exists('App\Core\Application')) {
    $app = new Application();
    $app->run();
    exit;
}
