<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use Illuminate\Foundation\Http\FormRequest;

/** Padrões do projeto (F2 §02, aba Padrões; P15, P46): motivo da pausa e duração da sprint em dias úteis. */
class UpdateProjectDefaultsRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'require_pause_reason' => ['required', 'boolean'],
            // smallint unsigned no banco; a regra é só "maior que 0" (F2 §02).
            'sprint_days' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @return array{require_pause_reason: bool, sprint_days: int}
     */
    public function defaults(): array
    {
        return [
            'require_pause_reason' => $this->boolean('require_pause_reason'),
            'sprint_days' => (int) $this->validated('sprint_days'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['sprint_days.min' => 'A duração da sprint é um número de dias úteis maior que 0.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['require_pause_reason' => 'pedir motivo na pausa', 'sprint_days' => 'duração da sprint'];
    }
}
