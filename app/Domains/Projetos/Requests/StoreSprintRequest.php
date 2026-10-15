<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Requests\Concerns\SprintFields;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;

/** Criar e editar sprint pela tela de sprints (F5 §02, §04). Autoriza antes de validar (G3). */
class StoreSprintRequest extends FormRequest
{
    use AuthorizesBeforeValidation, SprintFields;

    protected function abilityAndArguments(): array
    {
        $sprint = $this->route('sprint');

        return $sprint instanceof Sprint ? ['update', $sprint] : ['create', [Sprint::class, $this->route('project')]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->sprintRules();
    }

    /**
     * @return array{name: string, goal: ?string, starts_on: string, ends_on: string}
     */
    public function sprint(): array
    {
        return $this->sprintData($this->validated());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->sprintMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->sprintAttributes();
    }
}
