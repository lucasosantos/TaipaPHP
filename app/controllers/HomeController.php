<?php

namespace App\controllers;

use App\services\SecurityService;

class HomeController
{
    public function Index()
    {
        if (SecurityService::userIsAuthenticated()) { echo 'Usuário logado: ' . $_SESSION['username']; }
        view(
            template: 'template',
            view: 'index',
        );
    }

    public function Api()
    {
        echo json_encode("API TAIPA PHP");
    }

    public function Erro()
    {
        http_response_code(404);
        view(
            template: 'template',
            view: 'error',
            title: 'Erro 404',
        );
    }
}