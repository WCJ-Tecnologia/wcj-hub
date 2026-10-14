import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { Textarea } from '@/components/shadcn/textarea';
import { addBusinessDays, businessDaysBetween } from '@/lib/dias-uteis';
import type { BoardCard, BoardProject } from '@/types/quadro';

export const SPRINT_NAME_MAX = 80;
export const SPRINT_GOAL_MAX = 500;

export interface NewSprintPayload {
    name: string;
    /** Opcional (P45, R5): vazio vai como `''`. */
    goal: string;
    starts_on: string;
    ends_on: string;
}

export interface WNewSprintDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O card que está saindo do Backlog e entra na sprint nova. */
    card: BoardCard;
    /** `sprints_count` dá o nome "Sprint N" e `sprint_days`, o fim proposto (P46). */
    project: BoardProject;
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo: o início proposto. A tela passa, para a story ser fixa. */
    today: string;
    /** Desabilita, mostra o spinner no botão e impede fechar. */
    loading?: boolean;
    /** Erros do servidor por campo (`name`, `goal`, `starts_on`, `ends_on`), ex.: nome repetido no projeto. */
    errors?: Record<string, string>;
    onConfirm: (payload: NewSprintPayload) => void;
}

/**
 * Diálogo *Nova sprint* (F5 §02, R5, P45, P46): abre quando o gestor arrasta um card do
 * Backlog e o projeto não tem sprint aberta. Confirmar cria a sprint e põe o card nela,
 * numa ação só; cancelar deixa o card no Backlog (a tela cuida).
 */
export function WNewSprintDialogOrganism({ open, onOpenChange, loading = false, ...props }: WNewSprintDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-new-sprint-dialog-organism">
                {/* Desmontado ao fechar: a próxima abertura volta à proposta. */}
                <NewSprintForm {...props} loading={loading} onCancel={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WNewSprintDialogOrganismProps, 'open' | 'onOpenChange' | 'loading'> & { loading: boolean; onCancel: () => void };

function NewSprintForm({ card, project, today, loading, errors, onConfirm, onCancel }: FormProps) {
    const formId = useId();
    const [name, setName] = useState(`Sprint ${project.sprints_count + 1}`);
    const [goal, setGoal] = useState('');
    const [start, setStart] = useState(today);
    const [end, setEnd] = useState(() => addBusinessDays(today, project.sprint_days));
    // Enquanto a pessoa não mexe no fim, ele acompanha o início pela duração padrão.
    const [endTouched, setEndTouched] = useState(false);
    const [tried, setTried] = useState(false);

    const endBeforeStart = start !== '' && end !== '' && end < start;
    const missing = { name: name.trim() === '', start: start === '', end: end === '' };

    const fieldError = (key: string, local: boolean, message: string) => errors?.[key] ?? (tried && local ? message : undefined);
    const nameError = fieldError('name', missing.name, 'Informe o nome.');
    const goalError = errors?.goal;
    const startError = fieldError('starts_on', missing.start, 'Informe o início.');
    // Fim antes do início aparece na hora, sem esperar o envio.
    const endError =
        errors?.ends_on ?? (endBeforeStart ? 'O fim não pode ser antes do início' : tried && missing.end ? 'Informe o fim.' : undefined);

    const businessDays = start && end && !endBeforeStart ? businessDaysBetween(start, end) : null;

    const changeStart = (value: string | null) => {
        const next = value ?? '';
        setStart(next);
        if (!endTouched && next) setEnd(addBusinessDays(next, project.sprint_days));
    };

    const changeEnd = (value: string | null) => {
        setEndTouched(true);
        setEnd(value ?? '');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (loading || endBeforeStart || missing.name || missing.start || missing.end) return;
        onConfirm({ name: name.trim(), goal: goal.trim(), starts_on: start, ends_on: end });
    };

    const ids = { name: `${formId}-name`, goal: `${formId}-goal`, start: `${formId}-start`, end: `${formId}-end`, days: `${formId}-days` };

    return (
        <>
            <DialogHeader>
                <DialogTitle>Nova sprint</DialogTitle>
                <DialogDescription>Não há sprint aberta neste projeto. {card.key} entra nesta sprint.</DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate className="space-y-4">
                    <WFormFieldMolecule label="Nome" htmlFor={ids.name} required error={nameError}>
                        <Input value={name} maxLength={SPRINT_NAME_MAX} disabled={loading} onChange={(e) => setName(e.target.value)} />
                    </WFormFieldMolecule>

                    <WFormFieldMolecule
                        label="Objetivo"
                        htmlFor={ids.goal}
                        error={goalError}
                        hint={`Opcional, até ${SPRINT_GOAL_MAX} caracteres.`}
                    >
                        <Textarea value={goal} maxLength={SPRINT_GOAL_MAX} disabled={loading} onChange={(e) => setGoal(e.target.value)} />
                    </WFormFieldMolecule>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <WFormFieldMolecule label="Início" htmlFor={ids.start} required error={startError}>
                            <WDatePickerMolecule value={start || null} clearable={false} disabled={loading} onChange={changeStart} />
                        </WFormFieldMolecule>
                        <WFormFieldMolecule
                            label="Fim"
                            htmlFor={ids.end}
                            required
                            error={endError}
                            hint={`Proposta: ${project.sprint_days} dias úteis, contando o início.`}
                        >
                            <WDatePickerMolecule value={end || null} clearable={false} disabled={loading} onChange={changeEnd} />
                        </WFormFieldMolecule>
                    </div>

                    <WFormFieldMolecule
                        label="Dias úteis"
                        htmlFor={ids.days}
                        hint="De segunda a sexta, entre o início e o fim, inclusive. Feriado não conta."
                    >
                        <Input value={businessDays ?? '—'} readOnly className="w-24 bg-muted font-mono tabular-nums" />
                    </WFormFieldMolecule>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onCancel} disabled={loading}>
                    Cancelar
                </Button>
                <Button type="submit" form={formId} disabled={loading}>
                    {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Criar sprint
                </Button>
            </DialogFooter>
        </>
    );
}
