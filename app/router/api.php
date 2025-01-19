<?php

function api_router(){

    // Define que o tipo de conteúdo da resposta é JSON
    header('Content-Type: application/json');

    // Headers adicionais
    header('Access-Control-Allow-Origin: *'); // Permitir acesso de qualquer origem
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Métodos permitidos
    header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Headers permitidos

    #Rotas da api
    #Rota exata | '/user' => 'NameController@NameMethod',
    #Rotas dinamica | '/user/VarName/[a-z][0-9]+' => 'NameController@NameMethod',
    # '/soletras\/[a-z]+' | '/sonumeros\/[0-9]+ | '/letrasenumeros\/[a-z0-9]+'

    $rotasApi = [
        'GET' => [
            '/api' => 'HomeController@api',
        ],
        'POST' => [
            '/api/register' => 'LoginController@Api_Register',
            '/api/login' => 'LoginController@Api_Login'
        ],
        //'PUT' => [],
        //'DELETE' => []
    ];
    
    router($rotasApi);

}