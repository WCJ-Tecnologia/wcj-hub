import { Users } from 'lucide-react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { DEVELOPMENT_TIME_NOTE } from '@/lib/indicadores';
import { hours } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { PeopleTab, PersonRow } from '@/types/indicadores';

export interface WPeopleTabOrganismProps {
    data: PeopleTab;
}

const count = (value: number) => <span className={cn('tabular-nums', value > 0 && 'font-medium text-destructive')}>{value}</span>;

/**
 * Aba *Por pessoa* (F7 §03, §06, §10.4; P53): as contas de *Horas* somadas por dev, dentro do
 * projeto. Previstas = das tarefas em que a pessoa é dev; Lançadas = o que ela lançou em qualquer
 * tarefa do conjunto, inclusive como QA. *Sem dev* junta as tarefas sem dev, por último.
 */
export function WPeopleTabOrganism({ data }: WPeopleTabOrganismProps) {
    const columns: WColumn<PersonRow>[] = [
        {
            key: 'person',
            header: 'Pessoa',
            cell: (row) => (row.person ? <span className="font-medium">{row.person.name}</span> : <span className="text-muted-foreground">Sem dev</span>),
        },
        {
            key: 'daily',
            header: 'Carga diária',
            className: 'text-right tabular-nums',
            cell: (row) => (row.daily_hours === null ? <span className="text-muted-foreground">—</span> : hours(row.daily_hours)),
        },
        { key: 'tasks', header: 'Tarefas como dev', className: 'text-right tabular-nums', cell: (row) => row.tasks },
        { key: 'estimated', header: 'Previstas', className: 'text-right tabular-nums', cell: (row) => hours(row.estimated) },
        { key: 'logged', header: 'Lançadas por ela', className: 'text-right tabular-nums', cell: (row) => hours(row.logged) },
        { key: 'development', header: 'Tempo em desenvolvimento', className: 'text-right tabular-nums', cell: (row) => hours(row.development_hours) },
        { key: 'late', header: 'Atrasadas', className: 'text-right', cell: (row) => count(row.late) },
        { key: 'paused', header: 'Em pausa', className: 'text-right', cell: (row) => count(row.paused) },
    ];

    return (
        <section data-slot="w-people-tab-organism" aria-label="Por pessoa" className="space-y-3">
            <p className="text-sm text-muted-foreground">
                As previstas ficam com o dev da tarefa; as lançadas são as da pessoa em qualquer tarefa, inclusive como QA. Tempo em
                desenvolvimento: {DEVELOPMENT_TIME_NOTE}.
            </p>
            <WDataTableOrganism
                columns={columns}
                rows={data.rows}
                rowKey={(row) => row.person?.id ?? 'sem-dev'}
                caption="Indicadores por pessoa"
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Users className="size-5" />}
                        title="Nenhuma tarefa com dev com esses filtros"
                        description="Cada tarefa conta para o dev dela."
                    />
                }
            />
        </section>
    );
}
