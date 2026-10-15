<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Requests\Concerns\SprintFields;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * *Montar sprint* do quadro (D-GER-35): as tarefas escolhidas no Backlog, todas do projeto da
 * rota (conferidas numa consulta só), e a sprint proposta, quando o projeto não tem aberta
 * (`new_sprint`, os mesmos campos do arrastar, R5). Autoriza antes de validar: `manageTasks` e,
 * quando vem `new_sprint`, também `create` da SprintPolicy, cada negação auditada (G3).
 */
class SprintTasksBatchRequest extends FormRequest
{
    use AuthorizesBeforeValidation, SprintFields;

    /** Um Backlog inteiro de projeto grande cabe num lote (o benchmark do quadro é de 500 cards, B3). */
    public const MAX_TASKS = 500;

    protected function abilityAndArguments(): array
    {
        return ['manageTasks', [Sprint::class, $this->route('project')]];
    }

    public function authorize(): Response
    {
        [$ability, $arguments] = $this->abilityAndArguments();
        $response = $this->inspectAndAuditDenial($ability, $arguments);

        return $response->allowed() && $this->filled('new_sprint')
            ? $this->inspectAndAuditDenial('create', $arguments)
            : $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        assert($project instanceof Project);

        return [
            'task_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_TASKS, Rule::exists('tasks', 'id')->where('project_id', $project->id)],
            'task_ids.*' => ['integer', 'distinct'],
            'new_sprint' => ['nullable', 'array'],
            ...($this->filled('new_sprint') ? $this->sprintRules('new_sprint.') : []),
        ];
    }

    /**
     * @return list<int>
     */
    public function taskIds(): array
    {
        return array_map('intval', array_values((array) $this->validated('task_ids')));
    }

    /**
     * @return array{name: string, goal: ?string, starts_on: string, ends_on: string}|null
     */
    public function newSprint(): ?array
    {
        return $this->filled('new_sprint') ? $this->sprintData((array) $this->validated('new_sprint')) : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'task_ids.required' => 'Selecione ao menos uma tarefa.',
            'task_ids.min' => 'Selecione ao menos uma tarefa.',
            'task_ids.max' => 'Selecione até '.self::MAX_TASKS.' tarefas por vez.',
            'task_ids.exists' => 'Alguma tarefa selecionada não é deste projeto.',
            ...$this->sprintMessages('new_sprint.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['task_ids' => 'tarefas', 'task_ids.*' => 'tarefa', ...$this->sprintAttributes('new_sprint.')];
    }
}
