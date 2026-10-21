import { Bug } from 'lucide-react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Badge } from '@/components/shadcn/badge';
import { dateBr } from '@/lib/utils';
import type { QualityTab } from '@/types/indicadores';

export interface WQualityTabOrganismProps {
    data: QualityTab;
}

type Row = QualityTab['rows'][number];

/**
 * Aba *Qualidade* (F7 §03, §06; P25): os bugs achados em produção, cada um ligado à tarefa
 * original, do mais recente para o mais antigo, com o total no topo. Não é retrabalho de ninguém.
 * Vazia: "Nenhum bug de produção registrado neste projeto".
 */
export function WQualityTabOrganism({ data }: WQualityTabOrganismProps) {
    const columns: WColumn<Row>[] = [
        {
            key: 'bug',
            header: 'Bug',
            cell: (row) => (
                <span className="inline-flex flex-wrap items-center gap-2">
                    <WTaskKeyAtom taskKey={row.task.key} href={row.task.url} title={row.task.title} />
                    <span className="font-medium">{row.task.title}</span>
                    {row.archived && <Badge variant="outline">arquivado</Badge>}
                </span>
            ),
        },
        {
            key: 'origin',
            header: 'Tarefa original',
            cell: (row) => (
                <span className="inline-flex flex-wrap items-center gap-2">
                    <WTaskKeyAtom taskKey={row.origin.key} href={row.origin.url} title={row.origin.title} />
                    <span>{row.origin.title}</span>
                </span>
            ),
        },
        { key: 'created', header: 'Criado em', cell: (row) => dateBr(row.created_on) },
        { key: 'stage', header: 'Etapa atual', cell: (row) => row.stage.name },
    ];

    return (
        <section data-slot="w-quality-tab-organism" aria-label="Qualidade" className="space-y-3">
            <p className="text-sm">
                <span className="font-mono text-xs text-muted-foreground uppercase">Bugs de produção</span>{' '}
                <span className="font-heading text-2xl font-semibold tabular-nums">{data.total}</span>
            </p>
            <WDataTableOrganism
                columns={columns}
                rows={data.rows}
                rowKey={(row) => row.task.id}
                caption="Bugs de produção e a tarefa original"
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Bug className="size-5" />}
                        title="Nenhum bug de produção registrado neste projeto"
                        description="Um bug de produção é um card criado ligado à tarefa original."
                    />
                }
            />
        </section>
    );
}
