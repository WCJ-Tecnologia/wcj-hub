<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Cadastrável, lista geral do Administrador (P40, R11; docs/banco.txt, rework_reasons). O código
 * depende só do slug 'other': o "Outro" existe sempre, não se desativa nem se exclui e sempre pede
 * texto (P40). Os outros itens nascem sem slug, como em task_types.
 */
class ReworkReason extends Model
{
    use Deactivatable;

    public const OTHER = 'other';

    protected $fillable = ['slug', 'label', 'requires_text'];

    protected function casts(): array
    {
        return [
            'requires_text' => 'boolean',
        ];
    }

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
