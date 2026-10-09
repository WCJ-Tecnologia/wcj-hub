import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { BRASIL } from '@/components/organisms/w-client-form-dialog-organism/w-client-form-dialog-organism';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { maskAlphanumericCnpj } from '@/lib/masks';
import { deactivation, situation } from '@/lib/situation';
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

// Desativado fica em cinza (F1 §03).
const muted = (row: ClientRow) => cn(!row.active && 'text-muted-foreground');

const NameLink = ({ row, className }: { row: ClientRow; className?: string }) => (
    <Link href={`/admin/clients/${row.id}`} className={cn('font-medium underline-offset-4 hover:underline', muted(row), className)}>
        {row.name}
    </Link>
);

export interface WClientTableOrganismProps {
    rows: ClientRow[];
    /** As ações da linha (desktop) ou do card (`labeled`). */
    actions: (row: ClientRow, labeled?: boolean) => ReactNode;
    emptyState: ReactNode;
}

/** Lista de clientes com as colunas do F1 §03; tabela no desktop, cards no celular. */
export function WClientTableOrganism({ rows, actions, emptyState }: WClientTableOrganismProps) {
    const columns: WColumn<ClientRow>[] = [
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
                    <WStatusPillAtom kind="client" status={row.active} />
                    {!row.active && <p className="mt-1 text-xs text-muted-foreground">{deactivation(row)}</p>}
                </>
            ),
        },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => actions(row) },
    ];

    return (
        <WDataTableOrganism
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
                        <WStatusPillAtom kind="client" status={row.active} size="sm" />
                    </div>
                    {!row.active && <p className="mt-2 text-xs text-muted-foreground">{situation(row)}</p>}
                    {actions(row, true)}
                </>
            )}
        />
    );
}
