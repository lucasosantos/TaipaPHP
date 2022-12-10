<?php

namespace App\controllers;

class HomeController
{
    public function Index()
    {
        views('index');
    }

    public function Api(){
        echo json_encode("API TAIPA PHP");
    }

    public function Erro()
    {
        http_response_code(404);
        views('error');
    }
}
