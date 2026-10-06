<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alocação (P5; docs/banco.txt, project_members). Vínculo: removido de verdade. */
class ProjectMember extends Model
{
    protected $fillable = ['project_id', 'user_id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
