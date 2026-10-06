<?php

namespace App\Http\Requests\Concerns;

/**
 * Masks live in the browser; the database only ever stores raw values. This
 * strips formatting again on the server so an API client cannot bypass it.
 */
trait NormalizesMaskedInput
{
    /**
     * @param  list<string>  $fields
     */
    protected function normalizeDigits(array $fields): void
    {
        $payload = [];

        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $digits = preg_replace('/\D/', '', (string) $this->input($field)) ?? '';
            $payload[$field] = $digits === '' ? null : $digits;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * Accepts "1.234,56", "1234.56" or "R$ 90" and returns a decimal string.
     *
     * @param  list<string>  $fields
     */
    protected function normalizeMoney(array $fields): void
    {
        $payload = [];

        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $raw = trim((string) $this->input($field));

            if ($raw === '') {
                $payload[$field] = null;

                continue;
            }

            $cleaned = preg_replace('/[^\d,.-]/', '', $raw) ?? '';

            // A comma means Brazilian formatting, so dots are thousand separators.
            if (str_contains($cleaned, ',')) {
                $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
            }

            $payload[$field] = $cleaned;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
