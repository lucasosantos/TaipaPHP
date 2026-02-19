<?php
namespace App\Exceptions;

/**
 * Exceção para método não permitido (405)
 */
class MethodNotAllowedException extends \Exception
{
    public function getStatusCode(): int
    {
        return 405;
    }
}