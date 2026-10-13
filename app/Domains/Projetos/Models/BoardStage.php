<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa do quadro por projeto (P8, P10–P12; docs/banco.md, board_stages). single_role_key é
 * coluna gerada (STORED): nunca se grava.
 */
class BoardStage extends Model
{
    protected $fillable = [
        'project_id',
        'stage_role_id',
        'name',
        'position',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'archived_at' => 'datetime',
            'single_role_key' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stageRole(): BelongsTo
    {
        return $this->belongsTo(StageRole::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
