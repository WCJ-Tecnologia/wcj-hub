import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { BRASIL } from '@/components/clients/client-form-dialog';
import { DataTable, type Column } from '@/components/data-table';
import { StatusPill } from '@/components/status-pill';
import { maskAlphanumericCnpj } from '@/lib/masks';
import { cn } from '@/lib/utils';

export interface ClientRow {
    id: number;
    name: string;
    country: string;
    country_name: string;
    document: string | null;
    active: boolean;
    /** "DD/MM/AAAA HH:MM" no fuso local; nulo quando ativo. */
    deactivated_at: string | null;
    deactivated_by: string | null;
    /** Projetos não encerrados, para a confirmação de desativar; nulo quando a pessoa não desativa. */
    open_projects: number | null;
    /** Decidido pela ClientPolicy no servidor; a tela só esconde o botão. */
    can: { update: boolean; deactivate: boolean; reactivate: boolean; delete: boolean };
}

/** CNPJ com máscara; identificador fiscal como foi digitado. */
export const formatDocument = (row: Pick<ClientRow, 'country' | 'document'>) =>
    row.document === null ? '—' : row.country === BRASIL ? maskAlphanumericCnpj(row.document) : row.document;

/** "em DD/MM/AAAA por Fulano" (F1 §02, Situação). */
export const deactivation = (row: Pick<ClientRow, 'deactivated_at' | 'deactivated_by'>) =>
    `em ${row.deactivated_at?.slice(0, 10) ?? '—'}${row.deactivated_by ? ` por ${row.deactivated_by}` : ''}`;

export const situation = (row: ClientRow) => (row.active ? 'Ativo' : `Desativado ${deactivation(row)}`);

// Desativado fica em cinza (F1 §03).
const muted = (row: ClientRow) => cn(!row.active && 'text-muted-foreground');

const NameLink = ({ row, className }: { row: ClientRow; className?: string }) => (
    <Link href={`/admin/clients/${row.id}`} className={cn('font-medium underline-offset-4 hover:underline', muted(row), className)}>
        {row.name}
    </Link>
);

/** Lista de clientes com as colunas do F1 §03; tabela no desktop, cards no celular. */
export function ClientTable({
    rows,
    actions,
    emptyState,
}: {
    rows: ClientRow[];
    actions: (row: ClientRow, labeled?: boolean) => ReactNode;
    emptyState: ReactNode;
}) {
    const columns: Column<ClientRow>[] = [
        { key: 'name', header: 'Nome', cell: (row) => <NameLink row={row} /> },
        { key: 'country', header: 'País', cell: (row) => <span className={muted(row)}>{row.country_name}</span> },
        {
            key: 'document',
            header: 'CNPJ / identificador',
            className: 'font-mono text-xs tabular-nums',
            cell: (row) => <span className={muted(row)}>{formatDocument(row)}</span>,
        },
        {
            key: 'active',
            header: 'Situação',
            cell: (row) => (
                <>
                    <StatusPill kind="client" status={row.active} />
                    {!row.active && <p className="mt-1 text-xs text-muted-foreground">{deactivation(row)}</p>}
                </>
            ),
        },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => actions(row) },
    ];

    return (
        <DataTable
            caption="Lista de clientes"
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            emptyState={emptyState}
            mobileCard={(row) => (
                <>
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <NameLink row={row} className="block truncate font-semibold" />
                            <p className="truncate text-xs text-muted-foreground">
                                {row.country_name} · <span className="font-mono tabular-nums">{formatDocument(row)}</span>
                            </p>
                        </div>
                        <StatusPill kind="client" status={row.active} size="sm" />
                    </div>
                    {!row.active && <p className="mt-2 text-xs text-muted-foreground">{situation(row)}</p>}
                    {actions(row, true)}
                </>
            )}
        />
    );
}
