<?php

namespace App\services;

use App\models\user;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SecurityService {
    
    static function ValidarLogin(){
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

    //Regra de acesso: Usuário logado
    static function pageRuleIsAuthenticated(){
        if (self::ValidarLogin()) {
            goToPage('logout');
        }
    }

    //Teste lógico para saber se o usuário esta logado, retorna true ou false
    static function userIsAuthenticated(){
        if (self::ValidarLogin()) {
            return true;
        } else {
            return false;
        }
    }

    //Regra de acesso: Usuário com nível de acesso específico
    public function pageRuleAuthenticatedUserLevel($level){
        pageRuleIsAuthenticated();
        if (userLevel() != $level) {
            goToPage('painel');
        }
    }

    //Retorna a string do nivel do usuário
    public function userLevel() {
        $Login = new LoginController;
        return $Login->GetUserLevel();
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
}