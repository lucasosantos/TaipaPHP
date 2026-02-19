<?php
namespace App\Http;

use App\Http\Response;

/**
 * Resposta de Download
 */
class DownloadResponse extends Response
{
    public function __construct(
        string $filePath,
        ?string $fileName = null
    ) {
        parent::__construct(file_get_contents($filePath), 200);
        
        $fileName = $fileName ?? basename($filePath);
        
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
        $this->header('Content-Length', (string)filesize($filePath));
    }
    
    /**
     * Envia arquivo
     */
    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
        exit;
    }
}
