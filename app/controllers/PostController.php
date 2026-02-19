<?php

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Models\Posts;
use App\Security\ApiSecurity;
use App\Validation\Validator;
use App\Security\Middlewares\UserPostOwnerMiddleware;

class PostController
{
    public function Index()
    {
        ApiSecurity::requireAuth();
        $posts = new Posts;
        return new JsonResponse(
            true,
            $posts
                ->view('admin')
                ->listAll()
            );
    }

    public function Create(Request $request)
    {
        ApiSecurity::requireAuth();

        $dados = Validator::validate($request->all(), [
            'name' => 'string|required|min:3|max:150',
            'description' => 'string|required|min:3|max:250',
            'value' => 'float|required'
        ]);

        $posts = new Posts;

        try{
            $new = $posts->insert([
                'name' => $dados['name'],
                'description' => $dados['description'],
                'value' => $dados['value'],
                'user_id' => ApiSecurity::getUserId()
            ]);
            return new JsonResponse(true, ['message' => 'Post created successfully', 'post' => $new]);
        }
        catch (\Exception $e)
        {
            return new JsonResponse(false, ['error' => $e->getMessage()]);
        }
    }

    public function Update(Request $request, $id)
    {
        ApiSecurity::requireAuth();

        // Verifica se o usuário é o proprietário do post
        UserPostOwnerMiddleware::handle($id);
    
        $dados = Validator::validate($request->all(), [
            'name' => 'string|required|min:3|max:150',
            'description' => 'string|required|min:3|max:250',
            'value' => 'float|required'
        ]);

        $posts = new Posts;

        try       {
            $edited = $posts->update('id', $id, $dados);
            return new JsonResponse(true, ['message' => 'Post updated successfully', 'post' => $edited]);
        }
        catch (\Exception $e)
        {
            return new JsonResponse(false, ['error' => $e->getMessage()]);
        }

    }

    public function Delete(Request $request, int $id)
    {
        ApiSecurity::requireAuth();
        // Verifica se o usuário é o proprietário do post
        UserPostOwnerMiddleware::handle($id);

        $posts = new Posts;
        return new JsonResponse(true, $posts->delete('id', $id));
    }

}