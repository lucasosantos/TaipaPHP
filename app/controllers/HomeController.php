<?php

namespace App\controllers;

class HomeController
{
    public function Index()
    {
        views('index');
    }

    public function Api()
    {
        views('apiteste');
    }

    public function Erro()
    {
        views('error');
    }
}
