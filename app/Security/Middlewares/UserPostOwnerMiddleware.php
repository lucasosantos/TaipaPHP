<?php

namespace App\Security\Middlewares;

use App\Http\JsonResponse;
use App\Models\Posts;
use App\Security\ApiSecurity;
use Exception;

class UserPostOwnerMiddleware
{
    public static function handle($id): void
    {
        $postId = $id; // Obtém ID do post da rota
        $userId = ApiSecurity::getUserId(); // Obtém ID do usuário autenticado

        $posts = new Posts;
        $post = $posts->getOneById($postId);
        if (!$post) {
            throw new Exception('Post não encontrado');
        }

        if ($post['user_id'] !== $userId) {
            throw new Exception('Acesso negado: você não é o proprietário deste post');
        }
    }
}