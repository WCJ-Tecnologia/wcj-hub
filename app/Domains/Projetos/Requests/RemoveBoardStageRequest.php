<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use Illuminate\Foundation\Http\FormRequest;

/** Remover etapa (F2 §04; P12): o destino é obrigatório só quando há cards, e isso o Service decide. */
class RemoveBoardStageRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['destination_id' => ['nullable', 'integer']];
    }

    public function destinationId(): ?int
    {
        $id = $this->validated('destination_id');

        return $id === null ? null : (int) $id;
    }
}
