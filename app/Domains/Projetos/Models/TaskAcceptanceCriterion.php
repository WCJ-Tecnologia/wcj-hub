<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Critério de aceite = item do roteiro de QA (P17, P36; docs/banco.md, task_acceptance_criteria).
 * number é estável (T9); parent_key é coluna gerada (VIRTUAL): nunca se grava. marked_by e
 * marked_at: a última marcação do resultado (F4 §02; B4), só pelo CriterionService.
 */
class TaskAcceptanceCriterion extends Model
{
    protected $table = 'task_acceptance_criteria';

    protected $fillable = [
        'task_id',
        'parent_id',
        'number',
        'description',
        'test_result_id',
        'marked_by',
        'marked_at',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'parent_key' => 'integer',
            'marked_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }
}
