<?php

function web_router(){

    #Rotas do sistema
    #Rota exata | '/user' => 'NameController@NameMethod',
    #Rotas dinamica | '/user/VarName\/[a-z0-9]+' => 'NameController@NameMethod',
    # '/soletras\/[a-z]+' | '/sonumeros\/[0-9]+ | '/letrasenumeros\/[a-z0-9]+'

    $rotasWeb = [
        '/' => 'HomeController@Index',
        '/erro' => 'HomeController@Erro',
        '/print/nome/[a-z0-9]+' => 'HomeController@Print',
    ];

    router($rotasWeb);

}