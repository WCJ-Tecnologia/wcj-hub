<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;

/** Arquivar o card (F3 §02 "Diálogo de arquivar"; P26): o motivo é obrigatório, até 500. */
class ArchiveTaskRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        return ['archive', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['archive_reason' => ['required', 'string', 'max:500']];
    }

    public function reason(): string
    {
        return trim($this->validated('archive_reason'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['archive_reason' => 'motivo'];
    }
}
