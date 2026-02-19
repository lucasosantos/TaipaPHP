<?php
namespace App\Http;

use App\Http\Response;

/**
 * Resposta de Redirecionamento
 */
class RedirectResponse extends Response
{
    public function __construct(
        string $url, 
        int $statusCode = 302
    ) {
        parent::__construct('', $statusCode);
        $this->header('Location', $url);
    }
    
    /**
     * Envia redirecionamento
     */
    public function send(): void
    {
        $this->sendHeaders();
        exit;
    }
    
    /**
     * Com flash message
     */
    public function with(string $key, $value): self
    {
        $_SESSION['_flash'][$key] = $value;
        return $this;
    }
}