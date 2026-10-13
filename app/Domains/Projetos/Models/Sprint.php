<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Sprint (P45–P51; docs/banco.md, sprints). Aberta = closed_at nulo. */
class Sprint extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'goal',
        'starts_on',
        'ends_on',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'closed_at' => 'datetime',
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
