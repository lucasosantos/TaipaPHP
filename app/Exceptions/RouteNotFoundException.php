<?php
namespace App\Exceptions;

/**
 * Exceção para rota não encontrada (404)
 */
class RouteNotFoundException extends \Exception
{
    public function getStatusCode(): int
    {
        return 404;
    }
}