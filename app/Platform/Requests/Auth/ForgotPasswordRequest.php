<?php

namespace App\Platform\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tela pública, sem sessão: não há Policy a perguntar. O limite é o "login" da rota.
 */
class ForgotPasswordRequest extends FormRequest
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
        return ['email' => ['required', 'email']];
    }
}
