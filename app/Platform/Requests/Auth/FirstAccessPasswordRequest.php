<?php

namespace App\Platform\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Troca da senha provisória no primeiro acesso: é a própria conta de quem está logado
 * (lista "da própria conta" do A5), sem Policy. O limite é o "login" da rota.
 */
class FirstAccessPasswordRequest extends FormRequest
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
        return ['password' => ['required', 'confirmed', Password::defaults()]];
    }
}
