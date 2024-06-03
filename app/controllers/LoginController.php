<?php

namespace App\controllers;

use App\models\user;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class LoginController
{

    public function LoginPage() {
        view(
            template: 'template',
            view: 'Login',
            title: 'Login',
        );
    }

    public function RegisterPage() {
        view(
            template: 'template',
            view: 'register',
            title: 'Register',
        );
    }

    public function Login() {
        $dados = request_post();
        $user_factory = new user;
        $registro = $user_factory->getOne('username', $dados['username']);

        if ($registro) {
            // usuario encontrado
            if (password_verify($dados['password'], $registro['password'])) {
                // Logado

                $payload = [
                    'exp' => time() + 60 * 60 * 24 * 1, // 2 Dias
                    'iat' => time(),
                    'user' => $dados['username']
                ];

                $jwt = JWT::encode($payload, KEY, ALGORITHM);

                $_SESSION['token'] = $jwt;
                $_SESSION['username'] = $dados['username'];

                sendMsn('Bem-vindo(a)!', 1);
                header('Location: /');

            } else {
                sendMsn('Senha incorreta!', 3);
                session_destroy();
            }
        } else {
            sendMsn('Usuario não encontrado!', 3);
            session_destroy();
        };
    }

    public function GetUserLevel() {
        $user_factory = new user;
        $registro = $user_factory->getOne('user', $_SESSION['user']);
        return $registro['level'];
    }

    public function Register() {
        $dados = request_post();

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
            $valido = JWT::decode($_SESSION['token'], new Key(KEY, ALGORITHM));
            if ($valido) {
                if (isset($valido->user)) {
                    return true;
                }
            }
        }
        return false;
    }
}