<?php
namespace App\Validation;

use InvalidArgumentException;
use App\Exceptions\ValidationException;

/**
 * Validator - Sistema de Validação Unificado
 * 
 * Usado por ApiInput e WebInput para validação e sanitização
 */
class Validator
{
    /**
     * Sanitiza valor baseado no tipo
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function sanitize(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return match($type) {
            'string' => self::sanitizeString($value),
            'int', 'integer' => self::sanitizeInt($value),
            'float', 'double', 'decimal' => self::sanitizeFloat($value),
            'bool', 'boolean' => self::sanitizeBool($value),
            'email' => self::sanitizeEmail($value),
            'url' => self::sanitizeUrl($value),
            'alpha' => self::sanitizeAlpha($value),
            'alphanumeric' => self::sanitizeAlphanumeric($value),
            'date' => self::sanitizeDate($value),
            'datetime' => self::sanitizeDateTime($value),
            'time' => self::sanitizeTime($value),
            'slug' => self::sanitizeSlug($value),
            'phone' => self::sanitizePhone($value),
            'cpf' => self::sanitizeCpf($value),
            'cnpj' => self::sanitizeCnpj($value),
            'array' => self::sanitizeArray($value),
            'json' => self::sanitizeJson($value),
            'html' => self::sanitizeHtml($value),
            'raw' => $value, // Sem sanitização (use com cuidado!)
            default => throw new InvalidArgumentException("Tipo inválido: {$type}"),
        };
    }
    
    /**
     * Valida dados contra regras
     * 
     * @param array $data Dados a validar
     * @param array $rules Regras de validação
     * @return array Dados validados
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules): array
    {
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            // Parse regra: 'string|required|min:3|max:50'
            $rulesParts = explode('|', $rule);
            $type = array_shift($rulesParts);
            
            // Flags de validação
            $required = in_array('required', $rulesParts);
            $nullable = in_array('nullable', $rulesParts);
            
            // Obtém valor
            $value = self::getNestedValue($data, $field, null);
            
            // Valida presença
            if ($value === null || $value === '') {
                if ($required && !$nullable) {
                    $errors[$field] = self::getMessage('required', $field);
                    continue;
                }
                if ($nullable) {
                    $validated[$field] = null;
                    continue;
                }
            }
            
            try {
                // Sanitiza
                $sanitized = self::sanitize($value, $type);
                
                // Aplica regras de validação
                foreach ($rulesParts as $rulePart) {
                    self::applyRule($rulePart, $field, $sanitized, $data, $errors);
                }
                
                $validated[$field] = $sanitized;
                
            } catch (\Exception $e) {
                $errors[$field] = $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Validação falhou', $errors);
        }
        
        return $validated;
    }
    
    /**
     * Aplica regra de validação
     */
    private static function applyRule(
        string $rule, 
        string $field, 
        mixed $value, 
        array $allData, 
        array &$errors
    ): void {
        // Min
        if (str_starts_with($rule, 'min:')) {
            $min = (int)substr($rule, 4);
            if (strlen((string)$value) < $min) {
                $errors[$field] = self::getMessage('min', $field, ['min' => $min]);
            }
        }
        
        // Max
        elseif (str_starts_with($rule, 'max:')) {
            $max = (int)substr($rule, 4);
            if (strlen((string)$value) > $max) {
                $errors[$field] = self::getMessage('max', $field, ['max' => $max]);
            }
        }
        
        // In (valores permitidos)
        elseif (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed, true)) {
                $errors[$field] = self::getMessage('in', $field, ['values' => implode(', ', $allowed)]);
            }
        }
        
        // Not In (valores proibidos)
        elseif (str_starts_with($rule, 'not_in:')) {
            $forbidden = explode(',', substr($rule, 7));
            if (in_array($value, $forbidden, true)) {
                $errors[$field] = self::getMessage('not_in', $field);
            }
        }
        
        // Regex
        elseif (str_starts_with($rule, 'regex:')) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, (string)$value)) {
                $errors[$field] = self::getMessage('regex', $field);
            }
        }
        
        // Confirmed (campo de confirmação)
        elseif ($rule === 'confirmed') {
            $confirmField = $field . '_confirmation';
            if (!isset($allData[$confirmField]) || $allData[$confirmField] !== $allData[$field]) {
                $errors[$field] = self::getMessage('confirmed', $field);
            }
        }
        
        // Same (igual a outro campo)
        elseif (str_starts_with($rule, 'same:')) {
            $otherField = substr($rule, 5);
            if (!isset($allData[$otherField]) || $allData[$otherField] !== $value) {
                $errors[$field] = self::getMessage('same', $field, ['other' => $otherField]);
            }
        }
        
        // Different (diferente de outro campo)
        elseif (str_starts_with($rule, 'different:')) {
            $otherField = substr($rule, 10);
            if (isset($allData[$otherField]) && $allData[$otherField] === $value) {
                $errors[$field] = self::getMessage('different', $field, ['other' => $otherField]);
            }
        }
        
        // Between (entre dois valores)
        elseif (str_starts_with($rule, 'between:')) {
            [$min, $max] = explode(',', substr($rule, 8));
            $length = strlen((string)$value);
            if ($length < $min || $length > $max) {
                $errors[$field] = self::getMessage('between', $field, ['min' => $min, 'max' => $max]);
            }
        }
        
        // Size (tamanho exato)
        elseif (str_starts_with($rule, 'size:')) {
            $size = (int)substr($rule, 5);
            if (strlen((string)$value) !== $size) {
                $errors[$field] = self::getMessage('size', $field, ['size' => $size]);
            }
        }
        
        // Unique (deve ser único - requer callback)
        elseif (str_starts_with($rule, 'unique:')) {
            // Format: unique:table,column
            [$table, $column] = explode(',', substr($rule, 7));
            // Nota: Implementação requer acesso ao banco
            // Por enquanto, apenas placeholder
        }
    }
    
    /**
     * Obtém valor aninhado usando notação de ponto
     */
    private static function getNestedValue(array $data, string $key, mixed $default): mixed
    {
        if (isset($data[$key])) {
            return $data[$key];
        }
        
        // Suporta notação de ponto
        $keys = explode('.', $key);
        $value = $data;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Obtém mensagem de erro
     */
    private static function getMessage(string $rule, string $field, array $params = []): string
    {
        // Converte field_name para Field Name
        $fieldName = ucwords(str_replace('_', ' ', $field));
        
        return match($rule) {
            'required' => "{$fieldName} é obrigatório",
            'min' => "{$fieldName} deve ter no mínimo {$params['min']} caracteres",
            'max' => "{$fieldName} deve ter no máximo {$params['max']} caracteres",
            'in' => "{$fieldName} deve ser um de: {$params['values']}",
            'not_in' => "{$fieldName} contém valor inválido",
            'regex' => "{$fieldName} está em formato inválido",
            'confirmed' => "Confirmação de {$fieldName} não corresponde",
            'same' => "{$fieldName} deve ser igual a {$params['other']}",
            'different' => "{$fieldName} deve ser diferente de {$params['other']}",
            'between' => "{$fieldName} deve ter entre {$params['min']} e {$params['max']} caracteres",
            'size' => "{$fieldName} deve ter exatamente {$params['size']} caracteres",
            default => "{$fieldName} é inválido",
        };
    }
    
    // ==========================================
    // MÉTODOS DE SANITIZAÇÃO
    // ==========================================
    
    private static function sanitizeString(mixed $value): string
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        
        // Remove tags HTML (XSS protection)
        $value = strip_tags($value);
        
        // Remove caracteres de controle
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        
        // Trim
        $value = trim($value);
        
        return $value;
    }
    
    private static function sanitizeInt(mixed $value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    private static function sanitizeFloat(mixed $value): float
    {
        $value = filter_var(
            $value, 
            FILTER_SANITIZE_NUMBER_FLOAT, 
            FILTER_FLAG_ALLOW_FRACTION
        );

        if ($value === false || $value === null || !is_numeric($value)) {
            throw new InvalidArgumentException('Valor numérico inválido');
        }

        return (float)$value;
    }
    
    private static function sanitizeBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    private static function sanitizeEmail(mixed $value): string
    {
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email inválido');
        }
        
        return strtolower($value);
    }
    
    private static function sanitizeUrl(mixed $value): string
    {
        $value = filter_var($value, FILTER_SANITIZE_URL);
        
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('URL inválida');
        }
        
        return $value;
    }
    
    private static function sanitizeAlpha(mixed $value): string
    {
        return preg_replace('/[^a-zA-ZÀ-ÿ\s]/', '', (string)$value);
    }
    
    private static function sanitizeAlphanumeric(mixed $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', (string)$value);
    }
    
    private static function sanitizeDate(mixed $value): string
    {
        $value = (string)$value;
        
        // Formatos aceitos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d'); // Sempre retorna ISO
            }
        }
        
        throw new InvalidArgumentException('Data inválida. Use: YYYY-MM-DD ou DD/MM/YYYY');
    }
    
    private static function sanitizeDateTime(mixed $value): string
    {
        $value = (string)$value;
        
        $formats = ['Y-m-d H:i:s', 'd/m/Y H:i:s', 'Y-m-d H:i', 'd/m/Y H:i'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        
        throw new InvalidArgumentException('Data/hora inválida');
    }
    
    private static function sanitizeTime(mixed $value): string
    {
        $value = (string)$value;
        
        if (preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/', $value, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = $matches[2];
            $second = $matches[3] ?? '00';
            return "{$hour}:{$minute}:{$second}";
        }
        
        throw new InvalidArgumentException('Hora inválida. Use: HH:MM ou HH:MM:SS');
    }
    
    private static function sanitizeSlug(mixed $value): string
    {
        $value = (string)$value;
        
        // Remove acentos
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        
        // Minúsculo
        $value = strtolower($value);
        
        // Remove caracteres especiais
        $value = preg_replace('/[^a-z0-9-]/', '-', $value);
        
        // Remove múltiplos hífens
        $value = preg_replace('/-+/', '-', $value);
        
        // Remove hífens nas extremidades
        $value = trim($value, '-');
        
        return $value;
    }
    
    private static function sanitizePhone(mixed $value): string
    {
        // Remove tudo exceto números
        return preg_replace('/[^0-9]/', '', (string)$value);
    }
    
    private static function sanitizeCpf(mixed $value): string
    {
        $cpf = preg_replace('/[^0-9]/', '', (string)$value);
        
        if (strlen($cpf) !== 11) {
            throw new InvalidArgumentException('CPF deve ter 11 dígitos');
        }
        
        // Valida CPF
        if (!self::validateCpf($cpf)) {
            throw new InvalidArgumentException('CPF inválido');
        }
        
        return $cpf;
    }
    
    private static function sanitizeCnpj(mixed $value): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string)$value);
        
        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ deve ter 14 dígitos');
        }
        
        // Valida CNPJ
        if (!self::validateCnpj($cnpj)) {
            throw new InvalidArgumentException('CNPJ inválido');
        }
        
        return $cnpj;
    }
    
    private static function sanitizeArray(mixed $value): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Valor deve ser array');
        }
        
        return array_map(
            fn($item) => is_string($item) ? self::sanitizeString($item) : $item,
            $value
        );
    }
    
    private static function sanitizeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (!is_string($value)) {
            throw new InvalidArgumentException('JSON deve ser string ou array');
        }
        
        try {
            return json_decode($value, true, 10, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('JSON inválido: ' . $e->getMessage());
        }
    }
    
    private static function sanitizeHtml(mixed $value): string
    {
        $value = (string)$value;
        
        // Tags permitidas (ajuste conforme necessário)
        $allowedTags = '<p><br><strong><em><ul><ol><li><a><h1><h2><h3>';
        
        // Remove tags não permitidas
        $value = strip_tags($value, $allowedTags);
        
        // Sanitiza atributos de <a>
        $value = preg_replace_callback(
            '/<a\s+([^>]*)>/i',
            function($matches) {
                preg_match('/href=["\']([^"\']*)["\']/', $matches[1], $href);
                preg_match('/title=["\']([^"\']*)["\']/', $matches[1], $title);
                
                $newAttrs = [];
                if (!empty($href[1])) {
                    $url = filter_var($href[1], FILTER_SANITIZE_URL);
                    $newAttrs[] = 'href="' . htmlspecialchars($url, ENT_QUOTES) . '"';
                }
                if (!empty($title[1])) {
                    $newAttrs[] = 'title="' . htmlspecialchars($title[1], ENT_QUOTES) . '"';
                }
                
                $newAttrs[] = 'rel="noopener noreferrer"';
                $newAttrs[] = 'target="_blank"';
                
                return '<a ' . implode(' ', $newAttrs) . '>';
            },
            $value
        );
        
        return $value;
    }
    
    // ==========================================
    // VALIDADORES AUXILIARES
    // ==========================================
    
    private static function validateCpf(string $cpf): bool
    {
        // Elimina CPFs inválidos conhecidos
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Valida primeiro dígito
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        if ($cpf[9] != $digit1) {
            return false;
        }
        
        // Valida segundo dígito
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        return $cpf[10] == $digit2;
    }
    
    private static function validateCnpj(string $cnpj): bool
    {
        // Elimina CNPJs inválidos conhecidos
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Valida primeiro dígito
        $sum = 0;
        $multiplier = 5;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $multiplier;
            $multiplier = ($multiplier == 2) ? 9 : $multiplier - 1;
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        if ($cnpj[12] != $digit1) {
            return false;
        }
        
        // Valida segundo dígito
        $sum = 0;
        $multiplier = 6;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $multiplier;
            $multiplier = ($multiplier == 2) ? 9 : $multiplier - 1;
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        return $cnpj[13] == $digit2;
    }
}