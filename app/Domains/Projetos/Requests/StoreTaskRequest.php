<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Task;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;

/** Card rápido (F3 §04; P17, P56): só o título, até 200 caracteres. */
class StoreTaskRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    protected function abilityAndArguments(): array
    {
        return ['create', [Task::class, $this->route('project')]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:200']];
    }

    public function title(): string
    {
        return trim($this->validated('title'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'título'];
    }
}
