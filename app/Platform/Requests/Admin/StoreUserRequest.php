<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use App\Platform\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Convidar e editar usuário (F1 §02; N1 a N3, IA4). Desativar e reativar têm rota própria (D3):
 * a situação não vem do formulário.
 */
class StoreUserRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    /** Convidar (sem usuário na rota) ou editar. */
    protected function abilityAndArguments(): array
    {
        $user = $this->route('user');

        return $user === null ? ['create', User::class] : ['update', $user];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('daily_hours') === '') {
            $this->merge(['daily_hours' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'role_id' => ['required', 'integer', Rule::in(RoleEnum::values())],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email,'.($id ?? 'NULL')],
            // Mesmos limites do CHECK de users.daily_hours; obrigatória para Desenvolvedor (N3).
            'daily_hours' => [
                Rule::requiredIf(fn () => (int) $this->input('role_id') === RoleEnum::DEVELOPER->value),
                'nullable', 'numeric', 'gt:0', 'lte:24',
            ],
            'ai_write_allowed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daily_hours.required' => UserService::DAILY_HOURS_REQUIRED,
            'email.unique' => UserService::EMAIL_TAKEN,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role_id' => 'papel',
            'name' => 'nome',
            'email' => 'e-mail',
            'daily_hours' => 'carga diária',
            'ai_write_allowed' => 'IA pode escrever',
        ];
    }
}
