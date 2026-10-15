import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { sprintFieldErrors, WSprintFieldsMolecule } from '@/components/molecules/w-sprint-fields-molecule/w-sprint-fields-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { batchSummary, batchSummaryText, proposedSprint } from '@/lib/quadro';
import type { BoardCard, BoardProject, SprintBatchPayload } from '@/types/quadro';
import type { SprintFields } from '@/types/sprints';

export interface WStartSprintDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** `open_sprint` decide o caso; `sprints_count` e `sprint_days`, a sprint proposta (P46). */
    project: BoardProject;
    /** Os cards marcados no Backlog. */
    cards: BoardCard[];
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo: o início proposto. */
    today: string;
    /** Gravando: desabilita, mostra o spinner e impede fechar. */
    loading?: boolean;
    /**
     * Erros do servidor: `task_ids` (o lote recusado, com as chaves), `new_sprint.*` (os campos) e `name`
     * (nome repetido no projeto, o mesmo erro do *Nova sprint*).
     */
    errors?: Record<string, string>;
    onConfirm: (payload: SprintBatchPayload) => void;
}

/**
 * Confirmação do *Montar sprint* do quadro (D-GER-35; F5 §01, §02, §04). Sem sprint aberta é *Iniciar
 * sprint*: os campos da `WSprintFieldsMolecule` já preenchidos com a proposta ("Sprint N", hoje, fim pela
 * duração padrão em dias úteis, objetivo opcional) e confirmar cria a sprint com as tarefas, numa ação só
 * (R5). Com sprint aberta, só confirma: as tarefas saem do Backlog e vão para o To do da sprint aberta (P45).
 * Nos dois casos lista as marcadas e o total de horas, só como referência (P49).
 */
export function WStartSprintDialogOrganism({ open, onOpenChange, loading = false, ...props }: WStartSprintDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-start-sprint-dialog-organism" className="sm:max-w-xl">
                {/* Desmontado ao fechar: a próxima abertura volta à proposta. */}
                {open && <StartSprintForm {...props} loading={loading} onCancel={() => onOpenChange(false)} />}
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WStartSprintDialogOrganismProps, 'open' | 'onOpenChange' | 'loading'> & { loading: boolean; onCancel: () => void };

function StartSprintForm({ project, cards, today, loading, errors = {}, onConfirm, onCancel }: FormProps) {
    const formId = useId();
    const openSprint = project.open_sprint;
    const [value, setValue] = useState<SprintFields>(() => proposedSprint(project, today));
    const [tried, setTried] = useState(false);
    const summary = batchSummary(cards);

    // `new_sprint.name` da validação e `name` do nome repetido caem no mesmo campo.
    const fieldErrors: Partial<Record<keyof SprintFields, string>> = {
        ...Object.fromEntries(
            Object.entries(errors)
                .filter(([key]) => key.startsWith('new_sprint.'))
                .map(([key, message]) => [key.slice('new_sprint.'.length), message]),
        ),
        ...(errors.name ? { name: errors.name } : {}),
    };
    const batchError = Object.entries(errors).find(([key]) => key === 'task_ids' || key.startsWith('task_ids.'))?.[1];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (loading || cards.length === 0) return;
        if (openSprint) {
            onConfirm({ task_ids: cards.map((card) => card.id) });
            return;
        }
        if (Object.keys(sprintFieldErrors(value)).length > 0) return;
        onConfirm({
            task_ids: cards.map((card) => card.id),
            new_sprint: { name: value.name.trim(), goal: value.goal.trim(), starts_on: value.starts_on, ends_on: value.ends_on },
        });
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>{openSprint ? `Pôr na ${openSprint.name}` : 'Iniciar sprint'}</DialogTitle>
                <DialogDescription>
                    {openSprint
                        ? `${cards.length === 1 ? 'A tarefa sai' : 'As tarefas saem'} do Backlog e ${cards.length === 1 ? 'vai' : 'vão'} para o To do da ${openSprint.name}.`
                        : 'Não há sprint aberta neste projeto. A sprint nasce com as tarefas marcadas, que vão para o To do.'}
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate className="space-y-5">
                    {batchError && (
                        <p
                            role="alert"
                            className="rounded-md border border-destructive/40 bg-destructive/5 px-3 py-2 text-sm text-destructive"
                        >
                            {batchError}
                        </p>
                    )}

                    {!openSprint && (
                        <WSprintFieldsMolecule
                            value={value}
                            onChange={setValue}
                            sprintDays={project.sprint_days}
                            errors={fieldErrors}
                            tried={tried}
                            disabled={loading}
                        />
                    )}

                    <section aria-labelledby={`${formId}-tasks`} className="space-y-2">
                        <h3 id={`${formId}-tasks`} className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                            {batchSummaryText(summary)}
                        </h3>
                        {cards.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Nenhuma tarefa marcada: volte ao quadro e marque no Backlog.</p>
                        ) : (
                            <ul
                                aria-label="Tarefas marcadas"
                                className="max-h-48 divide-y divide-border overflow-y-auto rounded-md border border-border"
                            >
                                {cards.map((card) => (
                                    <li key={card.id} className="flex items-baseline gap-2 px-3 py-1.5 text-sm">
                                        <WTaskKeyAtom taskKey={card.key} size="sm" />
                                        <span className="min-w-0 flex-1 truncate">{card.title}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onCancel} disabled={loading}>
                    Cancelar
                </Button>
                <Button type="submit" form={formId} disabled={loading || cards.length === 0}>
                    {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    {openSprint ? `Pôr na ${openSprint.name}` : 'Iniciar sprint'}
                </Button>
            </DialogFooter>
        </>
    );
}
