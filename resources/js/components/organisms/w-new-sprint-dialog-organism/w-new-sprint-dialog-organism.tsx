import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { sprintFieldErrors, WSprintFieldsMolecule } from '@/components/molecules/w-sprint-fields-molecule/w-sprint-fields-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { addBusinessDays } from '@/lib/dias-uteis';
import type { BoardCard, BoardProject } from '@/types/quadro';
import type { SprintFields } from '@/types/sprints';

// Os limites moram nos campos da sprint; seguem exportados daqui para quem já importava do kit.
export { SPRINT_GOAL_MAX, SPRINT_NAME_MAX } from '@/components/molecules/w-sprint-fields-molecule/w-sprint-fields-molecule';

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
    const [value, setValue] = useState<SprintFields>(() => ({
        name: `Sprint ${project.sprints_count + 1}`,
        goal: '',
        starts_on: today,
        ends_on: addBusinessDays(today, project.sprint_days),
    }));
    const [tried, setTried] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (loading || Object.keys(sprintFieldErrors(value)).length > 0) return;
        onConfirm({ name: value.name.trim(), goal: value.goal.trim(), starts_on: value.starts_on, ends_on: value.ends_on });
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>Nova sprint</DialogTitle>
                <DialogDescription>Não há sprint aberta neste projeto. {card.key} entra nesta sprint.</DialogDescription>
            </DialogHeader>
            <DialogBody>
                {/* Os campos são os da WSprintFieldsMolecule (F5 §02): o fim acompanha o início até a pessoa mexer nele. */}
                <form id={formId} onSubmit={submit} noValidate>
                    <WSprintFieldsMolecule
                        value={value}
                        onChange={setValue}
                        sprintDays={project.sprint_days}
                        errors={errors}
                        tried={tried}
                        disabled={loading}
                    />
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
