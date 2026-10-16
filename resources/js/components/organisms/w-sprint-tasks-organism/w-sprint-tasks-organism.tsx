import { Link, router } from '@inertiajs/react';
import { Check, ClipboardList, Loader2, LogOut, Pencil, X } from 'lucide-react';
import { type FormEvent, useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { useRowAction } from '@/hooks/use-row-action';
import { hours } from '@/lib/quadro';
import { dateBr } from '@/lib/utils';
import type { SelectedSprint, SprintFilters, SprintQuickFilter, SprintTaskRow } from '@/types/sprints';

export interface WSprintTasksOrganismProps {
    projectId: number;
    sprint: SelectedSprint;
    filters: SprintFilters;
    /** Gestor do projeto e Administrador (`manageTasks`): horas na linha, reestimativa e *Tirar da sprint*, só na sprint aberta. */
    canManage: boolean;
}

/** O Radix Select não aceita item com valor vazio. */
const ALL = 'all';

const QUICK: { value: SprintQuickFilter; label: string }[] = [
    { value: 'no_developer', label: 'Sem dev' },
    { value: 'no_hours', label: 'Sem horas previstas' },
];

/**
 * Tarefas da sprint (F5 §03, §05): Chave, Título, Etapa, Dev, QA, Horas previstas, Horas lançadas
 * e Data prevista, pela ordem das etapas e da chave. Busca por chave e título; filtros Dev, Etapa
 * e os rápidos *Sem dev* e *Sem horas previstas*. Na sprint aberta, quem gere edita as horas
 * previstas na linha (P27), decide a reestimativa proposta e tira o card da sprint (P45).
 */
export function WSprintTasksOrganism({ projectId, sprint, filters, canManage }: WSprintTasksOrganismProps) {
    const rowAction = useRowAction();
    const [editing, setEditing] = useState<number | null>(null);
    const [removing, setRemoving] = useState<SprintTaskRow | null>(null);
    const editable = canManage && sprint.open;
    const url = `/projetos/${projectId}/sprints`;
    const params = {
        sprint: sprint.id,
        developer_id: filters.developer_id,
        stage_id: filters.stage_id,
        quick: filters.quick,
    };

    const apply = (changes: Record<string, string | number | null | undefined>) =>
        router.get(
            url,
            { search: filters.search || undefined, ...params, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const fromSelect = (value: string) => (value === ALL ? undefined : value);
    const task = (row: SprintTaskRow) => `/projetos/${projectId}/tarefas/${row.id}`;

    const estimated = (row: SprintTaskRow) =>
        editing === row.id ? (
            <HoursEditor
                row={row}
                busy={rowAction.isBusy(row)}
                onCancel={() => setEditing(null)}
                onSave={(value) =>
                    !rowAction.busy &&
                    router.put(
                        `${task(row)}/horas-previstas`,
                        { estimated_hours: value },
                        rowAction.request(row, { onFinish: () => setEditing(null) }),
                    )
                }
            />
        ) : (
            <div className="flex flex-col items-end gap-1">
                <span className="inline-flex items-center gap-1 tabular-nums">
                    {row.estimated_hours === null ? <span className="text-muted-foreground">—</span> : hours(row.estimated_hours)}
                    {editable && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={`Editar horas previstas de ${row.key}`}
                            disabled={rowAction.busy}
                            onClick={() => setEditing(row.id)}
                        >
                            <Pencil className="size-3.5" aria-hidden />
                        </Button>
                    )}
                </span>
                {row.proposed_hours !== null && (
                    <span className="flex flex-wrap items-center justify-end gap-1">
                        <Badge variant="warning">Reestimativa proposta: {hours(row.proposed_hours)}</Badge>
                        {editable && (
                            <>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="h-7"
                                    disabled={rowAction.busy}
                                    onClick={() => rowAction.post(`${task(row)}/reestimativa/aprovar`, row)}
                                >
                                    Aprovar
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    className="h-7"
                                    disabled={rowAction.busy}
                                    onClick={() => rowAction.post(`${task(row)}/reestimativa/recusar`, row)}
                                >
                                    Recusar
                                </Button>
                            </>
                        )}
                    </span>
                )}
            </div>
        );

    const actions = (row: SprintTaskRow, labeled = false) =>
        editable ? (
            <WRowActionsMolecule
                labeled={labeled}
                busy={rowAction.isBusy(row)}
                disabled={rowAction.isLocked(row)}
                actions={[
                    { label: 'Tirar da sprint', ariaLabel: `Tirar ${row.key} da sprint`, icon: LogOut, onClick: () => setRemoving(row) },
                ]}
            />
        ) : null;

    const columns: WColumn<SprintTaskRow>[] = [
        { key: 'key', header: 'Chave', cell: (row) => <WTaskKeyAtom taskKey={row.key} href={row.url} title={row.title} /> },
        { key: 'title', header: 'Título', cell: (row) => <span className="font-medium">{row.title}</span> },
        { key: 'stage', header: 'Etapa', cell: (row) => row.stage.name },
        { key: 'developer', header: 'Dev', cell: (row) => row.developer?.name ?? <span className="text-muted-foreground">Sem dev</span> },
        { key: 'qa', header: 'QA', cell: (row) => row.qa?.name ?? <span className="text-muted-foreground">—</span> },
        { key: 'estimated', header: 'Previstas', className: 'text-right', cell: estimated },
        { key: 'logged', header: 'Lançadas', className: 'text-right tabular-nums', cell: (row) => hours(row.logged_hours) },
        {
            key: 'due',
            header: 'Data prevista',
            cell: (row) => (row.due_on ? dateBr(row.due_on) : <span className="text-muted-foreground">—</span>),
        },
        ...(editable
            ? [
                  {
                      key: 'actions',
                      header: <span className="sr-only">Ações</span>,
                      className: 'text-right',
                      cell: (row: SprintTaskRow) => actions(row),
                  },
              ]
            : []),
    ];

    const filtered = filters.search !== '' || filters.developer_id !== null || filters.stage_id !== null || filters.quick !== null;

    return (
        <section data-slot="w-sprint-tasks-organism" aria-labelledby="sprint-tasks-title" className="space-y-3">
            <h2 id="sprint-tasks-title" className="font-heading text-lg font-semibold">
                Tarefas da {sprint.name}
            </h2>

            <div className="flex flex-wrap items-end gap-3">
                <WSearchInputMolecule
                    url={url}
                    value={filters.search}
                    params={params}
                    placeholder="Chave ou título"
                    label="Buscar tarefa"
                    className="w-full sm:w-64"
                />
                <Select
                    value={filters.developer_id ? String(filters.developer_id) : ALL}
                    onValueChange={(value) => apply({ developer_id: fromSelect(value) })}
                >
                    <SelectTrigger className="w-full sm:w-48" aria-label="Filtrar por dev">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos os devs</SelectItem>
                        {sprint.options.developers.map((dev) => (
                            <SelectItem key={dev.id} value={String(dev.id)}>
                                {dev.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select
                    value={filters.stage_id ? String(filters.stage_id) : ALL}
                    onValueChange={(value) => apply({ stage_id: fromSelect(value) })}
                >
                    <SelectTrigger className="w-full sm:w-44" aria-label="Filtrar por etapa">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todas as etapas</SelectItem>
                        {sprint.options.stages.map((stage) => (
                            <SelectItem key={stage.id} value={String(stage.id)}>
                                {stage.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <div className="flex flex-wrap gap-2" role="group" aria-label="Filtros rápidos">
                    {QUICK.map((quick) => {
                        const active = filters.quick === quick.value;
                        return (
                            <Button
                                key={quick.value}
                                size="sm"
                                variant={active ? 'default' : 'outline'}
                                aria-pressed={active}
                                onClick={() => apply({ quick: active ? undefined : quick.value })}
                            >
                                {quick.label}
                            </Button>
                        );
                    })}
                </div>
            </div>

            <WDataTableOrganism
                caption={`Tarefas da ${sprint.name}`}
                columns={columns}
                rows={sprint.tasks}
                rowKey={(row) => row.id}
                emptyState={
                    <WEmptyStateMolecule
                        icon={<ClipboardList className="size-5" aria-hidden />}
                        title={filtered ? 'Nenhuma tarefa com esses filtros.' : 'Nenhuma tarefa nesta sprint.'}
                        description={
                            filtered
                                ? 'Limpe a busca ou os filtros para ver todas.'
                                : 'Monte a sprint pelo quadro: Montar sprint marca várias tarefas do Backlog de uma vez.'
                        }
                        action={
                            filtered ? undefined : (
                                <Button variant="outline" asChild>
                                    <Link href={`/projetos/${projectId}/quadro`}>Abrir o quadro</Link>
                                </Button>
                            )
                        }
                    />
                }
                mobileCard={(row) => (
                    <div className="space-y-1 text-sm">
                        <p className="flex items-center gap-2">
                            <WTaskKeyAtom taskKey={row.key} href={row.url} size="sm" />
                            <span className="font-semibold">{row.title}</span>
                        </p>
                        <p className="text-muted-foreground">
                            {row.stage.name} · {row.developer?.name ?? 'Sem dev'} · QA {row.qa?.name ?? '—'}
                        </p>
                        <p>
                            Lançadas {hours(row.logged_hours)} · data prevista {row.due_on ? dateBr(row.due_on) : '—'}
                        </p>
                        <div className="flex justify-start">{estimated(row)}</div>
                        {actions(row, true)}
                    </div>
                )}
            />

            <WConfirmDialogMolecule
                open={removing !== null}
                onOpenChange={(next) => !next && setRemoving(null)}
                title="Tirar da sprint"
                description={removing ? `${removing.key} volta ao Backlog, fora de sprint. Não conta como retrabalho.` : undefined}
                confirmLabel="Tirar da sprint"
                loading={rowAction.busy}
                onConfirm={() => removing && rowAction.del(`${task(removing)}/sprint`, removing, { onFinish: () => setRemoving(null) })}
            />
        </section>
    );
}

/** Campo de horas previstas na linha: Enter salva, Esc cancela; vazio limpa (P27). */
function HoursEditor({
    row,
    busy,
    onSave,
    onCancel,
}: {
    row: SprintTaskRow;
    busy: boolean;
    onSave: (value: number | null) => void;
    onCancel: () => void;
}) {
    const [text, setText] = useState(row.estimated_hours === null ? '' : String(row.estimated_hours));
    const value = text.trim() === '' ? null : Number(text.replace(',', '.'));
    const invalid = value !== null && (!Number.isFinite(value) || value <= 0);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!invalid && !busy) onSave(value);
    };

    return (
        <form onSubmit={submit} className="flex items-center justify-end gap-1" onKeyDown={(event) => event.key === 'Escape' && onCancel()}>
            <Input
                autoFocus
                inputMode="decimal"
                value={text}
                disabled={busy}
                onChange={(event) => setText(event.target.value)}
                aria-label={`Horas previstas de ${row.key}`}
                aria-invalid={invalid}
                className="h-8 w-20 text-right tabular-nums"
            />
            <Button
                type="submit"
                size="icon"
                variant="ghost"
                className="size-8"
                disabled={invalid || busy}
                aria-label="Salvar horas previstas"
            >
                {busy ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
            </Button>
            <Button type="button" size="icon" variant="ghost" className="size-8" disabled={busy} onClick={onCancel} aria-label="Cancelar">
                <X className="size-4" aria-hidden />
            </Button>
        </form>
    );
}
