<?php

function api_router(){

    #Rotas da api
    #Rota exata | '/user' => 'NameController@NameMethod',
    #Rotas dinamica | '/user/VarName/[a-z][0-9]+' => 'NameController@NameMethod',
    # '/soletras\/[a-z]+' | '/sonumeros\/[0-9]+ | '/letrasenumeros\/[a-z0-9]+'

    $rotasApi = [
        'GET' => [
            '/api' => 'HomeController@api',
        ],
        //'POST' => [],
        //'PUT' => [],
        //'DELETE' => []
    ];
    
    router($rotasApi);

}