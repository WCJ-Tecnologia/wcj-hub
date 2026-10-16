<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Etiquetas do card pelo quadro (F3 §03; P18, P19): prioridade, área e tipo. Só as chaves
 * enviadas mudam; nulo limpa. "Do projeto e ativo" é do TaskService (R15).
 */
class UpdateTaskTagsRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        return ['update', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // A prioridade se escolhe, não se tira (o plano do B5b e o menu do card).
            'priority' => ['sometimes', 'required', Rule::exists('task_priorities', 'slug')],
            'area_id' => ['sometimes', 'nullable', 'integer'],
            'type_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * @return array{priority?: string, area_id?: ?int, type_id?: ?int}
     */
    public function changes(): array
    {
        $changes = $this->validated();
        foreach (['area_id', 'type_id'] as $key) {
            if (array_key_exists($key, $changes) && $changes[$key] !== null) {
                $changes[$key] = (int) $changes[$key];
            }
        }

        return $changes;
    }
}
