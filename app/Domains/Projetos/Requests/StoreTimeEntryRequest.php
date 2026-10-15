<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\TimeEntry;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use App\Support\DiasUteis;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Lançar e corrigir horas (F5 §02 "Lançar horas"; P30): data até hoje, no fuso de quem usa
 * (F5 §10.12), e horas decimais maiores que 0 e até 24. A pessoa não vem no pedido: é sempre
 * quem está logado (F5 §10.14). Lançar autoriza `create` na tarefa da rota; corrigir, `update`
 * no lançamento da rota.
 */
class StoreTimeEntryRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    protected function abilityAndArguments(): array
    {
        $entry = $this->route('time_entry');

        return $entry instanceof TimeEntry ? ['update', $entry] : ['create', [TimeEntry::class, $this->route('task')]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'worked_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.DiasUteis::today()],
            'hours' => ['required', 'numeric', 'gt:0', 'lte:24', 'decimal:0,2'],
        ];
    }

    /**
     * @return array{worked_on: string, hours: string}
     */
    public function entry(): array
    {
        return ['worked_on' => (string) $this->validated('worked_on'), 'hours' => (string) $this->validated('hours')];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'worked_on.before_or_equal' => 'A data não pode ser futura',
            'hours.gt' => 'Informe horas maiores que 0 e até 24',
            'hours.lte' => 'Informe horas maiores que 0 e até 24',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['worked_on' => 'data', 'hours' => 'horas'];
    }
}
