<?php

namespace App\controllers;

use App\models\user;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\services\SecurityService;
use App\enums\StatusCode;

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
        $dados = request_post_form();
        if (SecurityService::Login($dados['username'], $dados['password'])) {
            goToPage(DASHBOARD);
        } else {
            session_destroy();
            goToPage(LOGIN);
        }
    }

    public function Api_Login() {
        $dados = request_post_api();
        $user = '';
        if ($user = SecurityService::Login($dados['username'], $dados['password'])) {
            return json_return($user,StatusCode::OK->value);
        } else {
            return json_return('Bad Request',StatusCode::BadRequest->value);
        }
    }

    public function GetUserLevel() {
        return SecurityService::GetUserLevel();
    }

    public function Register() {
        $dados = request_post_form();

        if (SecurityService::Register($dados['username'], $dados['password'])) {
            goToPage(LOGIN);
        } else {
            goToPage(REGISTER);
        };
    }

    public function Api_Register() {
        $dados = request_post_api();
        if (SecurityService::Register($dados['username'], $dados['password'])) {
            return json_return('Success',StatusCode::OK->value);
        } else {
            return json_return('Bad Request',StatusCode::BadRequest->value);
        };
    }

    public function Logout() {
        SecurityService::CloseSession();
        goToPage('');
    }

    public function LoginIsValid(){
        return json_return(SecurityService::TokenIsValid());
    }
}