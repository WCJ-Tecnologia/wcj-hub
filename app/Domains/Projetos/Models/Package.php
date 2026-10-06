<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pacote = versão (P41–P44; docs/banco.txt, packages). Estado derivado das duas datas (R10). */
class Package extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'planned_release_on',
        'homologation_started_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_release_on' => 'date',
            'homologation_started_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
