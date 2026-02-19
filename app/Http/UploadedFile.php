<?php
namespace App\Http;

/**
 * Classe para gerenciar arquivos enviados
 */
class UploadedFile
{
    private array $file;
    
    public function __construct(array $file)
    {
        $this->file = $file;
    }
    
    /**
     * Verifica se upload foi bem-sucedido
     */
    public function isValid(): bool
    {
        return isset($this->file['error']) && 
               $this->file['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * Obtém nome original do arquivo
     */
    public function getClientOriginalName(): string
    {
        return $this->file['name'] ?? '';
    }
    
    /**
     * Obtém extensão do arquivo
     */
    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->getClientOriginalName(), PATHINFO_EXTENSION);
    }
    
    /**
     * Obtém mime type
     */
    public function getMimeType(): string
    {
        return $this->file['type'] ?? '';
    }
    
    /**
     * Obtém tamanho em bytes
     */
    public function getSize(): int
    {
        return $this->file['size'] ?? 0;
    }
    
    /**
     * Obtém caminho temporário
     */
    public function getTmpName(): string
    {
        return $this->file['tmp_name'] ?? '';
    }
    
    /**
     * Obtém código de erro
     */
    public function getError(): int
    {
        return $this->file['error'] ?? UPLOAD_ERR_NO_FILE;
    }
    
    /**
     * Move arquivo para destino
     */
    public function move(string $directory, ?string $name = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filename = $name ?? $this->getClientOriginalName();
        $destination = rtrim($directory, '/') . '/' . $filename;
        
        return move_uploaded_file($this->getTmpName(), $destination);
    }
    
    /**
     * Gera nome único para o arquivo
     */
    public function hashName(): string
    {
        $hash = bin2hex(random_bytes(16));
        return $hash . '.' . $this->getClientOriginalExtension();
    }
}