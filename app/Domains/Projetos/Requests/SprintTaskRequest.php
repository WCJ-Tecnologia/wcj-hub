<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Requests\Concerns\SprintFields;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pôr card na sprint aberta (P45; R5): o card, do projeto da rota, e a sprint proposta, quando o
 * projeto não tem aberta (`new_sprint`, o mesmo `MovePayload.new_sprint` do kit do quadro). O
 * quadro do B3 liga o arrastar do Backlog para o To do nesta mesma regra.
 */
class SprintTaskRequest extends FormRequest
{
    use AuthorizesBeforeValidation, SprintFields;

    protected function abilityAndArguments(): array
    {
        return ['manageTasks', [Sprint::class, $this->route('project')]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        assert($project instanceof Project);

        return [
            'task_id' => ['required', 'integer', Rule::exists('tasks', 'id')->where('project_id', $project->id)],
            'new_sprint' => ['nullable', 'array'],
            ...($this->filled('new_sprint') ? $this->sprintRules('new_sprint.') : []),
        ];
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
        return ['task_id.exists' => 'O card não é deste projeto.', ...$this->sprintMessages('new_sprint.')];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['task_id' => 'card', ...$this->sprintAttributes('new_sprint.')];
    }
}
