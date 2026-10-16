import { AlertTriangle, Gauge } from 'lucide-react';
import { useId } from 'react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { hours } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { CapacityPanel } from '@/types/sprints';

export interface WCapacityPanelOrganismProps {
    capacity: CapacityPanel;
    /**
     * `sprint` (padrão): a tabela da tela de sprints, com *Faltam* e *Diferença*. `plan`: o *Montar sprint* do
     * quadro (D-GER-35), em lista compacta para caber ao lado do plano, com *Atribuídas* e *Disponível*, e toda
     * pessoa alocada, mesmo sem card. A conta é a mesma (`CapacityQuery` no servidor, `planCapacity` na tela).
     */
    variant?: 'sprint' | 'plan';
}

type DevRow = CapacityPanel['developers'][number];

const NO_LOAD = 'sem carga diária: capacidade não calculada';

/**
 * Painel *Capacidade* da sprint (F5 §03; P48, P49, N3): por dev com tarefa na sprint, a carga
 * diária, a capacidade (carga × dias úteis), as horas que faltam e a diferença. Diferença negativa
 * em destaque, só como aviso: nada no sistema trava por ela (P49). Abaixo, à parte, as tarefas sem
 * dev e as sem horas previstas. Só para quem gere o projeto (F5 §09): a tela nem recebe para o dev.
 */
export function WCapacityPanelOrganism({ capacity, variant = 'sprint' }: WCapacityPanelOrganismProps) {
    const titleId = useId();
    const over = (row: DevRow) => row.difference !== null && row.difference < 0;

    const difference = (row: DevRow) => {
        if (row.difference === null) return <span className="text-muted-foreground">—</span>;
        return over(row) ? (
            <span className="inline-flex items-center gap-1 font-medium text-destructive">
                <AlertTriangle className="size-3.5" aria-hidden /> {hours(row.difference)}{' '}
                <span className="sr-only">(acima da capacidade)</span>
            </span>
        ) : (
            <span className="tabular-nums">{hours(row.difference)}</span>
        );
    };

    const columns: WColumn<DevRow>[] = [
        { key: 'name', header: 'Dev', cell: (row) => <span className="font-medium">{row.name}</span> },
        {
            key: 'daily_hours',
            header: 'Carga diária',
            cell: (row) => (row.daily_hours === null ? <span className="text-muted-foreground">{NO_LOAD}</span> : hours(row.daily_hours)),
        },
        {
            key: 'capacity',
            header: 'Capacidade',
            className: 'text-right tabular-nums',
            cell: (row) => (row.capacity === null ? '—' : hours(row.capacity)),
        },
        { key: 'remaining', header: 'Faltam', className: 'text-right tabular-nums', cell: (row) => hours(row.remaining) },
        { key: 'difference', header: 'Diferença', className: 'text-right', cell: difference },
    ];

    const summary = (
        <dl className={cn('grid gap-2 text-sm', variant === 'sprint' && 'sm:grid-cols-2')}>
            <div className="rounded-md border border-border px-3 py-2">
                <dt className="font-mono text-xs text-muted-foreground uppercase">Sem dev</dt>
                <dd>
                    {capacity.without_developer.tasks} tarefas · {hours(capacity.without_developer.hours)} previstas
                </dd>
            </div>
            <div className="rounded-md border border-border px-3 py-2">
                <dt className="font-mono text-xs text-muted-foreground uppercase">Sem horas previstas</dt>
                <dd>{capacity.without_hours.tasks} tarefas</dd>
            </div>
        </dl>
    );

    if (variant === 'plan') {
        return (
            <section data-slot="w-capacity-panel-organism" data-variant="plan" aria-labelledby={titleId} className="space-y-3">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 id={titleId} className="font-heading text-base font-semibold">
                        Capacidade
                    </h3>
                    <p className="font-mono text-xs text-muted-foreground uppercase">{capacity.business_days} dias úteis · só referência</p>
                </div>
                {capacity.developers.length === 0 ? (
                    <p className="text-sm text-muted-foreground">Nenhuma pessoa alocada no projeto.</p>
                ) : (
                    <ul aria-label="Capacidade por pessoa" className="divide-y divide-border rounded-md border border-border">
                        {capacity.developers.map((row) => (
                            <li key={row.id} className={cn('space-y-0.5 px-3 py-2 text-sm', over(row) && 'bg-destructive/5')}>
                                <p className="flex items-baseline justify-between gap-2">
                                    <span className="font-medium">{row.name}</span>
                                    <span className="text-xs text-muted-foreground">
                                        {row.daily_hours === null
                                            ? 'sem carga diária'
                                            : `${hours(row.daily_hours)}/dia · ${hours(row.capacity ?? 0)}`}
                                    </span>
                                </p>
                                <p className="flex items-baseline justify-between gap-2 text-xs">
                                    <span>Atribuídas {hours(row.remaining)}</span>
                                    <span>Disponível {difference(row)}</span>
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
                {summary}
            </section>
        );
    }

    return (
        <section data-slot="w-capacity-panel-organism" aria-labelledby={titleId} className="space-y-3">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id={titleId} className="font-heading text-lg font-semibold">
                    Capacidade
                </h2>
                <p className="font-mono text-xs text-muted-foreground uppercase">{capacity.business_days} dias úteis · só referência</p>
            </div>

            <WDataTableOrganism
                caption="Capacidade por dev na sprint"
                columns={columns}
                rows={capacity.developers}
                rowKey={(row) => row.id}
                rowClassName={(row) => (over(row) ? 'bg-destructive/5' : undefined)}
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Gauge className="size-5" aria-hidden />}
                        title="Nenhum dev com tarefa nesta sprint."
                        description="A capacidade aparece quando a sprint tem tarefa com dev."
                    />
                }
                mobileCard={(row) => (
                    <div className={cn('space-y-1 text-sm')}>
                        <p className="font-semibold">{row.name}</p>
                        <p className="text-muted-foreground">
                            {row.daily_hours === null ? NO_LOAD : `${hours(row.daily_hours)}/dia · capacidade ${hours(row.capacity ?? 0)}`}
                        </p>
                        <p>
                            Faltam {hours(row.remaining)} · diferença {difference(row)}
                        </p>
                    </div>
                )}
            />

            {summary}
        </section>
    );
}
