<?php

namespace App\Platform\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Meu perfil: a própria conta de quem está logado (lista "da própria conta" do A5), sem Policy.
 * Trocar a senha pede a senha atual: uma sessão esquecida aberta não basta para tomar a conta
 * (B9, SEC-04). Só o nome não pede nada. O limite é o "senha-atual" da rota (SEC-05).
 */
class UpdateProfileRequest extends FormRequest
{
    public const WRONG_CURRENT_PASSWORD = 'A senha atual não confere.';

    public const MISSING_CURRENT_PASSWORD = 'Digite a senha atual para trocar a senha.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sem senha nova, a senha atual nem é conferida: salvar só o nome não pede nada.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('password')) {
            $this->merge([
                'password' => null,
                'password_confirmation' => null,
                'current_password' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'current_password' => ['nullable', 'required_with:password', 'string', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required_with' => self::MISSING_CURRENT_PASSWORD,
            'current_password.current_password' => self::WRONG_CURRENT_PASSWORD,
        ];
    }
}
