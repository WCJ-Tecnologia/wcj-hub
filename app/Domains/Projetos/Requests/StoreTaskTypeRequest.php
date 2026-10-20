<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Enums\TagColorEnum;
use App\Domains\Projetos\Requests\Concerns\ManagesProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criar e editar tipo do projeto pela etiqueta do quadro (F3 §11 item 9; P18): nome e cor, só
 * quem gere o projeto. O repetido no projeto o Service recusa, com o nome.
 */
class StoreTaskTypeRequest extends FormRequest
{
    use ManagesProject;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'color' => ['nullable', Rule::enum(TagColorEnum::class)],
        ];
    }

    public function name(): string
    {
        return trim($this->validated('name'));
    }

    public function color(): ?string
    {
        return $this->validated('color');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nome', 'color' => 'cor'];
    }
}
