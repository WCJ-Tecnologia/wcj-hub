<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Project;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criar e editar projeto (F2 §02; P1–P3). O prefixo repetido não é conferido aqui: o unique do
 * banco recusa, e o ProjectService traduz para a mensagem com o nome do projeto dono (P3). O
 * cliente só entra na criação (P1): na edição ele nem é validado, então não chega ao Service.
 */
class StoreProjectRequest extends FormRequest
{
    public const DELIVERY = 'delivery';

    public const CONTINUOUS = 'continuous';

    /** Quem não pode recebe 403 antes da validação: não descobre nada pelos erros dos campos. */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            ? (bool) $this->user()?->can('manage', $project)
            : (bool) $this->user()?->can('create', Project::class);
    }

    protected function prepareForValidation(): void
    {
        $dueOn = $this->input('due_on');

        $this->merge([
            // F2 §10.2: gravado em maiúsculas, sem os espaços das pontas.
            'key_prefix' => mb_strtoupper(trim((string) $this->input('key_prefix'))),
            // Produto contínuo não tem entrega (P2): a data some mesmo que venha.
            'due_on' => $this->input('type') === self::CONTINUOUS || $dueOn === '' ? null : $dueOn,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            ...($project instanceof Project ? [] : ['client_id' => ['required', 'integer', $this->activeClient()]]),
            'manager_id' => ['required', 'integer', $this->eligibleManager($project instanceof Project ? $project : null)],
            // Letras sem acento, números e hífen, até 10 (F2 §10.2): a chave "SGM-17" sai daqui.
            'key_prefix' => ['required', 'string', 'regex:/^[A-Z0-9-]{1,10}$/'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in([self::DELIVERY, self::CONTINUOUS])],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'due_on' => ['nullable', 'required_if:type,'.self::DELIVERY, 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * Os campos que o ProjectService grava, sem o tipo: ele não é guardado à parte, é a entrega
     * preenchida ou vazia (P2).
     *
     * @return array<string, mixed>
     */
    public function projectData(): array
    {
        $data = $this->validated();
        unset($data['type']);

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key_prefix.regex' => 'Use até 10 letras sem acento, números ou hífen, sem espaço.',
            'due_on.required_if' => 'Informe a entrega do projeto de entrega.',
            'due_on.after_or_equal' => 'A entrega não pode ser antes do início.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'manager_id' => 'gestor responsável',
            'key_prefix' => 'prefixo',
            'name' => 'nome',
            'type' => 'tipo',
            'starts_on' => 'início',
            'due_on' => 'entrega',
        ];
    }

    /** Cliente desativado sai da lista de escolha (N11). */
    private function activeClient(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! Client::query()->active()->whereKey($value)->exists()) {
                $fail('Escolha um cliente ativo.');
            }
        };
    }

    /**
     * Gestor responsável: usuário ativo com papel Gestor de projetos ou Administrador (P1). Na
     * edição, manter o responsável atual não passa por aqui: só o gestor novo é conferido.
     */
    private function eligibleManager(?Project $project): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($project): void {
            if ($project !== null && (int) $value === $project->manager_id) {
                return;
            }

            $eligible = User::query()
                ->active()
                ->whereKey($value)
                ->whereIn('role_id', [RoleEnum::ADMIN->value, RoleEnum::PROJECT_MANAGER->value])
                ->exists();

            if (! $eligible) {
                $fail('Escolha um usuário ativo com papel Gestor de projetos ou Administrador.');
            }
        };
    }
}
