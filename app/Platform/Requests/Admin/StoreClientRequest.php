<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Services\ClientService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Criar e editar cliente (F1 §02; N5). As regras são as do ClientService, que valida de novo
 * porque o gestor também cria por ele (N6): um lugar só para a regra, os dois pontos de entrada.
 */
class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ClientService::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ClientService::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ClientService::attributes($this->all());
    }
}
