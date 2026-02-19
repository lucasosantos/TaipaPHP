<?php

return [
    'routes' => [
        [
            'method' => 'POST',
            'path' => '/api/login',
            'handler' => [\App\Controllers\AuthController::class, 'login']
        ],
        [
            'method' => 'POST',
            'path' => '/api/register',
            'handler' => [\App\Controllers\AuthController::class, 'create']
        ],
    ],
    
    'groups' => [
        [
            'prefix' => '/api/painel',
            'callback' => function($router) {
                // Users
                $router->get('/', [\App\Controllers\HomeController::class, 'index']);
                $router->get('/dash', [\App\Controllers\HomeController::class, 'Painel']);
            }
        ],
        [
            'prefix' => '/api/posts',
            'callback' => function($router) {
                // Users
                $router->get('/', [\App\Controllers\PostController::class, 'Index']);
                
                // Posts
                $router->post('/create', [\App\Controllers\PostController::class, 'Create']);
                $router->delete('/delete/{id}', [\App\Controllers\PostController::class, 'Delete']);
                $router->put('/update/{id}', [\App\Controllers\PostController::class, 'Update']);
            }
        ]
    ]
];