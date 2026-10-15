import { Head, router } from '@inertiajs/react';
import { Clock, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { WTimeEntryFormDialogOrganism } from '@/components/organisms/w-time-entry-form-dialog-organism/w-time-entry-form-dialog-organism';
import { Button } from '@/components/shadcn/button';
import { Label } from '@/components/shadcn/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useRowAction } from '@/hooks/use-row-action';
import { hours } from '@/lib/quadro';
import { dateBr } from '@/lib/utils';
import type { TimeEntryFilters, TimeEntryRow, TimeEntryTaskOption, TimeEntryTotals } from '@/types/horas';

export interface WMyHoursPageProps {
    entries: TimeEntryRow[];
    totals: TimeEntryTotals;
    /** Os projetos que vejo, para o filtro. */
    projects: { id: number; key_prefix: string; name: string }[];
    tasks: TimeEntryTaskOption[];
    filters: TimeEntryFilters;
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo. */
    today: string;
}

const ALL = 'all';
const DAY = 24 * 60 * 60 * 1000;

/** `AAAA-MM-DD` ± dias, em UTC (nunca o fuso do navegador, como o `lib/dias-uteis.ts`). */
function shift(iso: string, days: number): string {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d) + days * DAY).toISOString().slice(0, 10);
}

/** Segunda a domingo da semana de `iso`. */
function weekOf(iso: string): { from: string; to: string } {
    const [y, m, d] = iso.split('-').map(Number);
    const weekday = new Date(Date.UTC(y, m - 1, d)).getUTCDay();
    const from = shift(iso, -((weekday + 6) % 7));
    return { from, to: shift(from, 6) };
}

function monthOf(iso: string): { from: string; to: string } {
    const [y, m] = iso.split('-').map(Number);
    return { from: `${iso.slice(0, 7)}-01`, to: new Date(Date.UTC(y, m, 0)).toISOString().slice(0, 10) };
}

/**
 * *Minhas horas* (Inertia: `time-entries/index`, `/minhas-horas`; F5 §02 a §05; P30): os meus
 * lançamentos no período (padrão: a semana atual, de segunda a domingo), com Data, Projeto, Tarefa
 * e Horas, o total por dia e o do período. Atalhos de semana anterior e mês atual, filtro por
 * projeto e busca por chave ou título. Lançar, corrigir e excluir pelos diálogos.
 */
export function WMyHoursPage({ entries, totals, projects, tasks, filters, today }: WMyHoursPageProps) {
    const rowAction = useRowAction();
    const [form, setForm] = useState<{ open: boolean; entry: TimeEntryRow | null }>({ open: false, entry: null });
    const [deleting, setDeleting] = useState<TimeEntryRow | null>(null);
    const url = '/minhas-horas';
    const params = { from: filters.from, to: filters.to, project_id: filters.project_id };

    const apply = (changes: Record<string, string | number | null | undefined>) =>
        router.get(
            url,
            { search: filters.search || undefined, ...params, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const current = weekOf(today);
    const SHORTCUTS = [
        { label: 'Semana atual', range: current },
        { label: 'Semana anterior', range: weekOf(shift(current.from, -7)) },
        { label: 'Mês atual', range: monthOf(today) },
    ];
    const isCurrentWeek = filters.from === current.from && filters.to === current.to;

    const launch = (
        <Button onClick={() => setForm({ open: true, entry: null })}>
            <Plus className="size-4" aria-hidden /> Lançar horas
        </Button>
    );

    const actions = (row: TimeEntryRow, labeled = false) => (
        <WRowActionsMolecule
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            actions={[
                {
                    label: 'Corrigir',
                    ariaLabel: `Corrigir ${hours(row.hours)} de ${dateBr(row.worked_on)} em ${row.task.key}`,
                    icon: Pencil,
                    onClick: () => setForm({ open: true, entry: row }),
                    hidden: !row.can.update,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${hours(row.hours)} de ${dateBr(row.worked_on)} em ${row.task.key}`,
                    icon: Trash2,
                    destructive: true,
                    onClick: () => setDeleting(row),
                    hidden: !row.can.delete,
                },
            ]}
        />
    );

    const columns: WColumn<TimeEntryRow>[] = [
        { key: 'date', header: 'Data', cell: (row) => <span className="tabular-nums">{dateBr(row.worked_on)}</span> },
        { key: 'project', header: 'Projeto', cell: (row) => `${row.project.key_prefix} · ${row.project.name}` },
        {
            key: 'task',
            header: 'Tarefa',
            cell: (row) => (
                <span className="flex items-center gap-2">
                    <WTaskKeyAtom taskKey={row.task.key} href={row.task.url} title={row.task.title} />
                    <span>{row.task.title}</span>
                </span>
            ),
        },
        { key: 'hours', header: 'Horas', className: 'text-right tabular-nums', cell: (row) => hours(row.hours) },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => actions(row) },
    ];

    return (
        <WAppLayoutTemplate
            eyebrow="Projetos"
            title="Minhas horas"
            subtitle="As horas que você lançou, por dia, sem cronômetro."
            width="wide"
            actions={launch}
        >
            <Head title="Minhas horas" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end gap-3">
                    <div className="space-y-1.5">
                        <Label>De</Label>
                        <WDatePickerMolecule
                            value={filters.from}
                            clearable={false}
                            label="Início do período"
                            onChange={(date) => date && apply({ from: date })}
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label>Até</Label>
                        <WDatePickerMolecule
                            value={filters.to}
                            clearable={false}
                            label="Fim do período"
                            onChange={(date) => date && apply({ to: date })}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2" role="group" aria-label="Atalhos de período">
                        {SHORTCUTS.map((shortcut) => {
                            const active = filters.from === shortcut.range.from && filters.to === shortcut.range.to;
                            return (
                                <Button
                                    key={shortcut.label}
                                    size="sm"
                                    variant={active ? 'default' : 'outline'}
                                    aria-pressed={active}
                                    onClick={() => apply(shortcut.range)}
                                >
                                    {shortcut.label}
                                </Button>
                            );
                        })}
                    </div>
                    <Select
                        value={filters.project_id ? String(filters.project_id) : ALL}
                        onValueChange={(value) => apply({ project_id: value === ALL ? undefined : value })}
                    >
                        <SelectTrigger className="w-full sm:w-56" aria-label="Filtrar por projeto">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Todos os projetos</SelectItem>
                            {projects.map((project) => (
                                <SelectItem key={project.id} value={String(project.id)}>
                                    {project.key_prefix} · {project.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <WSearchInputMolecule
                        url={url}
                        value={filters.search}
                        params={params}
                        placeholder="Chave ou título"
                        label="Buscar tarefa"
                        className="w-full sm:w-56"
                    />
                </div>

                <WDataTableOrganism
                    caption="Meus lançamentos de horas"
                    columns={columns}
                    rows={entries}
                    rowKey={(row) => row.id}
                    emptyState={
                        <WEmptyStateMolecule
                            icon={<Clock className="size-5" aria-hidden />}
                            title={
                                isCurrentWeek && !filters.search && !filters.project_id
                                    ? 'Nenhuma hora lançada nesta semana.'
                                    : 'Nenhuma hora lançada neste período.'
                            }
                            description="Lance as horas do dia em que trabalhou numa tarefa."
                            action={launch}
                        />
                    }
                    mobileCard={(row) => (
                        <div className="space-y-1 text-sm">
                            <div className="flex items-center justify-between gap-2">
                                <span className="tabular-nums">{dateBr(row.worked_on)}</span>
                                <span className="font-semibold tabular-nums">{hours(row.hours)}</span>
                            </div>
                            <p className="flex items-center gap-2">
                                <WTaskKeyAtom taskKey={row.task.key} href={row.task.url} size="sm" />
                                <span>{row.task.title}</span>
                            </p>
                            {actions(row, true)}
                        </div>
                    )}
                />

                {entries.length > 0 && (
                    <section aria-labelledby="hours-totals" className="space-y-2">
                        <h2 id="hours-totals" className="font-heading text-base font-semibold">
                            Totais
                        </h2>
                        <dl className="grid gap-2 text-sm sm:grid-cols-3 lg:grid-cols-4">
                            {totals.days.map((day) => (
                                <div key={day.date} className="flex items-center justify-between rounded-md border border-border px-3 py-2">
                                    <dt className="tabular-nums">{dateBr(day.date)}</dt>
                                    <dd className="font-medium tabular-nums">{hours(day.hours)}</dd>
                                </div>
                            ))}
                            <div className="flex items-center justify-between rounded-md border border-primary/40 bg-primary/5 px-3 py-2">
                                <dt className="font-medium">Total do período</dt>
                                <dd className="font-semibold tabular-nums">{hours(totals.total)}</dd>
                            </div>
                        </dl>
                    </section>
                )}
            </div>

            <WTimeEntryFormDialogOrganism
                open={form.open}
                onOpenChange={(open) => setForm((state) => ({ ...state, open }))}
                entry={form.entry}
                tasks={tasks}
                today={today}
            />

            <WConfirmDialogMolecule
                open={deleting !== null}
                onOpenChange={(next) => !next && setDeleting(null)}
                title="Excluir lançamento"
                description={
                    deleting
                        ? `Excluir ${hours(deleting.hours)} de ${dateBr(deleting.worked_on).slice(0, 5)} em ${deleting.task.key}?`
                        : undefined
                }
                confirmLabel="Excluir"
                destructive
                loading={rowAction.busy}
                onConfirm={() => deleting && rowAction.del(`/horas/${deleting.id}`, deleting, { onFinish: () => setDeleting(null) })}
            />
        </WAppLayoutTemplate>
    );
}
