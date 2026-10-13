<?php

namespace App\Domains\Projetos\Models;

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
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function acceptanceCriterion(): BelongsTo
    {
        return $this->belongsTo(TaskAcceptanceCriterion::class, 'task_acceptance_criterion_id');
    }
}
