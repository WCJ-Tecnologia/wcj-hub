import { Loader2, X } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { sprintFieldErrors, WSprintFieldsMolecule } from '@/components/molecules/w-sprint-fields-molecule/w-sprint-fields-molecule';
import { WCapacityPanelOrganism } from '@/components/organisms/w-capacity-panel-organism/w-capacity-panel-organism';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { businessDaysBetween } from '@/lib/dias-uteis';
import { hours, planCapacity, planItemOf, planSummaryText, PRIORITIES, PRIORITY_LABEL, proposedSprint } from '@/lib/quadro';
import { dateBr } from '@/lib/utils';
import type { BoardCard, BoardProject, PrioritySlug, SprintBatchPayload, SprintPlanItem, SprintPlanning } from '@/types/quadro';
import type { SprintFields } from '@/types/sprints';

export interface WStartSprintDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** `open_sprint` decide o caso; `sprints_count` e `sprint_days`, a sprint proposta (P46). */
    project: BoardProject;
    /** Os cards marcados no Backlog, na ordem em que vão no payload. */
    cards: BoardCard[];
    /** Pessoas alocadas com a carga diária e o painel da sprint aberta (`SprintPlanningQuery`). */
    planning: SprintPlanning;
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo: o início proposto. */
    today: string;
    /** Tira o card do plano (e da seleção do quadro). */
    onRemove: (card: BoardCard) => void;
    /** Gravando: desabilita, mostra o spinner e impede fechar. */
    loading?: boolean;
    /**
     * Erros do servidor: `tasks` (o lote recusado, com as chaves), `tasks.{i}.developer_id` / `qa_id` /
     * `estimated_hours` / `priority` (na linha do card), `new_sprint.*` e `name` (nome repetido).
     */
    errors?: Record<string, string>;
    onConfirm: (payload: SprintBatchPayload) => void;
}

/** O Radix Select não aceita item de valor vazio. */
const NONE = 'none';

/**
 * *Iniciar sprint* / *Pôr na Sprint N* do quadro: o passo de planejamento do *Montar sprint* (D-GER-35;
 * F5 §01 "Horas na montagem" e "Capacidade", P27, P48, P49). Em cima, a sprint: os campos da
 * `WSprintFieldsMolecule` com a proposta quando não há aberta, ou o nome e o período da aberta; o período
 * manda nos dias úteis e, com eles, na capacidade. Embaixo, uma linha por card marcado, editável: dev e QA
 * (pessoas alocadas, com a carga diária), horas previstas e prioridade, e *Tirar* para deixar o card no
 * Backlog. Ao lado (em cima no celular), o `WCapacityPanelOrganism` do plano, ao vivo pela `planCapacity()`:
 * por pessoa, capacidade, atribuídas e disponível, negativo em destaque e nunca trava. Nada é obrigatório
 * (P27, P49). Confirmar devolve o `SprintBatchPayload`; o servidor grava tudo numa transação.
 */
export function WStartSprintDialogOrganism({ open, onOpenChange, loading = false, ...props }: WStartSprintDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-start-sprint-dialog-organism" className="sm:max-w-7xl">
                {/* Desmontado ao fechar: a próxima abertura volta à proposta e ao que os cards têm. */}
                {open && <PlanForm {...props} loading={loading} onCancel={() => onOpenChange(false)} />}
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WStartSprintDialogOrganismProps, 'open' | 'onOpenChange' | 'loading'> & { loading: boolean; onCancel: () => void };

/** "8,5" → 8.5; vazio → null; inválido → NaN. */
function parseHours(text: string): number | null {
    const value = text.trim();
    return value === '' ? null : Number(value.replace(',', '.'));
}

function PlanForm({ project, cards, planning, today, onRemove, loading, errors = {}, onConfirm, onCancel }: FormProps) {
    const formId = useId();
    const openSprint = planning.open_sprint ?? (project.open_sprint ? { ...project.open_sprint, starts_on: today, ends_on: today } : null);
    const [sprint, setSprint] = useState<SprintFields>(() => proposedSprint(project, today));
    const [tried, setTried] = useState(false);
    // O plano de cada card, começando pelo que o card já tem; a hora vai como texto até mandar.
    const [plan, setPlan] = useState<Record<number, SprintPlanItem>>(() =>
        Object.fromEntries(cards.map((card) => [card.id, planItemOf(card)])),
    );
    const [hoursText, setHoursText] = useState<Record<number, string>>(() =>
        Object.fromEntries(
            cards.map((card) => [card.id, card.estimated_hours === null ? '' : String(card.estimated_hours).replace('.', ',')]),
        ),
    );

    const items = cards.map((card) => {
        const parsed = parseHours(hoursText[card.id] ?? '');
        return {
            ...(plan[card.id] ?? planItemOf(card)),
            estimated_hours: parsed !== null && Number.isFinite(parsed) && parsed > 0 ? parsed : null,
        };
    });
    const hoursInvalid = (card: BoardCard) => {
        const parsed = parseHours(hoursText[card.id] ?? '');
        return parsed !== null && (!Number.isFinite(parsed) || parsed <= 0 || parsed > 9999.99);
    };

    const period = openSprint ? { starts_on: openSprint.starts_on, ends_on: openSprint.ends_on } : sprint;
    const capacity = planCapacity(planning, items, period);
    const person = (id: number | null) => planning.people.find((item) => item.id === id);

    const sprintErrors: Partial<Record<keyof SprintFields, string>> = {
        ...Object.fromEntries(
            Object.entries(errors)
                .filter(([key]) => key.startsWith('new_sprint.'))
                .map(([key, message]) => [key.slice('new_sprint.'.length), message]),
        ),
        ...(errors.name ? { name: errors.name } : {}),
    };
    const indexOf = (card: BoardCard) => cards.findIndex((item) => item.id === card.id);
    const rowError = (card: BoardCard, field: 'developer_id' | 'qa_id' | 'estimated_hours' | 'priority') =>
        field === 'estimated_hours' && hoursInvalid(card) ? 'Informe horas maiores que zero' : errors[`tasks.${indexOf(card)}.${field}`];

    const change = (card: BoardCard, changes: Partial<SprintPlanItem>) =>
        setPlan((current) => ({ ...current, [card.id]: { ...(current[card.id] ?? planItemOf(card)), ...changes } }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (loading || cards.length === 0 || cards.some(hoursInvalid)) return;
        if (!openSprint && Object.keys(sprintFieldErrors(sprint)).length > 0) return;
        onConfirm({
            tasks: items,
            ...(openSprint
                ? {}
                : {
                      new_sprint: {
                          name: sprint.name.trim(),
                          goal: sprint.goal.trim(),
                          starts_on: sprint.starts_on,
                          ends_on: sprint.ends_on,
                      },
                  }),
        });
    };

    const personLabel = (id: number) => {
        const item = person(id);
        if (!item) return 'Pessoa fora do projeto';
        return item.daily_hours === null ? `${item.name} · sem carga diária` : `${item.name} · ${hours(item.daily_hours)}/dia`;
    };

    const personSelect = (card: BoardCard, field: 'developer_id' | 'qa_id', label: string) => {
        const value = plan[card.id]?.[field] ?? null;
        const error = rowError(card, field);
        const errorId = `${formId}-${card.id}-${field}-error`;
        return (
            <div className="min-w-0 space-y-1">
                <Select
                    value={value === null ? NONE : String(value)}
                    onValueChange={(next) => change(card, { [field]: next === NONE ? null : Number(next) })}
                    disabled={loading}
                >
                    <SelectTrigger
                        className="w-full min-w-32"
                        aria-label={`${label} de ${card.key}`}
                        aria-invalid={error ? true : undefined}
                        aria-describedby={error ? errorId : undefined}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NONE}>{field === 'developer_id' ? 'Sem dev' : 'Sem QA'}</SelectItem>
                        {planning.people.map((item) => (
                            <SelectItem key={item.id} value={String(item.id)}>
                                {personLabel(item.id)}
                            </SelectItem>
                        ))}
                        {value !== null && !person(value) && <SelectItem value={String(value)}>{personLabel(value)}</SelectItem>}
                    </SelectContent>
                </Select>
                {error && (
                    <p id={errorId} className="text-xs text-destructive">
                        {error}
                    </p>
                )}
            </div>
        );
    };

    const hoursField = (card: BoardCard) => {
        const error = rowError(card, 'estimated_hours');
        const errorId = `${formId}-${card.id}-hours-error`;
        return (
            <div className="space-y-1">
                <Input
                    inputMode="decimal"
                    value={hoursText[card.id] ?? ''}
                    onChange={(event) => setHoursText((current) => ({ ...current, [card.id]: event.target.value }))}
                    placeholder="—"
                    disabled={loading}
                    aria-label={`Horas previstas de ${card.key}`}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? errorId : undefined}
                    className="w-20 text-right tabular-nums"
                />
                {error && (
                    <p id={errorId} className="text-xs text-destructive">
                        {error}
                    </p>
                )}
            </div>
        );
    };

    const prioritySelect = (card: BoardCard) => {
        const value = plan[card.id]?.priority;
        return (
            <Select
                value={value ?? NONE}
                onValueChange={(next) => next !== NONE && change(card, { priority: next as PrioritySlug })}
                disabled={loading}
            >
                <SelectTrigger className="w-full min-w-24" aria-label={`Prioridade de ${card.key}`}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {/* O plano escolhe uma prioridade, não tira (P19); sem nenhuma, fica como está. */}
                    {!value && <SelectItem value={NONE}>Sem prioridade</SelectItem>}
                    {PRIORITIES.map((slug) => (
                        <SelectItem key={slug} value={slug}>
                            {PRIORITY_LABEL[slug]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        );
    };

    const removeButton = (card: BoardCard, labeled = false) => (
        <Button
            type="button"
            variant="ghost"
            size={labeled ? 'sm' : 'icon'}
            className={labeled ? undefined : 'size-8'}
            aria-label={`Tirar ${card.key} do plano`}
            disabled={loading}
            onClick={() => onRemove(card)}
        >
            <X className="size-4" aria-hidden /> {labeled && 'Tirar do plano'}
        </Button>
    );

    const columns: WColumn<BoardCard>[] = [
        {
            key: 'card',
            header: 'Card',
            cell: (card) => (
                <span className="flex max-w-48 min-w-0 flex-col">
                    <WTaskKeyAtom taskKey={card.key} size="sm" />
                    <span className="line-clamp-2 text-sm font-medium">{card.title}</span>
                </span>
            ),
        },
        { key: 'developer', header: 'Dev', cell: (card) => personSelect(card, 'developer_id', 'Dev') },
        { key: 'qa', header: 'QA', cell: (card) => personSelect(card, 'qa_id', 'QA') },
        { key: 'hours', header: 'Horas', cell: hoursField },
        { key: 'priority', header: 'Prioridade', cell: prioritySelect },
        { key: 'remove', header: <span className="sr-only">Tirar</span>, className: 'text-right', cell: (card) => removeButton(card) },
    ];

    const batchError = errors.tasks;
    const label = openSprint ? `Pôr na ${openSprint.name}` : 'Iniciar sprint';

    return (
        <>
            <DialogHeader>
                <DialogTitle>{label}</DialogTitle>
                <DialogDescription>
                    Planeje antes de começar: quem faz cada card, as horas e a prioridade, com a capacidade de cada pessoa ao lado. Nada é
                    obrigatório; os cards vão para o To do.
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate className="space-y-6">
                    {batchError && (
                        <p
                            role="alert"
                            className="rounded-md border border-destructive/40 bg-destructive/5 px-3 py-2 text-sm text-destructive"
                        >
                            {batchError}
                        </p>
                    )}

                    {openSprint ? (
                        <p className="rounded-md border border-border bg-muted/40 px-3 py-2 text-sm">
                            <span className="font-semibold">{openSprint.name}</span> · {dateBr(openSprint.starts_on)} a{' '}
                            {dateBr(openSprint.ends_on)} · {businessDaysBetween(openSprint.starts_on, openSprint.ends_on)} dias úteis
                        </p>
                    ) : (
                        <section aria-labelledby={`${formId}-sprint`} className="space-y-3">
                            <h3 id={`${formId}-sprint`} className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                Nova sprint
                            </h3>
                            <WSprintFieldsMolecule
                                value={sprint}
                                onChange={setSprint}
                                sprintDays={project.sprint_days}
                                errors={sprintErrors}
                                tried={tried}
                                disabled={loading}
                            />
                        </section>
                    )}

                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
                        <div className="order-2 min-w-0 space-y-3 xl:order-1">
                            <h3 className="font-mono text-xs tracking-wide text-muted-foreground uppercase">Cards do plano</h3>
                            {cards.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Nenhum card no plano: volte ao quadro e marque no Backlog.</p>
                            ) : (
                                <WDataTableOrganism
                                    caption="Cards do plano"
                                    columns={columns}
                                    rows={cards}
                                    rowKey={(card) => card.id}
                                    mobileCard={(card) => (
                                        <div className="space-y-2 text-sm">
                                            <p className="flex items-baseline gap-2">
                                                <WTaskKeyAtom taskKey={card.key} size="sm" />
                                                <span className="font-semibold">{card.title}</span>
                                            </p>
                                            <div className="grid gap-2">
                                                {personSelect(card, 'developer_id', 'Dev')}
                                                {personSelect(card, 'qa_id', 'QA')}
                                                <div className="flex items-start gap-2">
                                                    {hoursField(card)}
                                                    <div className="flex-1">{prioritySelect(card)}</div>
                                                </div>
                                            </div>
                                            {removeButton(card, true)}
                                        </div>
                                    )}
                                />
                            )}
                            <p role="status" className="text-sm text-muted-foreground">
                                {planSummaryText(items)}
                            </p>
                        </div>
                        <div className="order-1 xl:order-2">
                            <WCapacityPanelOrganism capacity={capacity} variant="plan" />
                        </div>
                    </div>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onCancel} disabled={loading}>
                    Cancelar
                </Button>
                <Button type="submit" form={formId} disabled={loading || cards.length === 0}>
                    {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    {label}
                </Button>
            </DialogFooter>
        </>
    );
}
