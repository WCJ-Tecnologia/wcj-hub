<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\AiAuthorization;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Toda mudança de etapa (P14; docs/banco.md, task_stage_movements). Imutável: só created_at.
 * Retrabalho = rework_origin_id preenchido, e só isso (P39).
 */
class TaskStageMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'task_id',
        'moved_by',
        'ai_authorization_id',
        'from_board_stage_id',
        'to_board_stage_id',
        'rework_origin_id',
        'rework_reason_id',
        'reason_text',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function aiAuthorization(): BelongsTo
    {
        return $this->belongsTo(AiAuthorization::class);
    }

    public function fromBoardStage(): BelongsTo
    {
        return $this->belongsTo(BoardStage::class, 'from_board_stage_id');
    }

    public function toBoardStage(): BelongsTo
    {
        return $this->belongsTo(BoardStage::class, 'to_board_stage_id');
    }

    public function reworkOrigin(): BelongsTo
    {
        return $this->belongsTo(ValidationType::class, 'rework_origin_id');
    }

    public function reworkReason(): BelongsTo
    {
        return $this->belongsTo(ReworkReason::class);
    }
}
