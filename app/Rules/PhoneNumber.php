<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Brazilian phone: 10 digits (landline) or 11 digits (mobile, starting with 9).
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';
        $length = strlen($digits);

        if ($length !== 10 && $length !== 11) {
            $fail('Informe um telefone com DDD.');

            return;
        }

        if ((int) substr($digits, 0, 2) < 11) {
            $fail('DDD inválido.');

            return;
        }

        if ($length === 11 && $digits[2] !== '9') {
            $fail('Celulares com 9 dígitos devem começar com 9 após o DDD.');
        }
    }
}
