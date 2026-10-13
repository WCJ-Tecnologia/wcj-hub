<?php

namespace App\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Auditoria única (G3; docs/banco.md, audit_logs). Imutável: só created_at. O canal é
 * ai_authorization_id (null = tela).
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_DENIED = 'denied';

    protected $fillable = [
        'user_id',
        'ai_authorization_id',
        'action',
        'outcome',
        'auditable_type',
        'auditable_id',
        'changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiAuthorization(): BelongsTo
    {
        return $this->belongsTo(AiAuthorization::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
