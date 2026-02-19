<?php

namespace App\Http;

use App\Http\Response;

/**
 * Resposta HTML/View
 */
class ViewResponse extends Response
{
    public function __construct(
        string $content = '', 
        int $statusCode = 200
    ) {
        parent::__construct($content, $statusCode);
        $this->header('Content-Type', 'text/html; charset=utf-8');
    }
    
    /**
     * Envia resposta HTML
     */
    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
        exit;
    }
}