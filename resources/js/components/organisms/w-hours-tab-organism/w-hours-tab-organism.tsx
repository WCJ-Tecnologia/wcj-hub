import { Clock } from 'lucide-react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Badge } from '@/components/shadcn/badge';
import { DEVELOPMENT_TIME_NOTE } from '@/lib/indicadores';
import { hours } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { HoursRow, HoursTab } from '@/types/indicadores';

export interface WHoursTabOrganismProps {
    data: HoursTab;
}

const dash = <span className="text-muted-foreground">—</span>;

/** Lançadas − previstas: positiva (passou do previsto) em destaque. */
export function differenceCell(difference: number | null) {
    if (difference === null) return dash;
    const sign = difference > 0 ? '+' : '';
    return <span className={cn('tabular-nums', difference > 0 && 'font-medium text-destructive')}>{`${sign}${hours(difference)}`}</span>;
}

/**
 * Aba *Horas* (F7 §03, §06; P27, P30, R17): por tarefa, as horas previstas, as lançadas, a
 * diferença (positiva = passou do previsto) e, ao lado, o tempo em desenvolvimento estimado pelo
 * quadro (dias úteis em etapa de desenvolvimento × carga do dev), que nunca soma com as lançadas.
 * A arquivada aparece com a marca; as previstas dela ficam fora do total. Linha de total no fim.
 */
export function WHoursTabOrganism({ data }: WHoursTabOrganismProps) {
    const columns: WColumn<HoursRow>[] = [
        { key: 'key', header: 'Chave', cell: (row) => <WTaskKeyAtom taskKey={row.task.key} href={row.task.url} title={row.task.title} /> },
        {
            key: 'title',
            header: 'Título',
            cell: (row) => (
                <span className="inline-flex flex-wrap items-center gap-2">
                    <span className="font-medium">{row.task.title}</span>
                    {row.archived && <Badge variant="outline">arquivada</Badge>}
                </span>
            ),
        },
        { key: 'developer', header: 'Dev', cell: (row) => row.developer ?? <span className="text-muted-foreground">Sem dev</span> },
        { key: 'stage', header: 'Etapa atual', cell: (row) => row.stage.name },
        { key: 'estimated', header: 'Previstas', className: 'text-right tabular-nums', cell: (row) => (row.estimated === null ? dash : hours(row.estimated)) },
        { key: 'logged', header: 'Lançadas', className: 'text-right tabular-nums', cell: (row) => hours(row.logged) },
        { key: 'difference', header: 'Diferença', className: 'text-right', cell: (row) => differenceCell(row.difference) },
        {
            key: 'development',
            header: 'Tempo em desenvolvimento',
            className: 'text-right tabular-nums',
            cell: (row) => (row.development_hours === null ? dash : hours(row.development_hours)),
        },
    ];

    return (
        <section data-slot="w-hours-tab-organism" aria-label="Horas" className="space-y-3">
            <p className="text-sm text-muted-foreground">
                Diferença = lançadas − previstas. Tempo em desenvolvimento: {DEVELOPMENT_TIME_NOTE}.
            </p>
            <WDataTableOrganism
                columns={columns}
                rows={data.rows}
                rowKey={(row) => row.task.id}
                caption="Horas previstas e lançadas por tarefa"
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Clock className="size-5" />}
                        title="Nenhuma tarefa com horas previstas ou lançadas com esses filtros"
                        description="As horas previstas são definidas pelo gestor; as lançadas, em Minhas horas."
                    />
                }
            />
            {data.rows.length > 0 && (
                <dl className="grid gap-2 text-sm sm:grid-cols-4" aria-label="Totais">
                    <Total label="Previstas" value={hours(data.totals.estimated)} note="sem as arquivadas" />
                    <Total label="Lançadas" value={hours(data.totals.logged)} />
                    <Total label="Diferença" value={differenceCell(data.totals.difference)} />
                    <Total label="Tempo em desenvolvimento" value={hours(data.totals.development_hours)} note="estimado" />
                </dl>
            )}
        </section>
    );
}

function Total({ label, value, note }: { label: string; value: React.ReactNode; note?: string }) {
    return (
        <div className="rounded-md border border-border px-3 py-2">
            <dt className="font-mono text-xs text-muted-foreground uppercase">{label}</dt>
            <dd className="font-medium tabular-nums">
                {value}
                {note && <span className="ml-1 text-xs font-normal text-muted-foreground">({note})</span>}
            </dd>
        </div>
    );
}
