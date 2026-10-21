import { AlertTriangle, CalendarClock, CalendarX2, PauseCircle } from 'lucide-react';
import type { ReactNode } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { businessDays, missingText } from '@/lib/indicadores';
import { dateBr } from '@/lib/utils';
import type { SituationTab } from '@/types/indicadores';
import type { TaskRef } from '@/types/quadro';

export interface WSituationTabOrganismProps {
    data: SituationTab;
}

const keyCell = (task: TaskRef) => <WTaskKeyAtom taskKey={task.key} href={task.url} title={task.title} />;
const dev = (name: string | null) => name ?? <span className="text-muted-foreground">Sem dev</span>;

function Block({
    title,
    icon,
    count,
    empty,
    children,
}: {
    title: string;
    icon: ReactNode;
    count: number;
    empty: string;
    children: ReactNode;
}) {
    return (
        <section aria-label={title} className="space-y-2 rounded-xl border border-border bg-card p-4">
            <h3 className="flex items-center gap-2 font-heading text-base font-semibold">
                <span className="text-muted-foreground">{icon}</span>
                {title}
                <span className="ml-auto font-mono text-xs text-muted-foreground tabular-nums">{count}</span>
            </h3>
            {count === 0 ? <p className="text-sm text-muted-foreground">{empty}</p> : children}
        </section>
    );
}

/**
 * Aba *Situação* (F7 §03, §06; P15, P28, P53): o projeto inteiro, fora do arquivo, sem filtro, o
 * mesmo que `status_do_projeto` devolve pela IA (mais as pausas). Tarefas por etapa na ordem do
 * quadro; atrasadas (maior atraso primeiro, com a pausa descontada); próximas datas previstas;
 * em pausa, com o motivo; e as que entraram em desenvolvimento sem data prevista.
 */
export function WSituationTabOrganism({ data }: WSituationTabOrganismProps) {
    const late: WColumn<SituationTab['late'][number]>[] = [
        { key: 'key', header: 'Chave', cell: (row) => keyCell(row.task) },
        { key: 'title', header: 'Título', cell: (row) => <span className="font-medium">{row.task.title}</span> },
        { key: 'dev', header: 'Dev', cell: (row) => dev(row.developer) },
        { key: 'due', header: 'Data prevista', cell: (row) => dateBr(row.due_on) },
        { key: 'paused', header: 'Tempo em pausa', className: 'text-right tabular-nums', cell: (row) => businessDays(row.paused_days) },
        {
            key: 'delay',
            header: 'Atraso',
            className: 'text-right tabular-nums',
            cell: (row) => <span className="font-medium text-destructive">{businessDays(row.delay_days)}</span>,
        },
    ];
    const upcoming: WColumn<SituationTab['upcoming'][number]>[] = [
        { key: 'key', header: 'Chave', cell: (row) => keyCell(row.task) },
        { key: 'title', header: 'Título', cell: (row) => <span className="font-medium">{row.task.title}</span> },
        { key: 'dev', header: 'Dev', cell: (row) => dev(row.developer) },
        { key: 'due', header: 'Data prevista', cell: (row) => dateBr(row.due_on) },
    ];
    const paused: WColumn<SituationTab['paused'][number]>[] = [
        { key: 'key', header: 'Chave', cell: (row) => keyCell(row.task) },
        { key: 'title', header: 'Título', cell: (row) => <span className="font-medium">{row.task.title}</span> },
        { key: 'dev', header: 'Dev', cell: (row) => dev(row.developer) },
        { key: 'reason', header: 'Motivo', cell: (row) => row.reason ?? <span className="text-muted-foreground">Sem motivo</span> },
        { key: 'since', header: 'Pausada desde', cell: (row) => row.since ?? '—' },
        { key: 'by', header: 'Quem pausou', cell: (row) => row.by ?? '—' },
    ];
    const withoutDue: WColumn<SituationTab['without_due'][number]>[] = [
        { key: 'key', header: 'Chave', cell: (row) => keyCell(row.task) },
        { key: 'title', header: 'Título', cell: (row) => <span className="font-medium">{row.task.title}</span> },
        { key: 'missing', header: 'O que falta', cell: (row) => missingText(row.missing) },
    ];

    return (
        <div data-slot="w-situation-tab-organism" className="grid gap-4">
            <section aria-label="Tarefas por etapa" className="rounded-xl border border-border bg-card p-4">
                <h3 className="mb-3 font-heading text-base font-semibold">Tarefas por etapa</h3>
                <ol className="grid grid-cols-[repeat(auto-fill,minmax(7rem,1fr))] gap-2">
                    {data.by_stage.map((stage) => (
                        <li key={stage.id} className="rounded-md border border-border px-3 py-2">
                            <span className="block truncate font-mono text-xs text-muted-foreground uppercase" title={stage.name}>
                                {stage.name}
                            </span>
                            <span className="font-heading text-2xl font-semibold tabular-nums">{stage.count}</span>
                        </li>
                    ))}
                </ol>
            </section>

            <Block title="Atrasadas" icon={<AlertTriangle className="size-4" />} count={data.late.length} empty="Nenhuma tarefa atrasada">
                <WDataTableOrganism columns={late} rows={data.late} rowKey={(row) => row.task.id} caption="Tarefas atrasadas" />
            </Block>

            <Block
                title="Próximas datas previstas"
                icon={<CalendarClock className="size-4" />}
                count={data.upcoming.length}
                empty="Nenhuma data prevista pela frente"
            >
                <WDataTableOrganism
                    columns={upcoming}
                    rows={data.upcoming}
                    rowKey={(row) => row.task.id}
                    caption="Próximas datas previstas"
                />
            </Block>

            <Block title="Em pausa" icon={<PauseCircle className="size-4" />} count={data.paused.length} empty="Nenhuma tarefa em pausa">
                <WDataTableOrganism columns={paused} rows={data.paused} rowKey={(row) => row.task.id} caption="Tarefas em pausa" />
            </Block>

            <Block
                title="Sem data prevista"
                icon={<CalendarX2 className="size-4" />}
                count={data.without_due.length}
                empty="Toda tarefa em andamento tem data prevista"
            >
                <WDataTableOrganism
                    columns={withoutDue}
                    rows={data.without_due}
                    rowKey={(row) => row.task.id}
                    caption="Tarefas em desenvolvimento sem data prevista"
                />
            </Block>
        </div>
    );
}
