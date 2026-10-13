<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Link de PR colado na tarefa (P23; docs/banco.md, task_pull_requests). */
class TaskPullRequest extends Model
{
    protected $fillable = ['task_id', 'url'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
