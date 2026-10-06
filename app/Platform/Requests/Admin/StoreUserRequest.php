<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajuste mínimo do B0 à seção 9 do banco.txt (papel e carga diária). A tela completa de
 * usuários, com as regras N1 a N4, é do B1.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            // Mesmos limites do CHECK de users.daily_hours.
            'daily_hours' => ['nullable', 'numeric', 'gt:0', 'lte:24'],
            'active' => ['boolean'],
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
        ];
    }
}
