import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

import { sprintFieldErrors, WSprintFieldsMolecule } from '@/components/molecules/w-sprint-fields-molecule/w-sprint-fields-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import type { SprintFields, SprintRow } from '@/types/sprints';

export interface WSprintFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
    /** Duração padrão do projeto em dias úteis (P46). */
    sprintDays: number;
    /** Na edição, a sprint; na criação, null. */
    sprint: SprintRow | null;
    /** Na criação, a sprint proposta pelo servidor ("Sprint N", hoje, fim pela duração padrão). */
    proposal: SprintFields | null;
}

/**
 * *Nova sprint* e *Editar sprint* da tela de sprints (F5 §02, §04): nome, objetivo, início e fim,
 * com os dias úteis. Criar só aparece sem sprint aberta (P45); editar vale para aberta e encerrada.
 * Grava em `POST /projetos/{id}/sprints` e `PUT /projetos/{id}/sprints/{sprint}`.
 */
export function WSprintFormDialogOrganism({ open, onOpenChange, ...props }: WSprintFormDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-sprint-form-dialog-organism">
                {/* Desmontado ao fechar: a próxima abertura volta à proposta ou à sprint. */}
                {open && <SprintForm {...props} onClose={() => onOpenChange(false)} />}
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WSprintFormDialogOrganismProps, 'open' | 'onOpenChange'> & { onClose: () => void };

function SprintForm({ projectId, sprintDays, sprint, proposal, onClose }: FormProps) {
    const initial: SprintFields = sprint
        ? { name: sprint.name, goal: sprint.goal ?? '', starts_on: sprint.starts_on, ends_on: sprint.ends_on }
        : (proposal ?? { name: '', goal: '', starts_on: '', ends_on: '' });
    const form = useForm<SprintFields>(initial);
    const [tried, setTried] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (form.processing || Object.keys(sprintFieldErrors(form.data)).length > 0) return;
        const options = { preserveScroll: true, onSuccess: onClose };
        if (sprint) form.put(`/projetos/${projectId}/sprints/${sprint.id}`, options);
        else form.post(`/projetos/${projectId}/sprints`, options);
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>{sprint ? `Editar ${sprint.name}` : 'Nova sprint'}</DialogTitle>
                <DialogDescription>
                    {sprint
                        ? 'Mudar as datas muda os dias úteis e a capacidade. As tarefas ficam onde estão.'
                        : 'É a sprint aberta do projeto: os cards saem do Backlog para ela.'}
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id="sprint-form" onSubmit={submit} noValidate>
                    <WSprintFieldsMolecule
                        value={form.data}
                        onChange={(value) => form.setData(value)}
                        sprintDays={sprintDays}
                        followStart={sprint === null}
                        errors={form.errors}
                        tried={tried}
                        disabled={form.processing}
                    />
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onClose} disabled={form.processing}>
                    Cancelar
                </Button>
                <Button type="submit" form="sprint-form" disabled={form.processing}>
                    {form.processing && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    {sprint ? 'Salvar alterações' : 'Criar sprint'}
                </Button>
            </DialogFooter>
        </>
    );
}
