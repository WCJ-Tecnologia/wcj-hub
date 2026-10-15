<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use App\Domains\Projetos\Services\ProjectQuery;
use App\Platform\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alocar pessoas (F2 §02, aba Pessoas; P5): usuários ativos com papel Desenvolvedor, Gestor de
 * projetos ou Administrador que ainda não estão no projeto, um ou vários de uma vez.
 */
class AllocateMembersRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct', $this->eligible()],
        ];
    }

    /**
     * @return list<int>
     */
    public function userIds(): array
    {
        return array_map('intval', $this->validated('user_ids'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'Escolha pelo menos uma pessoa.',
            'user_ids.min' => 'Escolha pelo menos uma pessoa.',
            'user_ids.*.distinct' => 'A mesma pessoa aparece duas vezes.',
        ];
    }

    private function eligible(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = User::query()->active()->whereIn('role_id', ProjectQuery::MEMBER_ROLES)->find($value, ['id', 'name']);
            if ($user === null) {
                $fail('Escolha um usuário ativo com papel Desenvolvedor, Gestor de projetos ou Administrador.');

                return;
            }
            if ($this->project()->members()->where('user_id', $user->id)->exists()) {
                $fail("{$user->name} já está no projeto.");
            }
        };
    }
}
