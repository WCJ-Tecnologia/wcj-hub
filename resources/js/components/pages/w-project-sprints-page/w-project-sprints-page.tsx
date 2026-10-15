import { Head, Link } from '@inertiajs/react';
import { KanbanSquare, Plus, Settings } from 'lucide-react';
import { useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WCapacityPanelOrganism } from '@/components/organisms/w-capacity-panel-organism/w-capacity-panel-organism';
import { WCloseSprintDialogOrganism } from '@/components/organisms/w-close-sprint-dialog-organism/w-close-sprint-dialog-organism';
import { WSprintFormDialogOrganism } from '@/components/organisms/w-sprint-form-dialog-organism/w-sprint-form-dialog-organism';
import { WSprintListOrganism } from '@/components/organisms/w-sprint-list-organism/w-sprint-list-organism';
import { WSprintTasksOrganism } from '@/components/organisms/w-sprint-tasks-organism/w-sprint-tasks-organism';
import { Button } from '@/components/shadcn/button';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useRowAction } from '@/hooks/use-row-action';
import type { SelectedSprint, SprintClosing, SprintFields, SprintFilters, SprintProject, SprintRow } from '@/types/sprints';

export interface WProjectSprintsPageProps {
    project: SprintProject;
    sprints: SprintRow[];
    /** A sprint mostrada embaixo: a do `?sprint=`, senão a aberta, senão a mais recente. */
    selected: SelectedSprint | null;
    /** Só para quem pode encerrar a sprint aberta. */
    closing: SprintClosing | null;
    /** *Nova sprint*: só sem sprint aberta e para quem pode criar (P45). */
    proposal: SprintFields | null;
    can: { manage_tasks: boolean };
    filters: SprintFilters;
    today: string;
}

/**
 * Sprints do projeto (Inertia: `projects/sprints`, `/projetos/{id}/sprints`; F5 §01 a §05): a lista
 * com a aberta em destaque, as tarefas da sprint escolhida e, para quem gere, o painel
 * *Capacidade*. Criar, editar, encerrar, reabrir e excluir pelos diálogos; o servidor decide
 * as regras (uma aberta por projeto, reabrir sem outra aberta, excluir só a vazia).
 */
export function WProjectSprintsPage({ project, sprints, selected, closing, proposal, can, filters }: WProjectSprintsPageProps) {
    const rowAction = useRowAction();
    const [form, setForm] = useState<{ open: boolean; sprint: SprintRow | null }>({ open: false, sprint: null });
    const [closingOpen, setClosingOpen] = useState(false);
    const [deleting, setDeleting] = useState<SprintRow | null>(null);
    const base = `/projetos/${project.id}/sprints`;
    const openSprint = sprints.find((row) => row.open) ?? selected ?? null;

    const newButton = proposal ? (
        <Button onClick={() => setForm({ open: true, sprint: null })}>
            <Plus className="size-4" aria-hidden /> Nova sprint
        </Button>
    ) : undefined;

    return (
        <WAppLayoutTemplate
            eyebrow={`Projetos · ${project.key_prefix}`}
            title={`Sprints · ${project.name}`}
            subtitle={`${project.client} · sprint padrão de ${project.sprint_days} dias úteis`}
            width="wide"
            actions={
                <>
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/quadro`}>
                            <KanbanSquare className="size-4" aria-hidden /> Abrir o quadro
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/configuracao`}>
                            <Settings className="size-4" aria-hidden /> Configuração
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title={`Sprints · ${project.name}`} />

            <div className="space-y-8">
                <WSprintListOrganism
                    projectId={project.id}
                    sprints={sprints}
                    selectedId={selected?.id ?? null}
                    filters={filters}
                    isBusy={rowAction.isBusy}
                    isLocked={rowAction.isLocked}
                    onEdit={(sprint) => setForm({ open: true, sprint })}
                    onClose={() => setClosingOpen(true)}
                    onReopen={(sprint) => rowAction.post(`${base}/${sprint.id}/reabrir`, sprint)}
                    onDelete={setDeleting}
                    newButton={newButton}
                />

                {selected && (
                    <div className="space-y-8">
                        <WSprintTasksOrganism projectId={project.id} sprint={selected} filters={filters} canManage={can.manage_tasks} />
                        {selected.capacity && <WCapacityPanelOrganism capacity={selected.capacity} />}
                    </div>
                )}
            </div>

            <WSprintFormDialogOrganism
                open={form.open}
                onOpenChange={(open) => setForm((current) => ({ ...current, open }))}
                projectId={project.id}
                sprintDays={project.sprint_days}
                sprint={form.sprint}
                proposal={proposal}
            />

            {closing && openSprint && (
                <WCloseSprintDialogOrganism
                    open={closingOpen}
                    onOpenChange={setClosingOpen}
                    projectId={project.id}
                    sprint={sprints.find((row) => row.id === closing.sprint_id) ?? openSprint}
                    closing={closing}
                    sprintDays={project.sprint_days}
                />
            )}

            <WConfirmDialogMolecule
                open={deleting !== null}
                onOpenChange={(next) => !next && setDeleting(null)}
                title="Excluir sprint"
                description={deleting ? `Excluir a ${deleting.name}? Não dá para desfazer.` : undefined}
                confirmLabel="Excluir"
                destructive
                loading={rowAction.busy}
                onConfirm={() => deleting && rowAction.del(`${base}/${deleting.id}`, deleting, { onFinish: () => setDeleting(null) })}
            />
        </WAppLayoutTemplate>
    );
}
