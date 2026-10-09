<?php

namespace App\Platform\Models;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\Concerns\Deactivatable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Pessoa de um cliente (N8) e "quem pediu" no card de suporte (P52; docs/banco.txt, contacts). */
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use Deactivatable, HasFactory;

    /** O nome que fica depois da anonimização (N12; F1 §04). */
    public const ANONYMIZED_NAME = 'removido a pedido do titular';

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
    ];

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** As linhas de audit_logs deste contato (não confundir com User::auditLogs, que é a autoria). */
    public function auditTrail(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * D5: "Anonimizado" não tem coluna (banco.txt); é a linha anonymized na auditoria do contato.
     * Lista carrega com withAnonymized() para não consultar uma vez por linha.
     */
    public function isAnonymized(): bool
    {
        if (array_key_exists('anonymized', $this->attributes)) {
            return (bool) $this->attributes['anonymized'];
        }

        return $this->auditTrail()
            ->where('action', AuditActionEnum::ANONYMIZED->value)
            ->where('outcome', AuditLog::OUTCOME_SUCCESS)
            ->exists();
    }

    /** @param  Builder<static>  $query */
    public function scopeWithAnonymized(Builder $query): void
    {
        $query->withExists(['auditTrail as anonymized' => fn (Builder $logs) => $logs
            ->where('action', AuditActionEnum::ANONYMIZED->value)
            ->where('outcome', AuditLog::OUTCOME_SUCCESS)]);
    }

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
