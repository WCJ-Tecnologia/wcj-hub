import { closestCenter, DndContext, KeyboardSensor, PointerSensor, useSensor, useSensors, type DragEndEvent } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router, useForm } from '@inertiajs/react';
import { GripVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import {
    WBoardStageFormDialogOrganism,
    type StageFormData,
} from '@/components/organisms/w-board-stage-form-dialog-organism/w-board-stage-form-dialog-organism';
import { WRemoveBoardStageDialogOrganism } from '@/components/organisms/w-remove-board-stage-dialog-organism/w-remove-board-stage-dialog-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Card } from '@/components/shadcn/card';
import { cn } from '@/lib/utils';
import type { StageRoleOption, StageRow } from '@/types/configuracao';

export interface WBoardStagesTabOrganismProps {
    projectId: number;
    /** Etapas ativas, na ordem do quadro. */
    stages: StageRow[];
    /** Os papéis que a etapa nova pode ter; vazio para quem não gere. */
    roles: StageRoleOption[];
    canManage: boolean;
}

/** Papel Desenvolvimento é o padrão da etapa nova (F2 §02). */
const DEVELOPMENT_ROLE = 3;

const GRID =
    'grid grid-cols-[2rem_2.5rem_minmax(0,1fr)_auto] items-center gap-3 px-4 py-3 md:grid-cols-[2rem_3rem_minmax(0,1fr)_10rem_6rem_6rem]';

interface SortableStageRowProps {
    stage: StageRow;
    canManage: boolean;
    busy: boolean;
    onEdit: (stage: StageRow) => void;
    onRemove: (stage: StageRow) => void;
}

/** Uma linha da lista; só a alça arrasta (ponteiro ou teclado: Espaço pega, setas movem, Espaço solta). */
function SortableStageRow({ stage, canManage, busy, onEdit, onRemove }: SortableStageRowProps) {
    const { attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition, isDragging } = useSortable({
        id: stage.id,
        disabled: !canManage || busy,
    });

    return (
        <li
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            data-stage={stage.id}
            className={cn(GRID, 'bg-card', isDragging && 'relative z-10 shadow-md')}
        >
            {canManage ? (
                <button
                    ref={setActivatorNodeRef}
                    type="button"
                    aria-label={`Arrastar ${stage.name}`}
                    disabled={busy}
                    className="grid size-8 cursor-grab touch-none place-items-center rounded-md text-muted-foreground outline-none hover:bg-accent hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-default disabled:opacity-50"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="size-4" aria-hidden />
                </button>
            ) : (
                <span aria-hidden />
            )}
            <span className="font-mono text-xs text-muted-foreground tabular-nums">{stage.position}</span>
            <div className="min-w-0">
                <p className="truncate font-medium">{stage.name}</p>
                <p className="text-xs text-muted-foreground md:hidden">
                    {stage.role_label} · {stage.cards} cards
                </p>
            </div>
            <Badge variant="outline" className="hidden md:inline-flex">
                {stage.role_label}
            </Badge>
            <span className="hidden text-sm tabular-nums md:block">{stage.cards}</span>
            <WRowActionsMolecule
                busy={busy}
                actions={[
                    { label: 'Editar', ariaLabel: `Editar ${stage.name}`, icon: Pencil, onClick: () => onEdit(stage), hidden: !canManage },
                    {
                        label: 'Remover',
                        ariaLabel: `Remover ${stage.name}`,
                        icon: Trash2,
                        destructive: true,
                        onClick: () => onRemove(stage),
                        hidden: !canManage || !stage.removable,
                    },
                ]}
            />
        </li>
    );
}

/**
 * Aba Etapas (F2 §02 a §04; P8, P10–P12): a lista nunca fica vazia (Fila, Sprint e Suporte
 * existem sempre). Quem gere renomeia, cria, reordena arrastando (inclusive Fila, Sprint e
 * Suporte) e remove; a lista mostra quantos cards há em cada etapa hoje. A ordem vai inteira em
 * `PUT /projetos/{id}/etapas/ordem`; enquanto o servidor responde, a lista já mostra a ordem nova.
 */
export function WBoardStagesTabOrganism({ projectId, stages, roles, canManage }: WBoardStagesTabOrganismProps) {
    const [pending, setPending] = useState<number[] | null>(null);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<StageRow | null>(null);
    const [removing, setRemoving] = useState<StageRow | null>(null);
    const form = useForm<StageFormData>({ name: '', stage_role_id: DEVELOPMENT_ROLE });
    const removeForm = useForm<{ destination_id: number | '' }>({ destination_id: '' });
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const byId = new Map(stages.map((stage) => [stage.id, stage]));
    const rows = pending ? pending.map((id, index) => ({ ...(byId.get(id) as StageRow), position: index + 1 })) : stages;
    const busy = pending !== null || removeForm.processing;

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) return;
        const ids = rows.map((stage) => stage.id);
        const next = arrayMove(ids, ids.indexOf(Number(active.id)), ids.indexOf(Number(over.id)));
        setPending(next);
        router.put(
            `/projetos/${projectId}/etapas/ordem`,
            { ids: next },
            { preserveScroll: true, preserveState: true, onFinish: () => setPending(null) },
        );
    };

    const openCreate = () => {
        form.setData({ name: '', stage_role_id: DEVELOPMENT_ROLE });
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (stage: StageRow) => {
        form.setData({ name: stage.name, stage_role_id: stage.role_id });
        form.clearErrors();
        setEditing(stage);
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) {
            // Papel congelado (ou de papel único): só o nome vai; o servidor recusaria a troca.
            form.transform((data) => (editing.role_locked ? { name: data.name } : data));
            form.put(`/projetos/${projectId}/etapas/${editing.id}`, options);
        } else {
            form.transform((data) => data);
            form.post(`/projetos/${projectId}/etapas`, options);
        }
    };

    const remove = (destinationId: number | null) => {
        if (!removing) return;
        removeForm.transform(() => (destinationId === null ? {} : { destination_id: destinationId }));
        removeForm.delete(`/projetos/${projectId}/etapas/${removing.id}`, { preserveScroll: true, onSuccess: () => setRemoving(null) });
    };

    return (
        <div data-slot="w-board-stages-tab-organism" className="space-y-4">
            {canManage && (
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Arraste pela alça para mudar a ordem do quadro. O papel é o que os indicadores leem.
                    </p>
                    <Button onClick={openCreate}>
                        <Plus className="size-4" aria-hidden /> Nova etapa
                    </Button>
                </div>
            )}

            <Card className="gap-0 overflow-hidden p-0">
                <div
                    className={cn(
                        GRID,
                        'hidden border-b border-border bg-muted/40 font-mono text-xs tracking-wide text-muted-foreground uppercase md:grid',
                    )}
                    aria-hidden
                >
                    <span />
                    <span>Pos.</span>
                    <span>Nome</span>
                    <span>Papel</span>
                    <span>Cards</span>
                    <span className="sr-only">Ações</span>
                </div>
                <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
                    <SortableContext items={rows.map((stage) => stage.id)} strategy={verticalListSortingStrategy}>
                        <ul className="divide-y divide-border" aria-label="Etapas do quadro, na ordem" aria-busy={busy || undefined}>
                            {rows.map((stage) => (
                                <SortableStageRow
                                    key={stage.id}
                                    stage={stage}
                                    canManage={canManage}
                                    busy={busy}
                                    onEdit={openEdit}
                                    onRemove={setRemoving}
                                />
                            ))}
                        </ul>
                    </SortableContext>
                </DndContext>
            </Card>

            <WBoardStageFormDialogOrganism
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing}
                roles={roles}
                onSubmit={submit}
            />

            <WRemoveBoardStageDialogOrganism
                open={removing !== null}
                onOpenChange={(next) => !next && !removeForm.processing && setRemoving(null)}
                stage={removing}
                destinations={stages.filter((stage) => stage.id !== removing?.id)}
                loading={removeForm.processing}
                error={removeForm.errors.destination_id}
                onConfirm={remove}
            />
        </div>
    );
}
