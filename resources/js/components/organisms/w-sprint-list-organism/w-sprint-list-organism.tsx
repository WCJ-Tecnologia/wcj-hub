import { router } from '@inertiajs/react';
import { CalendarRange, Eye, Flag, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { type WRowAction, WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Card } from '@/components/shadcn/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { cn, dateBr } from '@/lib/utils';
import type { SprintFilters, SprintRow } from '@/types/sprints';

export interface WSprintListOrganismProps {
    projectId: number;
    sprints: SprintRow[];
    /** A sprint cujas tarefas a tela mostra. */
    selectedId: number | null;
    filters: SprintFilters;
    /** A linha cuja ação está rodando e a trava das outras (`useRowAction`). */
    isBusy: (row: SprintRow) => boolean;
    isLocked: (row: SprintRow) => boolean;
    onEdit: (row: SprintRow) => void;
    onClose: (row: SprintRow) => void;
    onReopen: (row: SprintRow) => void;
    onDelete: (row: SprintRow) => void;
    /** O botão *Nova sprint*, quando o projeto não tem aberta e a pessoa pode criar. */
    newButton?: ReactNode;
}

const ALL = 'all';

const period = (row: SprintRow) => `${dateBr(row.starts_on)} a ${dateBr(row.ends_on)}`;

/**
 * Lista de sprints do projeto (F5 §03, §05): a aberta em destaque no topo e as encerradas abaixo,
 * da mais recente para a mais antiga, com Nome, Período, Dias úteis, Objetivo, Situação e
 * Tarefas (prontas de total). *Ver tarefas* troca a sprint mostrada embaixo. Busca por nome e
 * filtro de situação. As ações aparecem pelo `can` de cada linha.
 */
export function WSprintListOrganism({
    projectId,
    sprints,
    selectedId,
    filters,
    isBusy,
    isLocked,
    onEdit,
    onClose,
    onReopen,
    onDelete,
    newButton,
}: WSprintListOrganismProps) {
    const url = `/projetos/${projectId}/sprints`;
    const open = sprints.find((row) => row.open) ?? null;
    const closed = sprints.filter((row) => !row.open);

    const select = (row: SprintRow) =>
        router.get(
            url,
            { sprint: row.id, search: filters.search || undefined, status: filters.status ?? undefined },
            { preserveState: true, preserveScroll: true },
        );

    const actionsOf = (row: SprintRow): WRowAction[] => [
        {
            label: 'Ver tarefas',
            ariaLabel: `Ver tarefas da ${row.name}`,
            icon: Eye,
            onClick: () => select(row),
            hidden: row.id === selectedId,
        },
        { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => onEdit(row), hidden: !row.can.update },
        { label: 'Encerrar', ariaLabel: `Encerrar ${row.name}`, icon: Flag, onClick: () => onClose(row), hidden: !row.can.close },
        { label: 'Reabrir', ariaLabel: `Reabrir ${row.name}`, icon: RotateCcw, onClick: () => onReopen(row), hidden: !row.can.reopen },
        {
            label: 'Excluir',
            ariaLabel: `Excluir ${row.name}`,
            icon: Trash2,
            destructive: true,
            onClick: () => onDelete(row),
            hidden: !row.can.delete,
        },
    ];

    const rowActions = (row: SprintRow, labeled = false) => (
        <WRowActionsMolecule labeled={labeled} busy={isBusy(row)} disabled={isLocked(row)} actions={actionsOf(row)} />
    );

    const progress = (row: SprintRow) => (
        <span className="font-mono text-sm tabular-nums">
            {row.tasks_done} de {row.tasks_total}
            <span className="sr-only"> tarefas prontas</span>
        </span>
    );

    const columns: WColumn<SprintRow>[] = [
        { key: 'name', header: 'Nome', cell: (row) => <span className="font-medium">{row.name}</span> },
        { key: 'period', header: 'Período', cell: period },
        { key: 'days', header: 'Dias úteis', className: 'text-right tabular-nums', cell: (row) => row.business_days },
        { key: 'goal', header: 'Objetivo', cell: (row) => row.goal ?? <span className="text-muted-foreground">—</span> },
        {
            key: 'status',
            header: 'Situação',
            cell: (row) => (
                <WStatusPillAtom kind="sprint" status={row.open} label={row.closed_at ? `Encerrada em ${row.closed_at}` : undefined} />
            ),
        },
        { key: 'tasks', header: 'Prontas', cell: progress },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    const changeStatus = (value: string) =>
        router.get(
            url,
            { search: filters.search || undefined, status: value === ALL ? undefined : value },
            { preserveState: true, replace: true },
        );

    return (
        <section data-slot="w-sprint-list-organism" aria-labelledby="sprints-title" className="space-y-4">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <h2 id="sprints-title" className="font-heading text-lg font-semibold">
                    Sprints
                </h2>
                <div className="flex flex-wrap items-end gap-3">
                    <WSearchInputMolecule
                        url={url}
                        value={filters.search}
                        params={{ status: filters.status }}
                        placeholder="Nome da sprint"
                        label="Buscar sprint"
                        className="w-full sm:w-56"
                    />
                    <Select value={filters.status ?? ALL} onValueChange={changeStatus}>
                        <SelectTrigger className="w-full sm:w-40" aria-label="Filtrar por situação">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Todas</SelectItem>
                            <SelectItem value="open">Aberta</SelectItem>
                            <SelectItem value="closed">Encerradas</SelectItem>
                        </SelectContent>
                    </Select>
                    {newButton}
                </div>
            </div>

            {open && (
                <Card
                    className={cn('gap-3 border-primary/40 p-4', open.id === selectedId && 'ring-2 ring-primary/30')}
                    aria-label={`Sprint aberta: ${open.name}`}
                >
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0 space-y-1">
                            <p className="font-mono text-xs tracking-wide text-muted-foreground uppercase">Sprint aberta</p>
                            <p className="font-heading text-xl font-semibold">{open.name}</p>
                            {open.goal && <p className="text-sm text-muted-foreground">{open.goal}</p>}
                        </div>
                        <WStatusPillAtom kind="sprint" status />
                    </div>
                    <dl className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="font-mono text-xs text-muted-foreground uppercase">Período</dt>
                            <dd>{period(open)}</dd>
                        </div>
                        <div>
                            <dt className="font-mono text-xs text-muted-foreground uppercase">Dias úteis</dt>
                            <dd className="tabular-nums">{open.business_days}</dd>
                        </div>
                        <div>
                            <dt className="font-mono text-xs text-muted-foreground uppercase">Prontas</dt>
                            <dd>{progress(open)}</dd>
                        </div>
                    </dl>
                    {rowActions(open, true)}
                </Card>
            )}

            {closed.length > 0 || !open ? (
                <WDataTableOrganism
                    caption="Sprints encerradas"
                    columns={columns}
                    rows={closed}
                    rowKey={(row) => row.id}
                    rowClassName={(row) => (row.id === selectedId ? 'bg-primary/5' : undefined)}
                    emptyState={
                        <WEmptyStateMolecule
                            icon={<CalendarRange className="size-5" aria-hidden />}
                            title={filters.search || filters.status ? 'Nenhuma sprint com esses filtros.' : 'Nenhuma sprint ainda.'}
                            description="A sprint é a fase do projeto: é por ela que os cards saem do Backlog. Monte a sprint pelo quadro, em Montar sprint."
                            action={newButton}
                        />
                    }
                    mobileCard={(row) => (
                        <div className="space-y-1 text-sm">
                            <div className="flex items-start justify-between gap-2">
                                <p className="font-semibold">{row.name}</p>
                                <WStatusPillAtom
                                    kind="sprint"
                                    status={row.open}
                                    size="sm"
                                    label={row.closed_at ? `Encerrada em ${row.closed_at}` : undefined}
                                />
                            </div>
                            <p className="text-muted-foreground">
                                {period(row)} · {row.business_days} dias úteis
                            </p>
                            <p>{progress(row)} prontas</p>
                            {rowActions(row, true)}
                        </div>
                    )}
                />
            ) : null}
        </section>
    );
}
