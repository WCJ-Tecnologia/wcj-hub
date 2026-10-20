<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Enums\TagColorEnum;
use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criar e editar área (F2 §02, aba Áreas; P18), na configuração e pela etiqueta do quadro (F3 §11
 * item 9, com a cor). A configuração não manda a cor: sem a chave, a cor fica como está. O
 * repetido no projeto o Service recusa, com o nome.
 */
class StoreProjectAreaRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'color' => ['sometimes', 'nullable', Rule::enum(TagColorEnum::class)],
        ];
    }

    public function name(): string
    {
        return trim($this->validated('name'));
    }

    /**
     * @return array{color?: ?string}
     */
    public function color(): array
    {
        return $this->has('color') ? ['color' => $this->validated('color')] : [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nome', 'color' => 'cor'];
    }
}
