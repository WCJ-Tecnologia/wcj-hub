<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Gravação única da auditoria (G3; docs/banco.txt, audit_logs). Por ora só a tela grava
 * (ai_authorization_id nulo); o canal da IA entra com o B8.
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
            'ip_address' => $this->ip(),
        ]);
    }

    /**
     * A tentativa barrada pela Policy (G3). Alvo Model: o registro; nome de classe: o tipo,
     * com id nulo, porque a negação veio antes de existir ou de achar o registro. Outro texto
     * (gate sem Model) fica como o tipo.
     */
    public function denied(string $ability, Model|string $target): AuditLog
    {
        $type = match (true) {
            $target instanceof Model => $target->getMorphClass(),
            is_subclass_of($target, Model::class) => (new $target)->getMorphClass(),
            default => $target,
        };

        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'ai_authorization_id' => null,
            // audit_logs.action tem 40 caracteres.
            'action' => mb_substr(AuditActionEnum::forAbility($ability)?->value ?? $ability, 0, 40),
            'outcome' => AuditLog::OUTCOME_DENIED,
            'auditable_type' => $type,
            'auditable_id' => $target instanceof Model ? $target->getKey() : null,
            'changes' => null,
            'ip_address' => $this->ip(),
        ]);
    }

    /**
     * Tira as chaves pessoais de changes em toda linha do registro (N12). É a única escrita
     * que altera linha já gravada; quem chama abre a transação da anonimização.
     *
     * @param  list<string>  $fields
     * @return int linhas alteradas
     */
    public function scrubPersonalData(Model $auditable, array $fields): int
    {
        $logs = AuditLog::query()
            ->where('auditable_type', $auditable->getMorphClass())
            ->where('auditable_id', $auditable->getKey())
            ->whereNotNull('changes')
            ->lockForUpdate()
            ->get();

        $changed = 0;
        foreach ($logs as $log) {
            $kept = array_diff_key($log->changes, array_flip($fields));
            if (count($kept) === count($log->changes)) {
                continue;
            }

            $log->changes = $kept === [] ? null : $kept;
            $log->save();
            $changed++;
        }

        return $changed;
    }

    // A4 (divida.php): o único ponto do Service que lê a requisição.
    private function ip(): ?string
    {
        return request()->ip();
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
