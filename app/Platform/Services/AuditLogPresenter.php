<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Support\LocalTime;
use App\Support\Paises;
use Carbon\CarbonImmutable;

/**
 * A camada de exibição da auditoria (stacks/ui.md §2): o rótulo técnico gravado (alias do morph
 * map, nome do campo, id do papel) vira a linguagem da tela. Os nomes dos registros chegam
 * prontos do AuditLogQuery, numa consulta por tipo.
 */
class AuditLogPresenter
{
    /** Tipo de registro (alias do morph map) → rótulo. Tipo fora daqui aparece como gravado. */
    private const TYPES = [
        'user' => 'Usuário',
        'client' => 'Cliente',
        'contact' => 'Contato',
        'project' => 'Projeto',
        'task' => 'Card',
        'sprint' => 'Sprint',
        'package' => 'Pacote',
        'ai_authorization' => 'Conexão de IA',
        'audit_log' => 'Auditoria',
    ];

    /** Campo de changes → rótulo do detalhe. Campo fora daqui aparece como gravado. */
    private const FIELDS = [
        'name' => 'Nome',
        'email' => 'E-mail',
        'role_id' => 'Papel',
        'daily_hours' => 'Carga diária',
        'ai_write_allowed' => 'IA pode escrever',
        'must_change_password' => 'Trocar senha no próximo acesso',
        'active' => 'Situação',
        'country' => 'País',
        'document' => 'CNPJ / identificador',
        'phone' => 'Telefone',
        'client_id' => 'Cliente',
        'revoked_at' => 'Revogada em',
    ];

    /** @return list<array{value: string, label: string}> */
    public function typeOptions(): array
    {
        return array_map(fn (string $type, string $label) => ['value' => $type, 'label' => $label], array_keys(self::TYPES), self::TYPES);
    }

    /**
     * @param  array<string, array<int, string>>  $names
     * @return array<string, mixed>
     */
    public function row(AuditLog $log, array $names): array
    {
        $assistant = $log->aiAuthorization?->aiAssistant;

        return [
            'id' => $log->id,
            'created_at' => LocalTime::format($log->created_at, 'd/m/Y H:i:s'),
            'user' => $log->user?->name ?? 'Sistema',
            'user_email' => $log->user?->email,
            'channel' => $assistant === null ? 'Tela' : "IA: {$assistant->label}",
            'action' => $log->action,
            'action_label' => AuditActionEnum::tryFrom($log->action)?->label() ?? $log->action,
            'type' => $log->auditable_type,
            'type_label' => self::TYPES[$log->auditable_type] ?? $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'record' => $this->record($log->auditable_type, $log->auditable_id, $names),
            'outcome' => $log->outcome,
            'changes' => $this->changes($log->changes ?? [], $names['client'] ?? []),
            'ip_address' => $log->ip_address,
        ];
    }

    /**
     * "Usuário Maria"; sem nome (excluído, registro de módulo): "Usuário #12"; negação antes de
     * achar o registro: só o tipo.
     *
     * @param  array<string, array<int, string>>  $names
     */
    public function record(string $type, ?int $id, array $names): string
    {
        $label = self::TYPES[$type] ?? $type;

        if ($id === null) {
            return $label;
        }

        $name = $names[$type][$id] ?? null;

        return $name === null ? "{$label} #{$id}" : "{$label} {$name}";
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes
     * @param  array<int, string>  $clients
     * @return list<array{field: string, label: string, before: string|null, after: string|null}>
     */
    private function changes(array $changes, array $clients): array
    {
        // O MySQL devolve as chaves do JSON na ordem dele: o detalhe segue a ordem do formulário.
        $order = array_flip(array_keys(self::FIELDS));
        uksort($changes, fn (string $a, string $b) => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX) ?: strcmp($a, $b));

        $rows = [];
        foreach ($changes as $field => [$before, $after]) {
            $rows[] = [
                'field' => $field,
                'label' => self::FIELDS[$field] ?? $field,
                'before' => $this->readable($field, $before, $clients),
                'after' => $this->readable($field, $after, $clients),
            ];
        }

        return $rows;
    }

    /** @param  array<int, string>  $clients */
    private function readable(string $field, mixed $value, array $clients): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (true) {
            $field === 'role_id' => RoleEnum::tryFrom((int) $value)?->label() ?? (string) $value,
            $field === 'active' => $value ? 'Ativo' : 'Desativado',
            $field === 'country' => Paises::nome((string) $value),
            $field === 'client_id' => $clients[(int) $value] ?? "Cliente #{$value}",
            $field === 'daily_hours' => number_format((float) $value, 2, ',', '.').' h',
            $field === 'revoked_at' => LocalTime::format(CarbonImmutable::parse((string) $value, 'UTC'), 'd/m/Y H:i:s'),
            is_bool($value) => $value ? 'Sim' : 'Não',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
        };
    }
}
