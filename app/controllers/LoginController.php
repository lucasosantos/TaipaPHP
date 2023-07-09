<?php

namespace App\controllers;

use App\models\user;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class LoginController
{

    public function LoginPage() {
        views('login');
    }

    public function RegisterPage() {
        views('register');
    }

    public function Login() {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $user_factory = new user;
        $registro = $user_factory->getOne('username', $dados['username']);

        if ($registro) {
            // usuario encontrado
            if (password_verify($dados['password'], $registro['password'])) {
                // Logado

                $key = getenv('KEY');
                $payload = [
                    'exp' => time() + 60 * 60 * 24 * 1, // 2 Dias
                    'iat' => time(),
                    'user' => $dados['username']
                ];

                $jwt = JWT::encode($payload, $key, getenv('ALGORITHM'));

                $_SESSION['token'] = $jwt;
                $_SESSION['username'] = $dados['username'];

                header('Location: /');

            } else {
                session_destroy();
            }
        } else {
            session_destroy();
        };
    }

    public function GetUserLevel() {
        $user_factory = new user;
        $registro = $user_factory->getOne('user', $_SESSION['user']);
        return $registro['level'];
    }

    public function Register() {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        $user_factory = new user;
        if ( 
            $user_factory->insert(
                ['username', 'password'],
                [
                    $dados["username"],
                    password_hash($dados['password'], PASSWORD_DEFAULT)
                ]
            )
        ) {
            echo "Registrado";
        }
        ;
    }

    public function Logout() {
        $_SESSION = array();
        $_SESSION['token'] = [''];
        $_SESSION['username'] = [''];
        session_destroy();
        goToPage('');
    }

    public function ValidarLogin(){
        if (isset($_SESSION['token'])) {
            $valido = JWT::decode($_SESSION['token'], new Key(getenv('KEY'), getenv('ALGORITHM')));
            if ($valido) {
                if (isset($valido->user)) {
                    if ($valido->exp > time()) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}