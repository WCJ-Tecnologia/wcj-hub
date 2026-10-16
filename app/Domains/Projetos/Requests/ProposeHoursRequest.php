<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;

/** O dev propõe horas (P27; F3 §04): maior que zero; nulo retira a proposta. */
class ProposeHoursRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        return ['proposeHours', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['hours' => ['present', 'nullable', 'numeric', 'gt:0', 'max:9999.99']];
    }

    public function hours(): ?string
    {
        $hours = $this->validated('hours');

        return $hours === null ? null : (string) $hours;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['hours' => 'horas'];
    }
}
