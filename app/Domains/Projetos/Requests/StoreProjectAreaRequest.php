<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use Illuminate\Foundation\Http\FormRequest;

/** Criar e editar área (F2 §02, aba Áreas; P18). O repetido no projeto o Service recusa, com o nome. */
class StoreProjectAreaRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:60']];
    }

    public function name(): string
    {
        return trim($this->validated('name'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nome'];
    }
}
