<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\ReworkReason;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Criar e editar motivo de retrabalho (F2 §02; P40, G7). Só o Administrador (ReworkReasonPolicy),
 * conferido antes da validação. O repetido o ListService recusa, com o nome.
 */
class StoreReworkReasonRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    protected function abilityAndArguments(): array
    {
        $reason = $this->route('rework_reason');

        return $reason instanceof ReworkReason ? ['update', $reason] : ['create', ReworkReason::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:80'],
            'requires_text' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array{label: string, requires_text: bool}
     */
    public function reasonData(): array
    {
        return ['label' => trim($this->validated('label')), 'requires_text' => $this->boolean('requires_text')];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['label' => 'nome', 'requires_text' => 'pede texto'];
    }
}
