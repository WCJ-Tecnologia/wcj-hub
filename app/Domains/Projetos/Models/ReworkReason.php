<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;

/** Cadastrável, lista geral do Administrador (P40, R11; docs/banco.txt, rework_reasons). */
class ReworkReason extends Model
{
    use Deactivatable;

    protected $fillable = ['label', 'requires_text'];

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
