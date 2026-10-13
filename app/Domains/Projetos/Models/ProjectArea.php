<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cadastrável por projeto (P18, G7; docs/banco.md, project_areas). */
class ProjectArea extends Model
{
    use Deactivatable;

    protected $fillable = ['project_id', 'name'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
