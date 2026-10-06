<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Gravação única da auditoria (G3; docs/banco.txt, audit_logs). Por ora só a tela grava
 * (ai_authorization_id nulo); o canal da IA e a tentativa negada entram com o B1 e o B8.
 */
class AuditService
{
    /** Senha e token nunca entram em changes (G3). */
    private const NEVER_AUDITED = ['password', 'remember_token', 'token'];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        AuditActionEnum $action,
        Model $auditable,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
        ?int $aiAuthorizationId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'ai_authorization_id' => $aiAuthorizationId,
            'action' => $action->value,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'changes' => $this->changes($before, $after),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * {"campo": [antes, depois]}, só com o que mudou. Criação: antes nulo; exclusão: depois nulo.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @return array<string, array{0: mixed, 1: mixed}>|null
     */
    private function changes(?array $before, ?array $after): ?array
    {
        $changes = [];

        foreach (array_unique([...array_keys($before ?? []), ...array_keys($after ?? [])]) as $field) {
            if (in_array($field, self::NEVER_AUDITED, true)) {
                continue;
            }

            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($old !== $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes === [] ? null : $changes;
    }
}
