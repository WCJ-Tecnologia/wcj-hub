<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editar o card (`TaskPatchPayload` do kit; F3 §02 e §04): a ficha e as etiquetas do quadro. Só as
 * chaves enviadas mudam; nulo limpa. Qualquer alocado edita (TaskPolicy::update); horas previstas
 * direto, só quem gere (SprintPolicy::manageTasks, P27): com a chave, a ability conferida é essa.
 * "Do projeto e ativo" é dos Services (R15).
 */
class UpdateTaskRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        return $this->has('estimated_hours')
            ? ['manageSprint', [Task::class, $this->card()->project]]
            : ['update', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'type_id' => ['sometimes', 'nullable', 'integer'],
            'area_id' => ['sometimes', 'nullable', 'integer'],
            // A prioridade se escolhe, não se tira (o plano do B5b e o menu do card).
            'priority' => ['sometimes', 'required', Rule::exists('task_priorities', 'slug')],
            'developer_id' => ['sometimes', 'nullable', 'integer'],
            'qa_id' => ['sometimes', 'nullable', 'integer'],
            'story_as' => ['sometimes', 'nullable', 'string', 'max:200'],
            'story_want' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'story_so_that' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'origin_task_id' => ['sometimes', 'nullable', 'integer'],
            'parent_task_id' => ['sometimes', 'nullable', 'integer'],
            'cover_attachment_id' => ['sometimes', 'nullable', 'integer'],
            'estimated_hours' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:9999.99'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $changes = $this->validated();
        foreach (['type_id', 'area_id', 'developer_id', 'qa_id', 'origin_task_id', 'parent_task_id', 'cover_attachment_id'] as $key) {
            if (array_key_exists($key, $changes) && $changes[$key] !== null) {
                $changes[$key] = (int) $changes[$key];
            }
        }
        foreach (['title', 'story_as', 'story_want', 'story_so_that', 'description'] as $key) {
            if (array_key_exists($key, $changes) && $changes[$key] !== null) {
                $changes[$key] = trim($changes[$key]) === '' && $key !== 'title' ? null : trim($changes[$key]);
            }
        }

        return $changes;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título', 'story_as' => 'Sendo', 'story_want' => 'Quero', 'story_so_that' => 'Para',
            'description' => 'informações adicionais', 'estimated_hours' => 'horas previstas',
        ];
    }
}
