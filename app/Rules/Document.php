<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates CPF/CNPJ check digits. The value is expected to already be stripped
 * to digits by the Form Request, but non-digits are tolerated.
 */
class Document implements ValidationRule
{
    public const CPF = 'cpf';

    public const CNPJ = 'cnpj';

    public function __construct(private readonly ?string $type = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        $isCpf = strlen($digits) === 11 && $this->validCpf($digits);
        $isCnpj = strlen($digits) === 14 && $this->validCnpj($digits);

        $valid = match ($this->type) {
            self::CPF => $isCpf,
            self::CNPJ => $isCnpj,
            default => $isCpf || $isCnpj,
        };

        if ($valid) {
            return;
        }

        $fail(match ($this->type) {
            self::CPF => 'Informe um CPF válido.',
            self::CNPJ => 'Informe um CNPJ válido.',
            default => 'Informe um CPF ou CNPJ válido.',
        });
    }

    private function validCpf(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        foreach ([9, 10] as $position) {
            $sum = 0;

            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $cpf[$i] * (($position + 1) - $i);
            }

            if ((int) $cpf[$position] !== ((10 * $sum) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    private function validCnpj(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $weightSets = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weightSets as $index => $weights) {
            $position = 12 + $index;
            $sum = 0;

            foreach ($weights as $offset => $weight) {
                $sum += (int) $cnpj[$offset] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
