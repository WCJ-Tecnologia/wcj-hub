<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cadastrável por projeto (P18, G7; docs/banco.md, project_areas). Cor: um dos 10 nomes de TagColor; nulo = cinza (14/10). */
class ProjectArea extends Model
{
    use Deactivatable;

    protected $fillable = ['project_id', 'name', 'color'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
