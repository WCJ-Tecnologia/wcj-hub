<?php

namespace App\Platform\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tela pública: quem chega aqui ainda não tem sessão, então não há Policy a perguntar.
 * O limite de tentativas é o "login" da rota (AppServiceProvider::registerRateLimiters).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
