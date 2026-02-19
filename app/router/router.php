<?php
namespace App\Router;

use App\Http\Request;
use App\Exceptions\RouteNotFoundException;
use App\Exceptions\MethodNotAllowedException;

/**
 * Router - Sistema de Roteamento
 * 
 */
class Router
{
    /**
     * Rotas registradas
     */
    private array $routes = [];
    
    /**
     * Rotas nomeadas
     */
    private array $namedRoutes = [];
    
    /**
     * Prefixo atual (para grupos)
     */
    private string $currentPrefix = '';
    
    /**
     * Métodos HTTP suportados
     */
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    
    /**
     * Construtor - pode receber rotas pré-definidas
     */
    public function __construct(array $routes = [])
    {
        if (!empty($routes)) {
            $this->loadRoutes($routes);
        }
    }
    
    /**
     * Carrega rotas de um array
     */
    private function loadRoutes(array $routes): void
    {
        if (isset($routes['routes']) && is_array($routes['routes'])) {
            foreach ($routes['routes'] as $route) {
                $this->addRoute(
                    $route['method'] ?? 'GET',
                    $route['path'] ?? '/',
                    $route['handler'] ?? null,
                    $route['name'] ?? null
                );
            }
        }
        
        if (isset($routes['groups']) && is_array($routes['groups'])) {
            foreach ($routes['groups'] as $group) {
                $this->group(
                    $group['prefix'] ?? '',
                    $group['callback'] ?? function() {}
                );
            }
        }
    }
    
    /**
     * Registra rota GET
     */
    public function get(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }
    
    /**
     * Registra rota POST
     */
    public function post(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }
    
    /**
     * Registra rota PUT
     */
    public function put(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }
    
    /**
     * Registra rota PATCH
     */
    public function patch(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }
    
    /**
     * Registra rota DELETE
     */
    public function delete(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }
    
    /**
     * Registra rota para qualquer método
     */
    public function any(string $path, $handler, ?string $name = null): self
    {
        foreach (self::METHODS as $method) {
            $this->addRoute($method, $path, $handler, $name);
        }
        return $this;
    }
    
    /**
     * Registra rota para múltiplos métodos
     */
    public function match(array $methods, string $path, $handler, ?string $name = null): self
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $name);
        }
        return $this;
    }
    
    /**
     * Adiciona uma rota
     */
    private function addRoute(string $method, string $path, $handler, ?string $name = null): self
    {
        $method = strtoupper($method);
        
        // Aplica prefixo do grupo
        $fullPath = $this->currentPrefix . $path;
        
        // Normaliza path
        $fullPath = $this->normalizePath($fullPath);
        
        // Registra rota
        $this->routes[$method][$fullPath] = [
            'handler' => $handler,
            'pattern' => $this->compilePattern($fullPath),
            'params' => $this->extractParams($fullPath)
        ];
        
        // Registra nome se fornecido
        if ($name !== null) {
            $this->namedRoutes[$name] = [
                'method' => $method,
                'path' => $fullPath
            ];
        }
        
        return $this;
    }
    
    /**
     * Cria grupo de rotas
     */
    public function group(string $prefix, callable $callback): self
    {
        $previousPrefix = $this->currentPrefix;
        
        $this->currentPrefix = $previousPrefix . $prefix;
        
        $callback($this);
        
        $this->currentPrefix = $previousPrefix;
        
        return $this;
    }
    
    /**
     * Despacha a requisição
     */
    public function dispatch(Request $request)
    {
        $method = $request->method();
        $uri = $this->normalizePath($request->uri());
        
        // Tenta encontrar rota
        $route = $this->findRoute($method, $uri);
        
        if ($route === null) {
            // Verifica se a rota existe para outro método
            if ($this->routeExistsForOtherMethod($uri, $method)) {
                throw new MethodNotAllowedException("Método $method não permitido para esta rota");
            }
            throw new RouteNotFoundException("Rota não encontrada: $uri");
        }
        
        // Extrai parâmetros da URL
        $params = $this->matchParams($route['pattern'], $uri, $route['params']);
        
        // Injeta parâmetros no request
        foreach ($params as $key => $value) {
            $request->setRouteParam($key, $value);
        }
        
        // Chama o handler
        return $this->callHandler($route['handler'], $request, $params);
    }
    
    /**
     * Encontra rota correspondente
     */
    private function findRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        foreach ($this->routes[$method] as $path => $route) {
            if ($this->matchRoute($route['pattern'], $uri)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Verifica se rota existe para outro método
     */
    private function routeExistsForOtherMethod(string $uri, string $excludeMethod): bool
    {
        foreach ($this->routes as $method => $routes) {
            if ($method === $excludeMethod) {
                continue;
            }
            
            foreach ($routes as $route) {
                if ($this->matchRoute($route['pattern'], $uri)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Compila padrão de rota
     */
    private function compilePattern(string $path): string
    {
        // Escapa barras
        $pattern = preg_quote($path, '#');
        
        // Converte parâmetros {param} para regex
        $pattern = preg_replace('#\\\{(\w+)\\\}#', '(?P<$1>[^/]+)', $pattern);
        
        // Converte parâmetros opcionais {param?} para regex
        $pattern = preg_replace('#\\\{(\w+)\\\?\\\}#', '(?P<$1>[^/]*)', $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Extrai nomes dos parâmetros
     */
    private function extractParams(string $path): array
    {
        preg_match_all('#\{(\w+)\??}#', $path, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * Verifica se URI corresponde ao padrão
     */
    private function matchRoute(string $pattern, string $uri): bool
    {
        return preg_match($pattern, $uri) === 1;
    }
    
    /**
     * Extrai parâmetros da URI
     */
    private function matchParams(string $pattern, string $uri, array $paramNames): array
    {
        if (empty($paramNames)) {
            return [];
        }
        
        preg_match($pattern, $uri, $matches);
        
        $params = [];
        foreach ($paramNames as $name) {
            $params[$name] = $matches[$name] ?? null;
        }
        
        return $params;
    }
    
    /**
     * Chama o handler da rota
     */
    private function callHandler($handler, Request $request, array $params)
    {
        // Handler como Closure
        if ($handler instanceof \Closure) {
            return $handler($request, ...array_values($params));
        }
        
        // Handler como string "Controller@method"
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);
            
            if (class_exists($controller)) {
                $instance = new $controller();
                
                if (method_exists($instance, $method)) {
                    return $instance->$method($request, ...array_values($params));
                }
            }
        }
        
        // Handler como array [Controller::class, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            
            if (class_exists($controller)) {
                $instance = new $controller();
                
                if (method_exists($instance, $method)) {
                    return $instance->$method($request, ...array_values($params));
                }
            }
        }
        
        throw new \RuntimeException("Handler inválido");
    }
    
    /**
     * Normaliza path (remove barras duplicadas, etc)
     */
    private function normalizePath(string $path): string
    {
        // Remove query string
        if (str_contains($path, '?')) {
            $path = substr($path, 0, strpos($path, '?'));
        }
        
        // Garante que começa com /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        
        // Remove barra final (exceto para root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        
        // Remove barras duplicadas
        $path = preg_replace('#/+#', '/', $path);
        
        return $path;
    }
    
    /**
     * Gera URL para rota nomeada
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Rota nomeada '$name' não encontrada");
        }
        
        $path = $this->namedRoutes[$name]['path'];
        
        // Substitui parâmetros
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
            $path = str_replace('{' . $key . '?}', $value, $path);
        }
        
        // Remove parâmetros opcionais não preenchidos
        $path = preg_replace('#/\{[^}]+\?\}#', '', $path);
        
        return $path;
    }
    
    /**
     * Lista todas as rotas registradas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}