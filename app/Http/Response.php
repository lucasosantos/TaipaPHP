<?php
namespace App\Http;

/**
 * Classe Response - Base para respostas
 */
abstract class Response
{
    protected mixed $content;
    protected int $statusCode;
    protected array $headers = [];
    
    public function __construct(mixed $content = '', int $statusCode = 200)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
    }
    
    /**
     * Define header
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    /**
     * Define múltiplos headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
    
    /**
     * Define status code
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * Define conteúdo
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Envia resposta
     */
    abstract public function send(): void;
    
    /**
     * Envia headers
     */
    protected function sendHeaders(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }
}