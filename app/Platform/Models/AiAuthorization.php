<?php

namespace App\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O "conector" de IA do usuário (IA4, IA6; docs/banco.md, ai_authorizations). Uma ativa por
 * usuário e assistente, travada pela coluna gerada active_assistant_id (T1).
 */
class AiAuthorization extends Model
{
    protected $fillable = [
        'user_id',
        'ai_assistant_id',
        'oauth_client_id',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
            'active_assistant_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiAssistant(): BelongsTo
    {
        return $this->belongsTo(AiAssistant::class);
    }
}
