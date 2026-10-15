<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use App\Domains\Projetos\Services\BoardStageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criar e editar etapa (F2 §02, aba Etapas; P11). Na etapa nova só os papéis que podem se
 * repetir; na edição o papel é opcional (a etapa de papel único nem o manda) e o Service
 * decide se ele ainda pode mudar (P8).
 */
class StoreBoardStageRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $editing = $this->route('board_stage') instanceof BoardStage;

        return [
            'name' => ['required', 'string', 'max:60'],
            'stage_role_id' => [$editing ? 'sometimes' : 'required', 'integer', Rule::in(BoardStageService::REPEATABLE_ROLES)],
        ];
    }

    /**
     * @return array{name: string, stage_role_id?: int}
     */
    public function stageData(): array
    {
        $data = $this->validated();
        $data['name'] = trim($data['name']);
        if (array_key_exists('stage_role_id', $data)) {
            $data['stage_role_id'] = (int) $data['stage_role_id'];
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['stage_role_id.in' => 'Escolha Desenvolvimento, Code review, Teste, Pronto ou Pausa: Fila, Sprint e Suporte existem uma vez só.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nome', 'stage_role_id' => 'papel'];
    }
}
