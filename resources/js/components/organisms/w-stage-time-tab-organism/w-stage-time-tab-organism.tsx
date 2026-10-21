import { Timer } from 'lucide-react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { businessDays } from '@/lib/indicadores';
import type { StageTimeTab } from '@/types/indicadores';

export interface WStageTimeTabOrganismProps {
    data: StageTimeTab;
}

type Row = StageTimeTab['rows'][number];

/**
 * Aba *Tempo por etapa* (F7 §03, §06; P11, P53): uma linha por papel de etapa, na ordem do quadro,
 * com as etapas que têm esse papel, quantas tarefas passaram, o tempo somado e a média por tarefa,
 * em dias úteis com uma casa decimal. Renomear a etapa não muda a conta; o papel pronto fica fora.
 * Vazia: "Nenhuma tarefa saiu do Backlog com esses filtros".
 */
export function WStageTimeTabOrganism({ data }: WStageTimeTabOrganismProps) {
    const columns: WColumn<Row>[] = [
        { key: 'label', header: 'Papel da etapa', cell: (row) => <span className="font-medium">{row.label}</span> },
        {
            key: 'stages',
            header: 'Etapas do quadro',
            cell: (row) => (row.stages.length ? row.stages.join(', ') : <span className="text-muted-foreground">nenhuma ativa</span>),
        },
        { key: 'tasks', header: 'Tarefas que passaram', className: 'text-right tabular-nums', cell: (row) => row.tasks },
        { key: 'total', header: 'Tempo somado', className: 'text-right tabular-nums', cell: (row) => businessDays(row.total_days) },
        { key: 'average', header: 'Tempo médio por tarefa', className: 'text-right tabular-nums', cell: (row) => businessDays(row.average_days) },
    ];

    return (
        <section data-slot="w-stage-time-tab-organism" aria-label="Tempo por etapa" className="space-y-3">
            <p className="text-sm text-muted-foreground">
                Soma por papel da etapa, não pelo nome: duas etapas com o mesmo papel contam juntas. Só dias de segunda a sexta.
            </p>
            <WDataTableOrganism
                columns={columns}
                rows={data.rows}
                rowKey={(row) => row.role}
                caption="Tempo por papel de etapa"
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Timer className="size-5" />}
                        title="Nenhuma tarefa saiu do Backlog com esses filtros"
                        description="O tempo por etapa conta a partir do primeiro movimento do card."
                    />
                }
            />
        </section>
    );
}
