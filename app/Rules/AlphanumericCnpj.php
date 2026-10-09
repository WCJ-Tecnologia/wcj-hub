<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * CNPJ alfanumérico da Receita (N5): 12 caracteres de 0-9 ou A-Z e 2 dígitos verificadores.
 * O valor de cada caractere é o código ASCII menos 48, então o CNPJ só de números calcula
 * igual ao antigo. Máscara e caixa não importam: o Service grava o normalize().
 */
class AlphanumericCnpj implements ValidationRule
{
    private const WEIGHTS = [
        [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
    ];

    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $value) ?? '');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->valid(self::normalize((string) $value))) {
            $fail('Informe um CNPJ válido.');
        }
    }

    /**
     * Os 12 primeiros caracteres seguidos dos 2 dígitos verificadores. Também gera CNPJ válido
     * para a factory de clientes.
     */
    public static function withCheckDigits(string $base): string
    {
        foreach (self::WEIGHTS as $weights) {
            $sum = 0;
            foreach ($weights as $offset => $weight) {
                $sum += (ord($base[$offset]) - 48) * $weight;
            }

            $remainder = $sum % 11;
            $base .= $remainder < 2 ? '0' : (string) (11 - $remainder);
        }

        return $base;
    }

    private function valid(string $cnpj): bool
    {
        // Todo igual (00000000000000) passa no cálculo, mas não é CNPJ.
        if (preg_match('/^[0-9A-Z]{12}[0-9]{2}$/', $cnpj) !== 1 || preg_match('/^(.)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        return self::withCheckDigits(substr($cnpj, 0, 12)) === $cnpj;
    }
}
