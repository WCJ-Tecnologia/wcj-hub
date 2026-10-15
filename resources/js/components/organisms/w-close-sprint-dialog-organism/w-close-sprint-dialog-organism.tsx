import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { sprintFieldErrors, WSprintFieldsMolecule } from '@/components/molecules/w-sprint-fields-molecule/w-sprint-fields-molecule';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Label } from '@/components/shadcn/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import type { SprintClosing, SprintDestination, SprintFields, SprintRow } from '@/types/sprints';

export interface WCloseSprintDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
    /** A sprint aberta que vai fechar: nome e quantas prontas ficam nela. */
    sprint: Pick<SprintRow, 'id' | 'name' | 'tasks_done'>;
    /** As tarefas que não chegaram a `Done` e a próxima sprint proposta (P50). */
    closing: SprintClosing;
    /** Duração padrão do projeto em dias úteis (P46). */
    sprintDays: number;
}

interface CloseForm {
    destinations: Record<number, SprintDestination>;
    create_next: boolean;
    next: SprintFields;
}

/** "1 tarefa pronta fica" / "3 tarefas prontas ficam". */
const plural = (count: number, one: string, many: string) => `${count} ${count === 1 ? one : many}`;

/**
 * Diálogo *Encerrar sprint* (F5 §02, §04; P50, P51): as tarefas que não chegaram a `Done`, cada uma
 * com o destino (próxima sprint, o padrão, ou `Backlog`), e a próxima sprint já preenchida, com
 * início no 1º dia útil depois do fim. Sem tarefa indo para a próxima, dá para não criar. As
 * prontas ficam nesta. Grava em `POST /projetos/{id}/sprints/{sprint}/encerrar`.
 */
export function WCloseSprintDialogOrganism({ open, onOpenChange, ...props }: WCloseSprintDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-close-sprint-dialog-organism" className="sm:max-w-2xl">
                {open && <CloseForm {...props} onClose={() => onOpenChange(false)} />}
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WCloseSprintDialogOrganismProps, 'open' | 'onOpenChange'> & { onClose: () => void };

function CloseForm({ projectId, sprint, closing, sprintDays, onClose }: FormProps) {
    const form = useForm<CloseForm>({
        destinations: Object.fromEntries(closing.tasks.map((task) => [task.id, 'next' as SprintDestination])),
        create_next: true,
        next: closing.next,
    });
    const [tried, setTried] = useState(false);

    const toNext = closing.tasks.filter((task) => form.data.destinations[task.id] !== 'backlog').length;
    const toBacklog = closing.tasks.length - toNext;
    // Com tarefa indo para a próxima, a próxima é obrigatória (P50).
    const createNext = form.data.create_next || toNext > 0;
    const nextName = form.data.next.name.trim() || 'a próxima sprint';

    const nextErrors = Object.fromEntries(
        Object.entries(form.errors as Record<string, string>)
            .filter(([key]) => key.startsWith('next.'))
            .map(([key, message]) => [key.slice(5), message]),
    );

    const summary = [
        `${plural(sprint.tasks_done, 'tarefa pronta fica', 'tarefas prontas ficam')} nela`,
        toNext > 0 ? `${plural(toNext, 'vai', 'vão')} para a ${nextName}` : null,
        toBacklog > 0 ? `${plural(toBacklog, 'volta', 'voltam')} ao Backlog` : null,
    ]
        .filter(Boolean)
        .join('; ');

    const setDestination = (id: number, value: SprintDestination) =>
        form.setData('destinations', { ...form.data.destinations, [id]: value });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (form.processing || (createNext && Object.keys(sprintFieldErrors(form.data.next)).length > 0)) return;
        form.transform((data) => ({
            destinations: data.destinations,
            create_next: createNext,
            ...(createNext ? { next: data.next } : {}),
        }));
        form.post(`/projetos/${projectId}/sprints/${sprint.id}/encerrar`, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>Encerrar a {sprint.name}</DialogTitle>
                <DialogDescription>
                    Escolha para onde vai cada tarefa que não chegou a Done. Encerrar não espera nada: pode haver tarefa em andamento.
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id="close-sprint-form" onSubmit={submit} noValidate className="space-y-6">
                    <section aria-labelledby="close-sprint-tasks" className="space-y-2">
                        <h3 id="close-sprint-tasks" className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                            Tarefas que não chegaram a Done ({closing.tasks.length})
                        </h3>
                        {closing.tasks.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Todas as tarefas da sprint chegaram a Done.</p>
                        ) : (
                            <ul className="divide-y divide-border rounded-md border border-border">
                                {closing.tasks.map((task) => (
                                    <li
                                        key={task.id}
                                        className="flex flex-col gap-2 px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0">
                                            <p className="flex items-center gap-2 text-sm">
                                                <WTaskKeyAtom taskKey={task.key} size="sm" />
                                                <span className="truncate">{task.title}</span>
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {task.stage.name} · {task.developer ?? 'Sem dev'}
                                            </p>
                                        </div>
                                        <Select
                                            value={form.data.destinations[task.id] ?? 'next'}
                                            onValueChange={(value) => setDestination(task.id, value as SprintDestination)}
                                            disabled={form.processing}
                                        >
                                            <SelectTrigger className="w-full sm:w-48" aria-label={`Destino de ${task.key}`}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="next">Próxima sprint</SelectItem>
                                                <SelectItem value="backlog">Backlog</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section aria-labelledby="close-sprint-next" className="space-y-3">
                        <h3 id="close-sprint-next" className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                            Próxima sprint
                        </h3>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="close-sprint-create-next"
                                checked={createNext}
                                disabled={toNext > 0 || form.processing}
                                onCheckedChange={(checked) => form.setData('create_next', checked === true)}
                            />
                            <Label htmlFor="close-sprint-create-next">Criar a próxima sprint</Label>
                        </div>
                        {toNext > 0 && <p className="text-xs text-muted-foreground">Obrigatória: há tarefa indo para a próxima sprint.</p>}
                        {createNext && (
                            <WSprintFieldsMolecule
                                value={form.data.next}
                                onChange={(value) => form.setData('next', value)}
                                sprintDays={sprintDays}
                                errors={nextErrors}
                                tried={tried}
                                disabled={form.processing}
                            />
                        )}
                        {form.errors.next && <p className="text-sm text-destructive">{form.errors.next}</p>}
                    </section>

                    <p role="status" className="rounded-md border border-border bg-muted/40 px-3 py-2 text-sm">
                        Encerrar a {sprint.name}? {summary}.
                    </p>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onClose} disabled={form.processing}>
                    Cancelar
                </Button>
                <Button type="submit" form="close-sprint-form" disabled={form.processing}>
                    {form.processing && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Encerrar sprint
                </Button>
            </DialogFooter>
        </>
    );
}
