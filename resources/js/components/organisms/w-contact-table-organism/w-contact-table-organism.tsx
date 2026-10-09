import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { deactivation, situation } from '@/lib/situation';
import { cn } from '@/lib/utils';

export interface ContactRow {
    id: number;
    name: string;
    email: string | null;
    /** Texto, como a pessoa digitou (F1 §02, D3): o "+" e os espaços ficam. */
    phone: string | null;
    client_id: number;
    client_name: string | null;
    active: boolean;
    /** D5: estado final, vindo da auditoria do contato. */
    anonymized: boolean;
    /** "DD/MM/AAAA HH:MM" no fuso local; nulo quando ativo. */
    deactivated_at: string | null;
    deactivated_by: string | null;
    /** Decidido pela ContactPolicy no servidor; a tela só esconde o botão. */
    can: { update: boolean; deactivate: boolean; reactivate: boolean; delete: boolean; anonymize: boolean };
}

export const contactStatus = (row: ContactRow) => (row.anonymized ? 'anonymized' : row.active);

// Desativado e anonimizado ficam em cinza (F1 §03).
const muted = (row: ContactRow) => cn(!row.active && 'text-muted-foreground');

const Situation = ({ row }: { row: ContactRow }) => (
    <>
        <WStatusPillAtom kind="contact" status={contactStatus(row)} />
        {!row.active && !row.anonymized && <p className="mt-1 text-xs text-muted-foreground">{deactivation(row)}</p>}
    </>
);

const ClientLink = ({ row }: { row: ContactRow }) => (
    <Link href={`/admin/clients/${row.client_id}`} className={cn('underline-offset-4 hover:underline', muted(row))}>
        {row.client_name}
    </Link>
);

export interface WContactTableOrganismProps {
    rows: ContactRow[];
    /** As ações da linha (desktop) ou do card (`labeled`). */
    actions: (row: ContactRow, labeled?: boolean) => ReactNode;
    emptyState: ReactNode;
    caption: string;
    /** Com a coluna Cliente (lista geral); sem ela na ficha do cliente. */
    showClient?: boolean;
}

/**
 * Contatos com as colunas do F1 §03, na lista geral (com a coluna Cliente) e na ficha do
 * cliente (sem ela). Tabela no desktop, cards no celular.
 */
export function WContactTableOrganism({ rows, actions, emptyState, caption, showClient = false }: WContactTableOrganismProps) {
    const columns: WColumn<ContactRow>[] = [
        { key: 'name', header: 'Nome', cell: (row) => <span className={cn('font-medium', muted(row))}>{row.name}</span> },
        { key: 'email', header: 'E-mail', className: 'text-muted-foreground', cell: (row) => row.email ?? '—' },
        {
            key: 'phone',
            header: 'Telefone',
            className: 'whitespace-nowrap text-muted-foreground tabular-nums',
            cell: (row) => row.phone || '—',
        },
        ...(showClient ? [{ key: 'client', header: 'Cliente', cell: (row: ContactRow) => <ClientLink row={row} /> }] : []),
        { key: 'active', header: 'Situação', cell: (row) => <Situation row={row} /> },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => actions(row) },
    ];

    return (
        <WDataTableOrganism
            caption={caption}
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            emptyState={emptyState}
            mobileCard={(row) => (
                <>
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className={cn('truncate font-semibold', muted(row))}>{row.name}</p>
                            <p className="truncate text-xs text-muted-foreground">
                                {[row.email, row.phone].filter(Boolean).join(' · ') || 'Sem e-mail nem telefone'}
                            </p>
                            {showClient && (
                                <p className="truncate text-xs">
                                    <ClientLink row={row} />
                                </p>
                            )}
                        </div>
                        <WStatusPillAtom kind="contact" status={contactStatus(row)} size="sm" />
                    </div>
                    {!row.active && !row.anonymized && <p className="mt-2 text-xs text-muted-foreground">{situation(row)}</p>}
                    {actions(row, true)}
                </>
            )}
        />
    );
}
