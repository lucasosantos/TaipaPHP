<?php

namespace App\enums;

enum MsnFeedback: string {
    case LOGIN_SUCCESS = 'Bem-vindo(a)!';
    case LOGIN_ERROR = 'Usuário ou senha incorretos!';
    case REGISTER_ERROR = 'Erro ao registrar usuário!';
    case REGISTER_SUCCESS = 'Registrado!';
}