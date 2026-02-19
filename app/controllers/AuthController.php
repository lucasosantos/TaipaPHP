<?php
namespace App\Controllers;

use App\Controllers\Controller;
use App\Exceptions\ValidationException;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Models\User;
use App\Security\ApiSecurity;
use App\Security\Security;
use App\Validation\RegisterValidator;
use App\Validation\Validator;
use Exception;

class AuthController extends Controller
{
    public function create(Request $request)
    { 
        // Obtém dados sanitizados e validados
        $data = Validator::validate($request->all(),[
            'username' => 'string|required|min:3|max:50',
            'email' => 'email|required',
            'password' => 'string|required|min:8'
        ]);

        // Valida unicidade de username e email
        RegisterValidator::validateEmailAndUsername($data['username'], $data['email']);

        // Valida força da senha
        $errors = Security::validatePasswordStrength($data['password']);
        if (!empty($errors)) {
            return new JsonResponse(false, ['Senha fraca', 'errors' => $errors], 422);
        }
        
        // Hash seguro da senha
        $passwordHash = Security::hashPassword($data['password']);
        
        // Salva no banco
        try {
            $user = new User();
            $userId = $user->insert([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $passwordHash,
                'level' => 'user'
            ]);
        } catch (\Exception $e) {
            throw new ValidationException('Erro ao criar usuário: ' . $e->getMessage());
        }
        
        if ($userId) {
            return new JsonResponse(true,['user_id' => $userId],201);
        } else { 
            return new JsonResponse(false,['success' => false,'message' => 'Erro ao criar usuário'], 422);
        }
    }

    public function login(Request $request)
    {

        //Valida os dados de entrada
        $data = Validator::validate($request->all(),[
            'username' => 'string|required|min:3|max:50',
            'password' => 'string|required'
        ]);
        
        try {
            $user = new User();
            $userData = $user->getOne('username', $data['username']);

            if (!$userData) {
                throw new Exception('Usuário não encontrado');
            }

            if ($userData && Security::verifyPassword($data['password'], $userData['password'])) {
            
                $payload = [
                    'id' => $userData['id'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'level' => $userData['level']
                ];
            
                return new JsonResponse(true, [
                    'user' => [
                            'username' => $userData['username'],
                            'email' => $userData['email'],
                        ],
                        'token' => ApiSecurity::generateJWT($payload)
                    ], 200);
            } else {
                return new JsonResponse(false, ['Credenciais inválidas'], 401);
            }

        } catch (Exception $e) {
            throw new Exception('Erro ao fazer login: ' . $e->getMessage());
        }
        
    }   
}