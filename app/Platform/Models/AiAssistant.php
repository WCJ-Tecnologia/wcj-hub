<?php

namespace App\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Domínio fechado (IA3, IA4): o slug dá o canal da auditoria, ia:<slug> (IA11, T10).
 * write_allowed = false emite token só de leitura.
 */
class AiAssistant extends Model
{
    public const CLAUDE = 1;

    public const CHATGPT = 2;

    public const CODEX = 3;

    public const GPT = 4;

    protected $fillable = ['slug', 'label', 'write_allowed'];

    protected function casts(): array
    {
        return [
            'write_allowed' => 'boolean',
        ];
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(AiAuthorization::class);
    }
}
