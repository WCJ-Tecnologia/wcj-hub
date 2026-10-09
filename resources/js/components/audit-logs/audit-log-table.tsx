import type { ReactNode } from 'react';

import { DataTable, type Column } from '@/components/data-table';
import { StatusPill } from '@/components/status-pill';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

/** Um campo alterado, já com o rótulo e os valores na linguagem da tela (AuditLogPresenter). */
export interface AuditChange {
    field: string;
    label: string;
    before: string | null;
    after: string | null;
}

export interface AuditLogRow {
    id: number;
    /** "DD/MM/AAAA HH:MM:SS" no fuso local. */
    created_at: string;
    user: string;
    user_email: string | null;
    /** "Tela" ou "IA: Claude". */
    channel: string;
    action: string;
    action_label: string;
    type: string;
    type_label: string;
    auditable_id: number | null;
    /** "Usuário Maria", "Cliente #12" (excluído). */
    record: string;
    outcome: 'success' | 'denied';
    changes: AuditChange[];
    ip_address: string | null;
}

// G3: a tentativa negada fica em destaque; o rótulo e o ícone do StatusPill dizem o porquê.
const highlight = (row: AuditLogRow) => (row.outcome === 'denied' ? 'bg-status-rejected-bg' : undefined);

/** Lista da auditoria com as colunas do F1 §03; tabela no desktop, cards no celular. */
export function AuditLogTable({
    rows,
    onOpen,
    emptyState,
}: {
    rows: AuditLogRow[];
    onOpen: (row: AuditLogRow) => void;
    emptyState: ReactNode;
}) {
    const columns: Column<AuditLogRow>[] = [
        { key: 'created_at', header: 'Data e hora', className: 'whitespace-nowrap text-muted-foreground tabular-nums' },
        { key: 'user', header: 'Quem', className: 'font-medium' },
        { key: 'channel', header: 'Canal', className: 'whitespace-nowrap' },
        { key: 'action_label', header: 'Ação', cell: (row) => <Badge variant="outline">{row.action_label}</Badge> },
        { key: 'record', header: 'Registro' },
        { key: 'outcome', header: 'Resultado', cell: (row) => <StatusPill kind="audit" status={row.outcome} size="sm" /> },
        {
            key: 'details',
            header: <span className="sr-only">Detalhes</span>,
            className: 'text-right',
            cell: (row) => (
                <Button
                    variant="link"
                    size="sm"
                    onClick={() => onOpen(row)}
                    aria-label={`Ver detalhes: ${row.action_label}, ${row.record}, ${row.created_at}`}
                >
                    Ver detalhes
                </Button>
            ),
        },
    ];

    return (
        <DataTable
            caption="Registros de auditoria"
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            rowClassName={highlight}
            emptyState={emptyState}
            mobileCard={(row) => (
                <button
                    type="button"
                    onClick={() => onOpen(row)}
                    className="block w-full rounded-md text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <div className="flex items-start justify-between gap-2">
                        <p className="min-w-0 truncate font-medium">{row.user}</p>
                        <StatusPill kind="audit" status={row.outcome} size="sm" />
                    </div>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Badge variant="outline">{row.action_label}</Badge>
                        <span className="text-sm">{row.record}</span>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground tabular-nums">
                        {row.created_at} · {row.channel}
                    </p>
                </button>
            )}
        />
    );
}
