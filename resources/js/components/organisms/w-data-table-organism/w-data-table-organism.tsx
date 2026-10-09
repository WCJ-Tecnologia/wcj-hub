import type { ReactNode } from 'react';

import { Card } from '@/components/shadcn/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/shadcn/table';
import { cn } from '@/lib/utils';

export interface WColumn<Row> {
    key: string;
    /** Cabeçalho visível. Passe um `<span className="sr-only">` para coluna de ações. */
    header: ReactNode;
    /** Sem `cell`, mostra `row[key]`. */
    cell?: (row: Row) => ReactNode;
    /** Classes do <th> e do <td>. */
    className?: string;
    /** Fora do card padrão no celular (ignorado quando há `mobileCard`). */
    hideOnMobile?: boolean;
}

export interface WDataTableOrganismProps<Row> {
    columns: WColumn<Row>[];
    rows: Row[];
    rowKey: (row: Row) => string | number;
    /** Card por linha no celular. Sem ele, cada coluna vira um par rótulo/valor. */
    mobileCard?: (row: Row) => ReactNode;
    /** Lido por leitor de tela; visualmente oculto. */
    caption: string;
    /** Substitui a tabela quando não há linhas. */
    emptyState?: ReactNode;
    className?: string;
    /** Destaque de uma linha (ex.: tentativa negada na auditoria), na tabela e no card. */
    rowClassName?: (row: Row) => string | undefined;
}

/**
 * Tabela declarativa sobre o Table do shadcn: caption e scope no desktop,
 * lista de cards no celular (md:hidden). `emptyState` substitui a tabela
 * quando não há linhas.
 */
export function WDataTableOrganism<Row>({
    columns,
    rows,
    rowKey,
    mobileCard,
    caption,
    emptyState,
    className,
    rowClassName,
}: WDataTableOrganismProps<Row>) {
    if (rows.length === 0 && emptyState) return <>{emptyState}</>;

    const render = (column: WColumn<Row>, row: Row): ReactNode =>
        column.cell ? column.cell(row) : ((row as Record<string, unknown>)[column.key] as ReactNode);

    return (
        <Card data-slot="w-data-table-organism" className={cn('gap-0 overflow-hidden p-0', className)}>
            <div className="hidden md:block">
                <Table>
                    <TableCaption className="sr-only">{caption}</TableCaption>
                    <TableHeader className="bg-muted/40">
                        <TableRow className="hover:bg-transparent">
                            {columns.map((column) => (
                                <TableHead
                                    key={column.key}
                                    scope="col"
                                    className={cn(
                                        'h-auto px-4 py-3 font-mono text-xs tracking-wide whitespace-normal text-muted-foreground uppercase',
                                        column.className,
                                    )}
                                >
                                    {column.header}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={rowKey(row)} className={rowClassName?.(row)}>
                                {columns.map((column) => (
                                    <TableCell key={column.key} className={cn('px-4 py-3 whitespace-normal', column.className)}>
                                        {render(column, row)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <ul className="divide-y divide-border md:hidden" aria-label={caption}>
                {rows.map((row) => (
                    <li key={rowKey(row)} className={cn('p-4', rowClassName?.(row))}>
                        {mobileCard ? (
                            mobileCard(row)
                        ) : (
                            <dl className="space-y-1.5 text-sm">
                                {columns
                                    .filter((column) => !column.hideOnMobile)
                                    .map((column) => (
                                        <div key={column.key} className="flex items-start justify-between gap-3">
                                            <dt className="shrink-0 text-xs text-muted-foreground">{column.header}</dt>
                                            <dd className="min-w-0 text-right">{render(column, row)}</dd>
                                        </div>
                                    ))}
                            </dl>
                        )}
                    </li>
                ))}
            </ul>
        </Card>
    );
}
