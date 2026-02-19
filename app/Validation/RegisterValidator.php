<?php
namespace App\Validation;

use App\Exceptions\ValidationException;
use App\Models\User;

class RegisterValidator
{
    /**
     * Valida se username e email já existem no banco
     *
     * @throws ValidationException se houver conflitos
     */
    public static function validateEmailAndUsername(string $username, string $email): void
    {
        $errors = [];

        try {
            $existingUser = (new User())->getOne('username', $username);
            if ($existingUser) {
                $errors['username'] = 'Username já existe';
            }

            $existingEmail = (new User())->getOne('email', $email);
            if ($existingEmail) {
                $errors['email'] = 'Email já existe';
            }

        } catch (\Exception $e) {
            // Erro de banco
            throw new \RuntimeException('Erro ao verificar dados: ' . $e->getMessage());
        }

        // Lança ValidationException apenas se houver erros
        if (!empty($errors)) {
            throw new ValidationException('Erros de validação', $errors);
        }
    }
}