import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { WTagAtom } from '@/components/atoms/w-tag-atom/w-tag-atom';
import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { dateTimeBr } from '@/lib/ficha';
import type { ArchivedCard } from '@/types/quadro';

export interface WArchivedCardsTableOrganismProps {
    /** Do mais recente para o mais antigo, como o servidor manda (`archived` do quadro). */
    rows: ArchivedCard[];
    /** Substitui a tabela quando não há linha: "Nenhum card arquivado" ou "Nenhum card com esses filtros". */
    emptyState: ReactNode;
}

const TypeTag = ({ row }: { row: ArchivedCard }) =>
    row.type ? (
        <WTagAtom label={row.type.label} color={row.type.color} kind="type" size="sm" />
    ) : (
        <span className="text-muted-foreground">—</span>
    );

/**
 * A lista de arquivados do quadro (F3 §03; P26): Chave, Título, Tipo, Arquivado em, Motivo. A chave e
 * o título abrem a ficha, que continua abrindo e é onde se desarquiva (F3 §04).
 */
export function WArchivedCardsTableOrganism({ rows, emptyState }: WArchivedCardsTableOrganismProps) {
    const columns: WColumn<ArchivedCard>[] = [
        { key: 'key', header: 'Chave', className: 'w-28', cell: (row) => <WTaskKeyAtom taskKey={row.key} href={row.url} size="sm" /> },
        {
            key: 'title',
            header: 'Título',
            cell: (row) => (
                <Link href={row.url} className="font-medium underline-offset-4 hover:underline">
                    {row.title}
                </Link>
            ),
        },
        { key: 'type', header: 'Tipo', className: 'w-40', cell: (row) => <TypeTag row={row} /> },
        {
            key: 'archived_at',
            header: 'Arquivado em',
            className: 'w-40 font-mono text-xs tabular-nums whitespace-nowrap',
            cell: (row) => dateTimeBr(row.archived_at),
        },
        { key: 'archive_reason', header: 'Motivo', cell: (row) => <span className="text-muted-foreground">{row.archive_reason}</span> },
    ];

    return (
        <WDataTableOrganism
            caption="Cards arquivados"
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            emptyState={emptyState}
            mobileCard={(row) => (
                <>
                    <div className="flex items-center justify-between gap-3">
                        <WTaskKeyAtom taskKey={row.key} href={row.url} size="sm" />
                        <span className="font-mono text-xs text-muted-foreground tabular-nums">{dateTimeBr(row.archived_at)}</span>
                    </div>
                    <Link href={row.url} className="mt-1 block font-medium underline-offset-4 hover:underline">
                        {row.title}
                    </Link>
                    <div className="mt-2">
                        <TypeTag row={row} />
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">{row.archive_reason}</p>
                </>
            )}
        />
    );
}
