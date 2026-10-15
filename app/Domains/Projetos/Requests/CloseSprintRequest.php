<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\SprintFields;
use App\Domains\Projetos\Services\SprintService;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Encerrar a sprint (F5 §02 "Encerrar sprint"; P50): o destino de cada tarefa não pronta, por id
 * (`destinations[17] = next | backlog`), e a próxima sprint, quando `create_next` é verdadeiro.
 * Se alguma tarefa vai para a próxima, o Service exige a próxima.
 */
class CloseSprintRequest extends FormRequest
{
    use AuthorizesBeforeValidation, SprintFields;

    protected function abilityAndArguments(): array
    {
        return ['close', $this->route('sprint')];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'destinations' => ['array'],
            'destinations.*' => [Rule::in([SprintService::DESTINATION_NEXT, SprintService::DESTINATION_BACKLOG])],
            'create_next' => ['required', 'boolean'],
            ...$this->nextRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function destinations(): array
    {
        $destinations = [];
        foreach ((array) $this->validated('destinations', []) as $id => $destination) {
            $destinations[(int) $id] = (string) $destination;
        }

        return $destinations;
    }

    /**
     * @return array{name: string, goal: ?string, starts_on: string, ends_on: string}|null
     */
    public function next(): ?array
    {
        return $this->boolean('create_next') ? $this->sprintData((array) $this->validated('next')) : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->sprintMessages('next.');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->sprintAttributes('next.');
    }

    /**
     * @return array<string, mixed>
     */
    private function nextRules(): array
    {
        return $this->boolean('create_next') ? ['next' => ['required', 'array'], ...$this->sprintRules('next.')] : [];
    }
}
