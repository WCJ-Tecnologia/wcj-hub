import type { ReactNode } from 'react';

import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export interface Column<Row> {
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

/**
 * Tabela declarativa: <table> com caption e scope no desktop, lista de cards no
 * celular (md:hidden). `emptyState` substitui a tabela quando não há linhas.
 */
export function DataTable<Row>({
    columns,
    rows,
    rowKey,
    mobileCard,
    caption,
    emptyState,
    className,
    rowClassName,
}: {
    columns: Column<Row>[];
    rows: Row[];
    rowKey: (row: Row) => string | number;
    /** Card por linha no celular. Sem ele, cada coluna vira um par rótulo/valor. */
    mobileCard?: (row: Row) => ReactNode;
    /** Lido por leitor de tela; visualmente oculto. */
    caption: string;
    emptyState?: ReactNode;
    className?: string;
    /** Destaque de uma linha (ex.: tentativa negada na auditoria), na tabela e no card. */
    rowClassName?: (row: Row) => string | undefined;
}) {
    if (rows.length === 0 && emptyState) return <>{emptyState}</>;

    const render = (column: Column<Row>, row: Row): ReactNode =>
        column.cell ? column.cell(row) : ((row as Record<string, unknown>)[column.key] as ReactNode);

    return (
        <Card className={cn('gap-0 overflow-hidden p-0', className)}>
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} scope="col" className={cn('px-4 py-3 font-semibold', column.className)}>
                                    {column.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={rowKey(row)} className={cn('border-b border-border last:border-0', rowClassName?.(row))}>
                                {columns.map((column) => (
                                    <td key={column.key} className={cn('px-4 py-3', column.className)}>
                                        {render(column, row)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
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
