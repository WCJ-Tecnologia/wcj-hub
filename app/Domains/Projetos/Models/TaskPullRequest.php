<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Link de PR colado na tarefa (P23; docs/banco.md, task_pull_requests). */
class TaskPullRequest extends Model
{
    protected $fillable = ['task_id', 'url', 'user_id'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** Quem colou o link (spec b3-ficha); nulo nas linhas de antes. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
