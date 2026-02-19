<?php
namespace App\Http;

/**
 * Resposta JSON
 */
class JsonResponse extends Response
{
    private array $data;
    private bool $success;
    private int $jsonOptions;
    
    public function __construct(
        bool $success = true,
        mixed $data = [],
        int $statusCode = 200,
        int $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        $this->success = $success;
        $this->data = is_array($data) ? $data : [$data];
        $this->jsonOptions = $jsonOptions;
        
        parent::__construct('', $statusCode);
        
        $this->header('Content-Type', 'application/json; charset=utf-8');
    }
    
    /**
     * Atualiza os dados (corrige o setData do Application)
     */
    public function setData(mixed $data): static
    {
        $this->data = is_array($data) ? $data : [$data];
        return $this;
    }
    
    /**
     * Obtém os dados
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Adiciona campo ao JSON
     */
    public function with(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Resposta de sucesso padronizada
     */
    public static function success(mixed $data = null, string $message = 'Sucesso', int $statusCode = 200): static
    {
        $body = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $body['data'] = $data;
        }
        
        return new static(true, $body, $statusCode);
    }
    
    /**
     * Resposta de erro padronizada
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): static
    {
        $body = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $body['errors'] = $errors;
        }
        
        return new static(false, $body, $statusCode);
    }
    
    /**
     * Envia resposta
     */
    public function send(): void
    {
        $this->sendHeaders();
        echo json_encode([
            'success' => $this->success,
            'data' => !$this->success ? ['errors' => $this->data] : $this->data,
            'status_code' => $this->statusCode
            ], $this->jsonOptions);
    }
}