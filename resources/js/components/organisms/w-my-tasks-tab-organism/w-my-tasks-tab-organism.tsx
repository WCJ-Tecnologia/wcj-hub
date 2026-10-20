import { ClipboardList } from 'lucide-react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { businessDays, DEVELOPMENT_TIME_NOTE, missingText, MY_ROLE_LABEL } from '@/lib/indicadores';
import { hours } from '@/lib/quadro';
import { cn, dateBr } from '@/lib/utils';
import type { MyTaskRow, MyTasksTab } from '@/types/indicadores';

export interface WMyTasksTabOrganismProps {
    data: MyTasksTab;
}

const dash = <span className="text-muted-foreground">—</span>;

/** "1,5 dia útil" em vermelho, "No prazo", ou "—" sem data. */
function delayCell(row: MyTaskRow) {
    if (row.delay_days === null) return dash;
    if (row.delay_days <= 0) return <span className="text-muted-foreground">No prazo</span>;
    return <span className="font-medium text-destructive">{businessDays(row.delay_days)}</span>;
}

/**
 * Aba *Minhas tarefas e horas* (F7 §03, §05, §06; P53, P54): as tarefas abertas em que a pessoa é
 * dev ou QA, atrasadas primeiro e depois pela data prevista, com o atraso, as horas previstas, as
 * que ela lançou e o tempo em desenvolvimento onde é dev. A única aba do dev nos indicadores.
 */
export function WMyTasksTabOrganism({ data }: WMyTasksTabOrganismProps) {
    const columns: WColumn<MyTaskRow>[] = [
        { key: 'key', header: 'Chave', cell: (row) => <WTaskKeyAtom taskKey={row.task.key} href={row.task.url} title={row.task.title} /> },
        { key: 'title', header: 'Título', cell: (row) => <span className="font-medium">{row.task.title}</span> },
        { key: 'role', header: 'Meu papel', cell: (row) => MY_ROLE_LABEL[row.role] },
        { key: 'stage', header: 'Etapa', cell: (row) => row.stage.name },
        {
            key: 'due',
            header: 'Data prevista',
            cell: (row) => (row.due_on ? dateBr(row.due_on) : <span className="text-muted-foreground">{missingText(row.missing)}</span>),
        },
        { key: 'delay', header: 'Atraso', className: 'text-right tabular-nums', cell: delayCell },
        { key: 'estimated', header: 'Previstas', className: 'text-right tabular-nums', cell: (row) => (row.estimated === null ? dash : hours(row.estimated)) },
        { key: 'logged', header: 'Lançadas por mim', className: 'text-right tabular-nums', cell: (row) => hours(row.logged_by_me) },
        {
            key: 'development',
            header: 'Tempo em desenvolvimento',
            className: cn('text-right tabular-nums'),
            cell: (row) => (row.development_hours === null ? dash : hours(row.development_hours)),
        },
    ];

    return (
        <section data-slot="w-my-tasks-tab-organism" aria-label="Minhas tarefas e horas" className="space-y-3">
            <p className="text-sm text-muted-foreground">Tempo em desenvolvimento: {DEVELOPMENT_TIME_NOTE}; só onde sou dev.</p>
            <WDataTableOrganism
                columns={columns}
                rows={data.rows}
                rowKey={(row) => row.task.id}
                caption="Minhas tarefas abertas"
                emptyState={
                    <WEmptyStateMolecule
                        icon={<ClipboardList className="size-5" />}
                        title="Nenhuma tarefa sua neste projeto"
                        description="Entram as tarefas abertas em que você é dev ou QA."
                    />
                }
            />
        </section>
    );
}
