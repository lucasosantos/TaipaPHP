<?php

return [
    'routes' => [
        [
            'method' => 'GET',
            'path' => '/',
            'handler' => [\App\Controllers\HomeController::class, 'index'],
            'name' => 'home'
        ]
    ],
    
    'groups' => [
        [
            'prefix' => '/admin',
            'callback' => function($router) {
                $router->get('/', [\App\Controllers\HomeController::class, 'index']);
            }
        ]
    ]
];