import type { ReactNode } from 'react';

import { DataTable, type Column } from '@/components/data-table';
import { StatusPill } from '@/components/status-pill';
import { Badge } from '@/components/ui/badge';
import { deactivation, situation } from '@/lib/situation';
import { cn } from '@/lib/utils';

export interface UserRow {
    id: number;
    name: string;
    email: string;
    active: boolean;
    /** "DD/MM/AAAA HH:MM" no fuso local; nulo quando ativo. */
    deactivated_at: string | null;
    deactivated_by: string | null;
    role_id: number;
    role_label: string;
    /** decimal(6,2) como texto ("8.00"); null = sem carga (N3). */
    daily_hours: string | null;
    ai_write_allowed: boolean;
    must_change_password: boolean;
    /** Último login (auditoria), no fuso local; nulo = nunca entrou. */
    last_login_at: string | null;
    /** Decidido pela UserPolicy no servidor; a tela só esconde o botão. */
    can: { update: boolean; deactivate: boolean; reactivate: boolean; delete: boolean; resend_invite: boolean };
}

/** "8.00" → "8 h"; "7.50" → "7,5 h". */
const formatHours = (hours: string | null) => (hours === null ? '—' : `${Number(hours).toLocaleString('pt-BR')} h`);

// Desativado fica em cinza (F1 §03).
const muted = (row: UserRow) => cn(!row.active && 'text-muted-foreground');

/** Lista de usuários com as colunas do F1 §03; tabela no desktop, cards no celular. */
export function UserTable({
    rows,
    actions,
    emptyState,
}: {
    rows: UserRow[];
    actions: (row: UserRow, labeled?: boolean) => ReactNode;
    emptyState: ReactNode;
}) {
    const columns: Column<UserRow>[] = [
        {
            key: 'name',
            header: 'Nome',
            cell: (row) => (
                <>
                    <p className={cn('font-medium', muted(row))}>{row.name}</p>
                    {row.must_change_password && row.active && (
                        <p className="mt-1">
                            <Badge variant="warning" className="whitespace-normal">
                                Trocar senha no próximo acesso
                            </Badge>
                        </p>
                    )}
                </>
            ),
        },
        { key: 'email', header: 'E-mail', className: 'text-muted-foreground', cell: (row) => row.email },
        { key: 'role_label', header: 'Papel', cell: (row) => <Badge variant={row.active ? 'info' : 'outline'}>{row.role_label}</Badge> },
        {
            key: 'daily_hours',
            header: 'Carga diária',
            className: 'tabular-nums',
            cell: (row) => <span className={muted(row)}>{formatHours(row.daily_hours)}</span>,
        },
        {
            key: 'last_login_at',
            header: 'Último acesso',
            className: 'text-muted-foreground tabular-nums',
            cell: (row) => row.last_login_at ?? 'Nunca entrou',
        },
        {
            key: 'active',
            header: 'Situação',
            cell: (row) => (
                <>
                    <StatusPill kind="user" status={row.active} />
                    {!row.active && <p className="mt-1 text-xs text-muted-foreground">{deactivation(row)}</p>}
                </>
            ),
        },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => actions(row) },
    ];

    return (
        <DataTable
            caption="Lista de usuários"
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            emptyState={emptyState}
            mobileCard={(row) => (
                <>
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className={cn('truncate font-semibold', muted(row))}>{row.name}</p>
                            <p className="truncate text-xs text-muted-foreground">{row.email}</p>
                        </div>
                        <StatusPill kind="user" status={row.active} size="sm" />
                    </div>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Badge variant={row.active ? 'info' : 'outline'}>{row.role_label}</Badge>
                        <Badge variant="outline">{formatHours(row.daily_hours)}</Badge>
                        {row.must_change_password && row.active && <Badge variant="warning">Trocar senha</Badge>}
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                        Último acesso: {row.last_login_at ?? 'nunca entrou'}
                        {!row.active && ` · ${situation(row)}`}
                    </p>
                    {actions(row, true)}
                </>
            )}
        />
    );
}
