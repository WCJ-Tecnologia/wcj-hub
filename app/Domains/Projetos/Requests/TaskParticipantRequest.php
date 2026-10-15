<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;

/** Pôr ou tirar participante pelo *+* do card (F3 §03; P58): editar a ficha, qualquer alocado. */
class TaskParticipantRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        return ['update', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['user_id' => ['required', 'integer'], 'add' => ['required', 'boolean']];
    }
}
