<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\Concerns\SprintFields;
use App\Domains\Projetos\Services\TaskAssignmentService;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * *Montar sprint* do quadro, já planejada (D-GER-35): as tarefas escolhidas no Backlog, cada uma
 * com o plano (dev, QA, horas previstas, prioridade; tudo opcional, só muda a chave que vier), e a
 * sprint proposta quando o projeto não tem aberta (`new_sprint`, os mesmos campos do arrastar, R5).
 * Tarefas do projeto da rota e dev e QA alocados nele, conferidos numa consulta cada, com o erro
 * na linha do card. Autoriza antes de validar: `manageTasks` e, quando vem `new_sprint`, também
 * `create` da SprintPolicy, cada negação auditada (G3).
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
        return [
            'tasks' => ['required', 'array', 'min:1', 'max:'.self::MAX_TASKS],
            'tasks.*' => ['array'],
            'tasks.*.id' => ['required', 'integer', 'distinct'],
            'tasks.*.developer_id' => ['nullable', 'integer'],
            'tasks.*.qa_id' => ['nullable', 'integer'],
            'tasks.*.estimated_hours' => ['nullable', 'numeric', 'gt:0', 'max:9999.99', 'decimal:0,2'],
            'tasks.*.priority' => ['sometimes', 'string', Rule::in(array_keys(TaskAssignmentService::PRIORITIES))],
            'new_sprint' => ['nullable', 'array'],
            ...($this->filled('new_sprint') ? $this->sprintRules('new_sprint.') : []),
        ];
    }

    /** As tarefas são do projeto e dev e QA estão alocados nele (P5, R15): uma consulta cada, para o lote todo. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $project = $this->route('project');
            assert($project instanceof Project);
            $tasks = (array) $this->input('tasks', []);

            $ids = array_map(fn ($item) => (int) $item['id'], $tasks);
            if (Task::query()->where('project_id', $project->id)->whereKey($ids)->count() !== count($ids)) {
                $validator->errors()->add('tasks', 'Alguma tarefa selecionada não é deste projeto.');

                return;
            }

            $people = [];
            foreach ($tasks as $item) {
                foreach (['developer_id', 'qa_id'] as $field) {
                    if (isset($item[$field])) {
                        $people[] = (int) $item[$field];
                    }
                }
            }
            $allocated = array_flip(app(TaskAssignmentService::class)->allocated($project->id, $people));
            foreach ($tasks as $index => $item) {
                foreach (['developer_id' => 'O dev', 'qa_id' => 'O QA'] as $field => $who) {
                    if (isset($item[$field]) && ! isset($allocated[(int) $item[$field]])) {
                        $validator->errors()->add("tasks.{$index}.{$field}", "{$who} ".TaskAssignmentService::NOT_ALLOCATED.'.');
                    }
                }
            }
        }];
    }

    /**
     * O plano de cada tarefa, só com as chaves que vieram.
     *
     * @return list<array{id: int, developer_id?: ?int, qa_id?: ?int, estimated_hours?: ?string, priority?: string}>
     */
    public function plan(): array
    {
        return array_values(array_map(function (array $item) {
            $plan = ['id' => (int) $item['id']];
            foreach (['developer_id', 'qa_id'] as $field) {
                if (array_key_exists($field, $item)) {
                    $plan[$field] = $item[$field] === null ? null : (int) $item[$field];
                }
            }
            if (array_key_exists('estimated_hours', $item)) {
                $plan['estimated_hours'] = $item['estimated_hours'] === null ? null : (string) $item['estimated_hours'];
            }
            if (array_key_exists('priority', $item)) {
                $plan['priority'] = (string) $item['priority'];
            }

            return $plan;
        }, (array) $this->validated('tasks')));
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
            'tasks.required' => 'Selecione ao menos uma tarefa.',
            'tasks.min' => 'Selecione ao menos uma tarefa.',
            'tasks.max' => 'Selecione até '.self::MAX_TASKS.' tarefas por vez.',
            'tasks.*.estimated_hours.gt' => 'Informe horas maiores que zero',
            ...$this->sprintMessages('new_sprint.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tasks' => 'tarefas',
            'tasks.*.id' => 'tarefa',
            'tasks.*.developer_id' => 'dev',
            'tasks.*.qa_id' => 'QA',
            'tasks.*.estimated_hours' => 'horas previstas',
            'tasks.*.priority' => 'prioridade',
            ...$this->sprintAttributes('new_sprint.'),
        ];
    }
}
