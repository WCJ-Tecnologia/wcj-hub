<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Sprint;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Horas previstas na linha da tarefa da sprint, pelo gestor e pelo Administrador (P27; F5 §03):
 * vazio limpa; com valor, maior que 0, com até duas casas.
 */
class EstimateTaskRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    protected function abilityAndArguments(): array
    {
        return ['manageTasks', [Sprint::class, $this->route('project')]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['estimated_hours' => ['nullable', 'numeric', 'gt:0', 'max:9999.99', 'decimal:0,2']];
    }

    public function hours(): ?string
    {
        $value = $this->validated('estimated_hours');

        return $value === null ? null : (string) $value;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['estimated_hours.gt' => 'Informe horas maiores que zero'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['estimated_hours' => 'horas previstas'];
    }
}
