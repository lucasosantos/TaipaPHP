<?php

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Security\ApiSecurity;
use App\Security\WebSecurity;

class HomeController
{
    public function Index()
    {
        ApiSecurity::requireAuth();
        return new JsonResponse(true, ['message' => 'Bem-vindo à API TaipaPHP!']);
    }

    public function Painel()
    {
        ApiSecurity::requireLevel('admin');
        
        return new JsonResponse(true, ['message' => 'Bem-vindo ao painel administrativo da API TaipaPHP!']);
    }

    public function DashAdmin()
    {
        WebSecurity::requireLevel('admin');

        jsonResponse([
            'message' => 'Bem-vindo ao dashboard de administrador!',
            'user' => $_SESSION['user'] ?? null
        ]);
    }

    public function Erro()
    {
        http_response_code(404);
        view(
            template: 'template',
            view: 'error'
        );
    }
}