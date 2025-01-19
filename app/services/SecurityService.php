<?php

namespace App\services;

use App\models\user;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\enums\MsnFeedback;
use App\enums\MsnType;

class SecurityService {
    
    static function TokenIsValid(){
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

    //Teste lógico para saber se o usuário esta logado, retorna true ou false
    static function IsAuthenticated(){
        if (self::TokenIsValid()) {
            return true;
        } else {
            return false;
        }
    }

    //Regra de acesso: Usuário logado
    static function PageRule_IsAuthenticated(){
        if (!self::TokenIsValid()) {
            goToPage(LOGOUT);
        }
    }

    //Regra de acesso: Usuário com nível de acesso específico
    static function pageRule_AuthenticatedUserLevel($level){
        self::PageRule_IsAuthenticated();
        if (self::GetUserLevel() != $level) {
            goToPage(DASHBOARD);
        }
    }

    static function Login($username, $password) {
        $user_factory = new user;
        $registro = $user_factory->getOne('username', $username);

        if ($registro) {
            // usuario encontrado
            if (password_verify($password, $registro['password'])) {
                // Logado

                $payload = [
                    'exp' => time() + 60 * 60 * 24 * 1, // 2 Dias
                    'iat' => time(),
                    'user' => $username
                ];

                $jwt = JWT::encode($payload, KEY, ALGORITHM);

                $user = array(
                    'username' => $username,
                    'token' => $jwt,
                );

                SecurityService::StartSession($username, $jwt);
                sendMsn(MsnFeedback::LOGIN_SUCCESS->value, MsnType::SUCCESS->value);
                return $user;
                
            } else {
                return false;
                sendMsn(MsnFeedback::LOGIN_ERROR->value, MsnType::DANGER->value);
            }
        } else {
            return false;
            sendMsn(MsnFeedback::LOGIN_ERROR->value, MsnType::ERROR->value);
        };
    }

    static function GetUserLevel() {
        $user_factory = new user;
        $registro = $user_factory->getOne('user', $_SESSION['user']);
        return $registro['level'];
    }

    static function Register($username, $password) {
        $user_factory = new user;
        if ( 
            $user_factory->insert(
                ['username', 'password'],
                [
                    $username,
                    password_hash($password, PASSWORD_DEFAULT)
                ]
            )
        ) {
            sendMsn(MsnFeedback::REGISTER_SUCCESS->value, MsnType::SUCCESS->value);
            return true;
        } else {
            sendMsn(MsnFeedback::REGISTER_SUCCESS->value, MsnType::ERROR->value);
            return false;
        }
        ;
    }

    static function StartSession($username, $token) {
        $_SESSION['token'] = $token;
        $_SESSION['username'] = $username;
    }

    static function CloseSession() {
        $_SESSION = array();
        $_SESSION['token'] = [''];
        $_SESSION['username'] = [''];
        session_destroy();
    }
}