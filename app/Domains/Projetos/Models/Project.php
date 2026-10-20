<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Projeto (P1–P3, P7–P9; docs/banco.md, projects). last_task_number é o contador da chave
 * (T4): só o Service o incrementa, por SQL atômico, e por isso não é preenchível.
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

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

    // Fora de App\Models, o Laravel não acha a factory pelo nome (ADR-001).
    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

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

    /** Tipos de card do projeto (P18, 14/10). */
    public function taskTypes(): HasMany
    {
        return $this->hasMany(TaskType::class);
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

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /**
     * Quando a lista carregou `members` filtrado pela pessoa (ProjectQuery), responde sem
     * consulta; fora dela, pergunta ao banco. A relação carregada só traz o vínculo de quem vê.
     */
    public function hasMember(User $user): bool
    {
        if ($this->relationLoaded('members')) {
            return $this->members->contains('user_id', $user->id);
        }

        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Os projetos que a pessoa vê (P5): o Administrador, todos; o gestor, os que gere (é o
     * responsável ou está alocado); o dev, os em que está alocado; o Comercial, nenhum (C14).
     * Espelha ProjectPolicy::view: a lista e o acesso por ID não podem divergir.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $allocated = fn (Builder $members) => $members->where('user_id', $user->id);

        match ($user->type()) {
            RoleEnum::ADMIN => null,
            RoleEnum::PROJECT_MANAGER => $query->where(fn (Builder $inner) => $inner
                ->where('manager_id', $user->id)
                ->orWhereHas('members', $allocated)),
            RoleEnum::DEVELOPER => $query->whereHas('members', $allocated),
            RoleEnum::SALES => $query->whereKey([]),
        };
    }
}
