<?php

namespace App\Exceptions;

/**
 * Exceção de validação
 */
class ValidationException extends \Exception
{
    public function __construct(
        string $message,
        private array $errors = []
    ) {
        parent::__construct($message);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
    
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
    
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ];
    }
}