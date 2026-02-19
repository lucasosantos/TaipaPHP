<?php
namespace App\Core;

use App\Http\Request;
use App\Http\Response;
use App\Http\ViewResponse;
use App\Core\Config;
use App\Http\JsonResponse;
use App\Router\Router;

/**
 * Aplicação Principal - Motor Dual
 * Detecta automaticamente se a requisição é para API ou WEB e direciona para o kernel apropriado.
 */
class Application
{
    /**
     * Modo de operação
     */
    private string $mode;
    
    /**
     * Kernel ativo
     */
    private KernelInterface $kernel;
    
    /**
     * Request atual
     */
    private Request $request;
    
    /**
     * Config da aplicação
     */
    private Config $config;
    
    /**
     * Constantes de modo
     */
    public const MODE_AUTO = 'auto';
    public const MODE_API = 'api';
    public const MODE_WEB = 'web';
    public const MODE_HYBRID = 'hybrid';
    
    /**
     * Inicializa aplicação
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->request = new Request();
        $this->mode = $this->detectMode();
        $this->kernel = $this->createKernel();
    }
    
    /**
     * Detecta modo de operação
     */
    private function detectMode(): string
    {
        // 1. Verifica configuração manual
        $configMode = $this->config->get('app.mode', self::MODE_AUTO);
        
        if ($configMode !== self::MODE_AUTO) {
            return $configMode;
        }
        
        // 2. Auto-detecção
        
        // Por Accept header
        $accept = $this->request->header('Accept', '');
        if (str_contains($accept, 'application/json')) {
            return self::MODE_API;
        }
        
        // Por Content-Type
        $contentType = $this->request->header('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            return self::MODE_API;
        }
        
        // Por prefixo de rota
        $uri = $this->request->uri();
        if (str_starts_with($uri, '/api/')) {
            return self::MODE_API;
        }
        
        // Por parâmetro GET (útil para debug)
        if ($this->request->get('_mode') === 'api') {
            return self::MODE_API;
        }
        
        // Padrão: WEB
        return self::MODE_WEB;
    }
    
    /**
     * Cria kernel apropriado
     */
    private function createKernel(): KernelInterface
    {
        return match($this->mode) {
            self::MODE_API => new ApiKernel($this->config, $this->request),
            self::MODE_WEB => new WebKernel($this->config, $this->request),
            self::MODE_HYBRID => new HybridKernel($this->config, $this->request),
            default => new WebKernel($this->config, $this->request),
        };
    }
    
    /**
     * Executa aplicação
     */
    public function run(): void
    {
        try {
            // 1. Boot do kernel
            $this->kernel->boot();
            
            // 2. Processa request
            $response = $this->kernel->handle($this->request);
            
            // 3. Envia resposta
            $response->send();
            
            // 4. Terminate
            $this->kernel->terminate();
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Trata exceções de forma apropriada ao modo
     */
    private function handleException(\Exception $e): void
    {
        error_log('[Application] ' . $e->getMessage());
        
        $statusCode = method_exists($e, 'getStatusCode')
            ? $e->getStatusCode()
            : 500;
        
        if ($this->mode === self::MODE_API) {

            $data = [
                (method_exists($e, 'getErrors') ? $e->getErrors() : $e->getMessage()) ??
                    'Erro interno do servidor',
            ];
            
            if ($this->config->isDebug()) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
                $data['trace'] = explode("\n", $e->getTraceAsString());
            }
            
            $response = new JsonResponse(false, $data, $statusCode);
            
        } else {
            $content = $this->config->isDebug()
                ? $this->renderDebugError($e)
                : $this->renderProductionError($statusCode);
            
            // ✅ Sempre ViewResponse, nunca Response abstrata
            $response = new ViewResponse($content, $statusCode);
        }
        
        $response->send();
    }
    
    /**
     * Renderiza erro para debug
     */
    private function renderDebugError(\Exception $e): string
    {
        return "
        <html>
        <head>
            <title>Error</title>
            <style>
                body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
                h1 { color: #f00; }
                pre { background: #111; padding: 15px; border-left: 3px solid #0f0; }
            </style>
        </head>
        <body>
            <h1>Exception: " . get_class($e) . "</h1>
            <p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>File:</strong> {$e->getFile()}:{$e->getLine()}</p>
            <h2>Stack Trace:</h2>
            <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
        </body>
        </html>
        ";
    }
    
    /**
     * Renderiza erro para produção
     */
    private function renderProductionError(int $code): string
    {
        $messages = [
            404 => 'Página não encontrada',
            403 => 'Acesso negado',
            500 => 'Erro interno do servidor'
        ];
        
        $message = $messages[$code] ?? 'Erro desconhecido';
        
        return "
        <html>
        <head>
            <title>{$code} - {$message}</title>
            <style>
                body { font-family: Arial; text-align: center; padding: 50px; }
                h1 { font-size: 48px; color: #333; }
                p { color: #666; }
                a { color: #007bff; text-decoration: none; }
            </style>
        </head>
        <body>
            <h1>{$code}</h1>
            <p>{$message}</p>
            <a href='/'>Voltar para início</a>
        </body>
        </html>
        ";
    }
    
    /**
     * Obtém modo atual
     */
    public function getMode(): string
    {
        return $this->mode;
    }
    
    /**
     * Verifica se está em modo API
     */
    public function isApi(): bool
    {
        return $this->mode === self::MODE_API;
    }
    
    /**
     * Verifica se está em modo WEB
     */
    public function isWeb(): bool
    {
        return $this->mode === self::MODE_WEB;
    }
}

/**
 * Interface para kernels
 */
interface KernelInterface
{
    public function boot(): void;
    public function handle(Request $request): Response;
    public function terminate(): void;
}

class ApiKernel implements KernelInterface
{
    public function __construct(
        private Config $config,
        private Request $request
    ) {}
    
    public function boot(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        
        if ($this->config->get('api.cors_enabled', false)) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
    }
    
    public function handle(Request $request): Response
    {
        $routes = require $this->config->get('paths.app') . '/router/api.php';
        
        $router = new Router($routes);
        $result = $router->dispatch($request);
        
        // ✅ Se o handler já retornou uma Response, usa direto
        if ($result instanceof Response) {
            return $result;
        }
        
        // ✅ Caso contrário, encapsula em JsonResponse
        return new JsonResponse($result);
    }
    
    public function terminate(): void {}
}

/**
 * Kernel para modo WEB
 */
class WebKernel implements KernelInterface
{
    public function __construct(
        private Config $config,
        private Request $request
    ) {}
    
    public function boot(): void
    {
        // Headers específicos de WEB
        header('Content-Type: text/html; charset=utf-8');
        
        // Headers de segurança (já implementados no bootstrap)
        \App\Security\Security::applySecurityHeaders();
    }
    
    public function handle(Request $request): Response
    {
        // Carrega rotas WEB
        $routes = require $this->config->get('paths.app') . '/router/web.php';
        
        // Processa rota
        $router = new Router($routes);
        $result = $router->dispatch($request);
        
        // Se já é uma Response, retorna
        if ($result instanceof Response) {
            return $result;
        }
        
        // Caso contrário, converte para ViewResponse
        return new ViewResponse($result);
    }
    
    public function terminate(): void
    {
        // Cleanup específico de WEB
    }
}

/**
 * Kernel híbrido (detecta por rota)
 */
class HybridKernel implements KernelInterface
{
    private KernelInterface $activeKernel;
    
    public function __construct(
        private Config $config,
        private Request $request
    ) {
        // Detecta kernel baseado na URL
        $uri = $request->uri();
        
        if (str_starts_with($uri, '/api/')) {
            $this->activeKernel = new ApiKernel($config, $request);
        } else {
            $this->activeKernel = new WebKernel($config, $request);
        }
    }
    
    public function boot(): void
    {
        $this->activeKernel->boot();
    }
    
    public function handle(Request $request): Response
    {
        return $this->activeKernel->handle($request);
    }
    
    public function terminate(): void
    {
        $this->activeKernel->terminate();
    }
}