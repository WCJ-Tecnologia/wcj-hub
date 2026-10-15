import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { WTaskPickerMolecule } from '@/components/molecules/w-task-picker-molecule/w-task-picker-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import type { TimeEntryFormData, TimeEntryRow, TimeEntryTaskOption } from '@/types/horas';
import type { TaskRef } from '@/types/quadro';

export interface WTimeEntryFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Na correção, o lançamento (a tarefa fica fixa); no lançamento novo, null. */
    entry: TimeEntryRow | null;
    /** As tarefas que posso escolher: dos projetos que vejo, sem arquivadas, as minhas primeiro. */
    tasks: TimeEntryTaskOption[];
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo: a data padrão. */
    today: string;
}

const HOURS_TEXT = 'Informe horas maiores que 0 e até 24';

/** "1,5" ou "1.5" → 1.5; vazio → NaN. */
const toNumber = (text: string) => (text.trim() === '' ? Number.NaN : Number(text.trim().replace(',', '.')));

/**
 * *Lançar horas* e *Corrigir lançamento* (F5 §02 "Lançar horas", §04; P30): tarefa (busca por chave
 * ou título; fixa na correção), data (até hoje) e horas decimais, maiores que 0 e até 24. A pessoa
 * é sempre quem está logado; não há descrição (quem quiser explicar comenta no card). Grava em
 * `POST /projetos/{id}/tarefas/{task}/horas` ou `PUT /horas/{lançamento}`.
 */
export function WTimeEntryFormDialogOrganism({ open, onOpenChange, ...props }: WTimeEntryFormDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-time-entry-form-dialog-organism">
                {open && <EntryForm {...props} onClose={() => onOpenChange(false)} />}
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WTimeEntryFormDialogOrganismProps, 'open' | 'onOpenChange'> & { onClose: () => void };

function EntryForm({ entry, tasks, today, onClose }: FormProps) {
    const form = useForm<TimeEntryFormData>({
        worked_on: entry?.worked_on ?? today,
        hours: entry ? String(entry.hours).replace('.', ',') : '',
    });
    const [task, setTask] = useState<TaskRef | null>(entry?.task ?? null);
    const [tried, setTried] = useState(false);
    const value = toNumber(form.data.hours);
    const hoursInvalid = !Number.isFinite(value) || value <= 0 || value > 24;
    const future = form.data.worked_on > today;

    const taskError = tried && !task ? 'Escolha a tarefa.' : undefined;
    const dateError =
        form.errors.worked_on ?? (future ? 'A data não pode ser futura' : tried && !form.data.worked_on ? 'Informe a data.' : undefined);
    const hoursError = form.errors.hours ?? (tried && hoursInvalid ? HOURS_TEXT : undefined);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (form.processing || !task || !form.data.worked_on || future || hoursInvalid) return;
        form.transform((data) => ({ ...data, hours: String(toNumber(data.hours)) }));
        const options = { preserveScroll: true, onSuccess: onClose };
        if (entry) {
            form.put(`/horas/${entry.id}`, options);
            return;
        }
        const projectId = tasks.find((option) => option.id === task.id)?.project_id;
        if (projectId) form.post(`/projetos/${projectId}/tarefas/${task.id}/horas`, options);
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>{entry ? 'Corrigir lançamento' : 'Lançar horas'}</DialogTitle>
                <DialogDescription>
                    {entry
                        ? 'Corrija a data ou as horas. O antes fica no histórico.'
                        : 'As horas do dia em que você trabalhou na tarefa. Pode lançar mais de uma vez no mesmo dia.'}
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id="time-entry-form" onSubmit={submit} noValidate className="space-y-4">
                    {entry ? (
                        <div className="space-y-1.5">
                            <p className="text-sm font-medium">Tarefa</p>
                            <p className="flex items-center gap-2 text-sm">
                                <WTaskKeyAtom taskKey={entry.task.key} />
                                <span>{entry.task.title}</span>
                            </p>
                        </div>
                    ) : (
                        <WFormFieldMolecule label="Tarefa" htmlFor="time-entry-task" required error={taskError}>
                            <WTaskPickerMolecule
                                id="time-entry-task"
                                cards={tasks}
                                mode="origin"
                                value={task}
                                onChange={setTask}
                                label="Buscar tarefa por chave ou título"
                                disabled={form.processing}
                            />
                        </WFormFieldMolecule>
                    )}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <WFormFieldMolecule label="Data" htmlFor="time-entry-date" required error={dateError}>
                            <WDatePickerMolecule
                                value={form.data.worked_on || null}
                                clearable={false}
                                disabled={form.processing}
                                onChange={(date) => form.setData('worked_on', date ?? '')}
                            />
                        </WFormFieldMolecule>
                        <WFormFieldMolecule
                            label="Horas"
                            htmlFor="time-entry-hours"
                            required
                            error={hoursError}
                            hint="Com decimais, ex.: 1,5."
                        >
                            <Input
                                inputMode="decimal"
                                value={form.data.hours}
                                disabled={form.processing}
                                onChange={(event) => form.setData('hours', event.target.value)}
                                className="tabular-nums"
                            />
                        </WFormFieldMolecule>
                    </div>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onClose} disabled={form.processing}>
                    Cancelar
                </Button>
                <Button type="submit" form="time-entry-form" disabled={form.processing}>
                    {form.processing && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    {entry ? 'Salvar correção' : 'Lançar'}
                </Button>
            </DialogFooter>
        </>
    );
}
