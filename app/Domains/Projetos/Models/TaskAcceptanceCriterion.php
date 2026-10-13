<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Critério de aceite = item do roteiro de QA (P17, P36; docs/banco.md, task_acceptance_criteria).
 * number é estável (T9); parent_key é coluna gerada (VIRTUAL): nunca se grava.
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
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'parent_key' => 'integer',
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

    public function evidences(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }
}
