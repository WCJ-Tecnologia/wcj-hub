<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anexo da tarefa ou evidência do QA (P24, P36; docs/banco.md, task_attachments). Excluir passa
 * pelo Service, que apaga o arquivo e a linha juntos (banco.md §4).
 */
class TaskAttachment extends Model
{
    protected $fillable = [
        'task_id',
        'task_acceptance_criterion_id',
        'path',
        'original_name',
        'mime_type',
        'user_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function acceptanceCriterion(): BelongsTo
    {
        return $this->belongsTo(TaskAcceptanceCriterion::class, 'task_acceptance_criterion_id');
    }

    /** Quem enviou (spec b3-ficha); nulo nas linhas de antes. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
