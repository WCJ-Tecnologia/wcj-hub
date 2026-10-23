<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use App\Domains\Projetos\Services\CriterionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Marcar o resultado de um item do roteiro de QA (P36; F4 §04; B4): `result` no vocabulário da
 * tela (`pending`, `approved`, `failed`). Autoriza `runQa` no card da rota antes de validar; o
 * critério de outro card é 404, antes da Policy (como na ficha, CR-1-04 do B3 PR 2).
 */
class CriterionResultRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        $criterion = $this->route('acceptanceCriterion');
        abort_if($criterion !== null && (int) $criterion->task_id !== $this->card()->id, 404);

        return ['runQa', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['result' => ['required', 'string', Rule::in(array_keys(CriterionService::RESULTS))]];
    }

    public function result(): string
    {
        return $this->validated('result');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['result.in' => 'Escolha Aprovado, Reprovado ou A testar.'];
    }
}
