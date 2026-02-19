<?php
namespace App\Http;

/**
 * Classe Request - Abstração de requisição HTTP
 */
class Request
{
    private array $query;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private ?array $json = null;

    /**
     * Parâmetros de rota extraídos pelo Router
     */
    private array $routeParams = [];
    
    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }
    
    /**
     * Obtém método HTTP
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }
    
    /**
     * Obtém URI
     */
    public function uri(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }
    
    /**
     * Obtém query string completa
     */
    public function queryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }
    
    /**
     * Obtém parâmetro GET
     */
    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
    
    /**
     * Obtém parâmetro POST
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }
    
    /**
     * Obtém header
     */
    public function header(string $key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$key] ?? $default;
    }
    
    /**
     * Obtém dados JSON do body
     */
    public function json(?string $key = null, $default = null)
    {
        if ($this->json === null) {
            $input = file_get_contents('php://input');
            $this->json = json_decode($input, true) ?? [];
        }
        
        if ($key === null) {
            return $this->json;
        }
        
        return $this->json[$key] ?? $default;
    }
    
    /**
     * Obtém input (POST ou JSON)
     */
    public function input(string $key, $default = null)
    {
        // Tenta POST primeiro
        if (isset($this->post[$key])) {
            return $this->post[$key];
        }
        
        // Depois JSON
        return $this->json($key, $default);
    }
    
    /**
     * Obtém todos os inputs
     */
    public function all(): array
    {
        $request = new self();
        if ($request->isJson()) {
            return $request->json();
        }
        return $request->post;
    }
    
    /**
     * Verifica se é requisição AJAX
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }
    
    /**
     * Verifica se é requisição JSON
     */
    public function isJson(): bool
    {
        return str_contains(
            $this->header('Content-Type', ''), 
            'application/json'
        );
    }
    
    /**
     * Verifica se espera JSON como resposta
     */
    public function expectsJson(): bool
    {
        return str_contains(
            $this->header('Accept', ''), 
            'application/json'
        );
    }
    
    /**
     * Obtém IP do cliente
     */
    public function ip(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if (!empty($this->server[$key])) {
                $ip = trim(explode(',', $this->server[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Obtém User Agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Define parâmetro de rota (usado pelo Router)
     */
    public function setRouteParam(string $key, $value): void
    {
        $this->routeParams[$key] = $value;
    }

    /**
     * Define múltiplos parâmetros de rota
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = array_merge($this->routeParams, $params);
    }

    /**
     * Obtém arquivo enviado
     */
    public function file(string $key): ?UploadedFile
    {
        if (!isset($this->files[$key])) {
            return null;
        }
        
        return new UploadedFile($this->files[$key]);
    }

    /**
     * Verifica se tem arquivo
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && 
               $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }
    
}