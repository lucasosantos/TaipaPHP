<?php

use App\core\Config;

/**
 * Redireciona para rota interna (SEGURO)
 * 
 * @param string $path Caminho da rota (sem domínio)
 * @param int $statusCode Código HTTP
 */
function redirect(string $path, int $statusCode = 302): void
{
    // Remove domínios para evitar open redirect
    $path = parse_url($path, PHP_URL_PATH) ?? '/';
    
    // Sanitiza o caminho
    $path = filter_var($path, FILTER_SANITIZE_URL);
    
    // Garante que começa com /
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }

    $config = Config::getInstance();
    $url = $config->get('app.url') . $path;

    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Redireciona para URL externa (COM VALIDAÇÃO)
 * 
 * @param string $url URL completa
 * @param array<string> $allowedDomains Domínios permitidos
 */
function redirectExternal(string $url, array $allowedDomains = []): void
{
    // Valida URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('URL inválida');
    }

    // Verifica domínios permitidos
    if (!empty($allowedDomains)) {
        $host = parse_url($url, PHP_URL_HOST);
        
        $allowed = false;
        foreach ($allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            throw new InvalidArgumentException('Domínio não permitido para redirect');
        }
    }

    header("Location: {$url}", true, 302);
    exit;
}

/**
 * Renderiza view com proteção XSS
 * 
 * @param string $view Nome da view (ex: 'home.index')
 * @param array $data Dados para a view
 * @param string $template Nome do template
 * @param int $statusCode Código HTTP
 */
function view(
    string $view,
    array $data = [],
    string $template = 'template',
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    $config = Config::getInstance();
    $viewsPath = $config->get('paths.views');

    // Converte notação de ponto em caminho
    $viewPath = str_replace('.', DIRECTORY_SEPARATOR, $view);
    
    // Previne path traversal
    if (str_contains($viewPath, '..')) {
        throw new InvalidArgumentException('Path traversal detectado');
    }

    $viewFile = $viewsPath . '/pages/' . $viewPath . '.php';
    $templateFile = $viewsPath . '/templates/' . $template . '.php';

    // Verifica existência
    if (!file_exists($viewFile)) {
        if ($config->isDebug()) {
            throw new RuntimeException("View não encontrada: {$viewFile}");
        }
        http_response_code(404);
        return;
    }

    $page = $viewFile;
    
    if (!isset($data['title'])) {
        $data['title'] = $config->get('app.name');
    }

    // Extrai variáveis com segurança (sem sobrescrever variáveis existentes)
    extract($data, EXTR_SKIP);

    // Variáveis helper disponíveis na view
    $app = $config;

    if (file_exists($templateFile)) {
        require $templateFile;
    } else {
        require $viewFile;
    }
}

/**
 * Inclui componente com proteção
 * 
 * @param string $name Nome do componente (ex: 'navbar' ou 'admin.header')
 * @param array $data Dados para o componente
 */
function component(string $name, array $data = []): void
{
    $config = Config::getInstance();
    $viewsPath = $config->get('paths.views');

    // Converte notação de ponto
    $componentPath = str_replace('.', DIRECTORY_SEPARATOR, $name);
    
    // Previne path traversal
    if (str_contains($componentPath, '..')) {
        throw new InvalidArgumentException('Path traversal detectado');
    }

    $file = $viewsPath . '/components/' . $componentPath . '.php';

    if (!file_exists($file)) {
        if ($config->isDebug()) {
            echo "<!-- Componente não encontrado: {$name} -->";
        }
        return;
    }

    extract($data, EXTR_SKIP);
    require $file;
}

/**
 * Retorna URL de asset com cache busting
 * 
 * @param string $path Caminho do asset (ex: 'css/style.css')
 * @return string URL completa
 */
function asset(string $path): string
{
    $config = Config::getInstance();
    
    // Remove barras iniciais
    $path = ltrim($path, '/');
    
    // Sanitiza
    $path = filter_var($path, FILTER_SANITIZE_URL);
    
    // Previne path traversal
    if (str_contains($path, '..')) {
        throw new InvalidArgumentException('Path traversal detectado em asset');
    }

    $baseUrl = $config->get('app.url');
    $fullPath = $config->get('paths.root') . '/assets/' . $path;

    // Cache busting com filemtime
    $version = '';
    if (file_exists($fullPath)) {
        $version = '?v=' . filemtime($fullPath);
    }

    return $baseUrl . '/assets/' . $path . $version;
}

/**
 * Escapa output HTML (proteção XSS)
 * 
 * @param string|null $value
 * @return string
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Obtém input POST com validação
 * 
 * @param string|null $key Chave específica ou null para todos
 * @param mixed $default Valor padrão
 * @return mixed
 */
function post(?string $key = null, $default = null)
{
    if ($key === null) {
        return filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS) ?? [];
    }

    $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    return $value ?? $default;
}

/**
 * Obtém input GET com validação
 */
function get(?string $key = null, $default = null)
{
    if ($key === null) {
        return filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS) ?? [];
    }

    $value = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    return $value ?? $default;
}

/**
 * Obtém JSON do body (para APIs)
 * 
 * @return array
 */
function jsonInput(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('JSON inválido: ' . json_last_error_msg());
    }

    return $data ?? [];
}

/**
 * Retorna resposta JSON
 * 
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Flash message (exibida uma vez)
 * 
 * @param string $key
 * @param string|null $message
 * @return string|null
 */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/**
 * Gera token CSRF
 * 
 * @return string
 */
function csrfToken(): string
{
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Campo input CSRF para formulários
 * 
 * @return string HTML
 */
function csrfField(): string
{
    $token = csrfToken();
    return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
}

/**
 * Valida token CSRF
 * 
 * @param string|null $token
 * @return bool
 */
function csrfVerify(?string $token): bool
{
    $sessionToken = $_SESSION['_csrf_token'] ?? null;
    
    if ($sessionToken === null || $token === null) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Gera URL para rota nomeada
 * 
 * @param string $path
 * @param array $params
 * @return string
 */
function url(string $path = '', array $params = []): string
{
    $config = Config::getInstance();
    $base = $config->get('app.url');
    
    $path = ltrim($path, '/');
    $url = $base . '/' . $path;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

/**
 * Dump and die (apenas desenvolvimento)
 */
function dd(...$vars): void
{
    $config = Config::getInstance();
    
    if (!$config->isDebug()) {
        error_log('dd() chamado em produção!');
        return;
    }

    echo '<pre style="background: #000; color: #0f0; padding: 20px; margin: 20px;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Verifica se requisição é AJAX
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Verifica se requisição é POST
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Verifica se requisição é GET
 */
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Obtém IP real do cliente
 */
function getClientIp(): string
{
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            
            // Se tiver múltiplos IPs, pega o primeiro
            if (str_contains($ip, ',')) {
                $ip = explode(',', $ip)[0];
            }

            $ip = trim($ip);
            
            // Valida IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}