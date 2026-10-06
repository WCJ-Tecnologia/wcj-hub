<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Projeto (P1–P3, P7–P9; docs/banco.txt, projects). last_task_number é o contador da chave
 * (T4): só o Service o incrementa, por SQL atômico, e por isso não é preenchível.
 */
class Project extends Model
{
    protected $fillable = [
        'client_id',
        'manager_id',
        'key_prefix',
        'name',
        'starts_on',
        'due_on',
        'finished_at',
        'require_pause_reason',
        'sprint_days',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'due_on' => 'date',
            'finished_at' => 'datetime',
            'last_task_number' => 'integer',
            'require_pause_reason' => 'boolean',
            'sprint_days' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withTimestamps();
    }

    public function boardStages(): HasMany
    {
        return $this->hasMany(BoardStage::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(ProjectArea::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
