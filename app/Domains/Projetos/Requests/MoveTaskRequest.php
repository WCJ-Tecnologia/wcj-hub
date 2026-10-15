<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use App\Domains\Projetos\Requests\Concerns\SprintFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mover o card (`MovePayload` do kit; F3 §04; P13, P15, P39, P40, P45, R5). A TaskPolicy decide
 * com o destino pedido (sair do Backlog ou entrar nele é de quem gere); destino que não é etapa do
 * projeto confere só o acesso e cai na validação.
 */
class MoveTaskRequest extends FormRequest
{
    use ActsOnCard, SprintFields;

    protected function abilityAndArguments(): array
    {
        $to = BoardStage::query()->where('project_id', $this->card()->project_id)->find($this->integer('stage_id'));

        return ['move', [$this->card(), $to]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'integer', Rule::exists('board_stages', 'id')->where('project_id', $this->card()->project_id)],
            'position' => ['nullable', 'integer', 'min:0'],
            'sequence' => ['nullable', 'array', 'max:2000'],
            'sequence.*' => ['integer'],
            'pause_reason' => ['nullable', 'string', 'max:500'],
            'mistake' => ['boolean'],
            'rework_origin_id' => ['nullable', 'integer'],
            'rework_reason_id' => ['nullable', 'integer'],
            'rework_reason_text' => ['nullable', 'string', 'max:500'],
            'new_sprint' => ['nullable', 'array'],
            ...($this->filled('new_sprint') ? $this->sprintRules('new_sprint.') : []),
        ];
    }

    public function stage(): BoardStage
    {
        return BoardStage::query()->findOrFail($this->integer('stage_id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $sequence = $this->validated('sequence');

        return [
            'sequence' => $sequence === null ? null : array_map('intval', $sequence),
            'pause_reason' => $this->validated('pause_reason'),
            'mistake' => $this->boolean('mistake'),
            'rework_origin_id' => $this->validated('rework_origin_id'),
            'rework_reason_id' => $this->validated('rework_reason_id'),
            'rework_reason_text' => $this->validated('rework_reason_text'),
            'new_sprint' => $this->filled('new_sprint') ? $this->sprintData((array) $this->validated('new_sprint')) : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['stage_id.exists' => 'Escolha uma etapa do quadro deste projeto.', ...$this->sprintMessages('new_sprint.')];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['pause_reason' => 'motivo da pausa', 'rework_reason_text' => 'texto do motivo', ...$this->sprintAttributes('new_sprint.')];
    }
}
