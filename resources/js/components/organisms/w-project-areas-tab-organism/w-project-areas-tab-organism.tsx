import { router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Tags, ToggleLeft, ToggleRight, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WShowInactiveToggleMolecule } from '@/components/molecules/w-show-inactive-toggle-molecule/w-show-inactive-toggle-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import {
    WProjectAreaFormDialogOrganism,
    type AreaFormData,
} from '@/components/organisms/w-project-area-form-dialog-organism/w-project-area-form-dialog-organism';
import { Button } from '@/components/shadcn/button';
import { useRowAction } from '@/hooks/use-row-action';
import { deactivation, situation } from '@/lib/situation';
import { cn } from '@/lib/utils';
import type { AreaRow } from '@/types/configuracao';

export interface WProjectAreasTabOrganismProps {
    projectId: number;
    areas: AreaRow[];
    showInactive: boolean;
    canManage: boolean;
}

type ConfirmKind = 'deactivate' | 'delete';

/**
 * Aba Áreas (F2 §02 a §04; P18, G2): a lista do projeto, que nasce vazia. Nome único no projeto;
 * desativada sai da lista de escolha do card e os cards que já a têm continuam com ela (P8);
 * excluir só a que nenhum card usa. Escreve em `/projetos/{id}/areas…`.
 */
export function WProjectAreasTabOrganism({ projectId, areas, showInactive, canManage }: WProjectAreasTabOrganismProps) {
    const rowAction = useRowAction();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<AreaRow | null>(null);
    const [confirm, setConfirm] = useState<{ kind: ConfirmKind; row: AreaRow } | null>(null);
    const form = useForm<AreaFormData>({ name: '' });
    const base = `/projetos/${projectId}/areas`;

    const openCreate = () => {
        form.setData({ name: '' });
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: AreaRow) => {
        form.setData({ name: row.name });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`${base}/${editing.id}`, options);
        else form.post(base, options);
    };

    const closeConfirm = { onFinish: () => setConfirm(null) };
    const CONFIRM: Record<ConfirmKind, { title: string; text: (row: AreaRow) => string; label: string; run: (row: AreaRow) => void }> = {
        deactivate: {
            title: 'Desativar área',
            text: (row) => `${row.name} sai da lista de escolha do card. Os cards que já têm a área continuam com ela.`,
            label: 'Desativar',
            run: (row) => rowAction.post(`${base}/${row.id}/deactivate`, row, closeConfirm),
        },
        delete: {
            title: 'Excluir área',
            text: (row) => `Excluir de verdade ${row.name}. Não dá para desfazer. Só sai a área que nenhum card usa.`,
            label: 'Excluir',
            run: (row) => rowAction.del(`${base}/${row.id}`, row, closeConfirm),
        },
    };

    const toggleInactive = (checked: boolean) =>
        router.get(
            `/projetos/${projectId}/configuracao`,
            { aba: 'areas', ...(checked ? { show_inactive: 1 } : {}) },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const newButton = canManage ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Nova área
        </Button>
    ) : undefined;

    const rowActions = (row: AreaRow, labeled = false) => (
        <WRowActionsMolecule
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            actions={[
                { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row), hidden: !canManage },
                {
                    label: 'Desativar',
                    ariaLabel: `Desativar ${row.name}`,
                    icon: ToggleLeft,
                    onClick: () => setConfirm({ kind: 'deactivate', row }),
                    hidden: !canManage || !row.active,
                },
                {
                    label: 'Reativar',
                    ariaLabel: `Reativar ${row.name}`,
                    icon: ToggleRight,
                    onClick: () => rowAction.post(`${base}/${row.id}/reactivate`, row),
                    hidden: !canManage || row.active,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${row.name}`,
                    icon: Trash2,
                    destructive: true,
                    onClick: () => setConfirm({ kind: 'delete', row }),
                    hidden: !canManage,
                },
            ]}
        />
    );

    const pill = (row: AreaRow, size: 'default' | 'sm' = 'default') => (
        <WStatusPillAtom kind="client" status={row.active} label={row.active ? 'Ativa' : `Desativada ${deactivation(row)}`} size={size} />
    );

    const columns: WColumn<AreaRow>[] = [
        {
            key: 'name',
            header: 'Nome',
            cell: (row) => <span className={cn('font-medium', !row.active && 'text-muted-foreground')}>{row.name}</span>,
        },
        { key: 'situation', header: 'Situação', cell: (row) => pill(row) },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    const current = confirm ? CONFIRM[confirm.kind] : null;

    return (
        <div data-slot="w-project-areas-tab-organism" className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <WShowInactiveToggleMolecule checked={showInactive} onChange={toggleInactive} />
                {newButton}
            </div>

            <WDataTableOrganism
                caption="Áreas do projeto"
                columns={columns}
                rows={areas}
                rowKey={(row) => row.id}
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Tags className="size-5" aria-hidden />}
                        title={showInactive ? 'Nenhuma área.' : 'Nenhuma área ativa.'}
                        description="Área do produto, para etiquetar os cards deste projeto. Cadastre se quiser usar."
                        action={newButton}
                    />
                }
                mobileCard={(row) => (
                    <>
                        <div className="flex items-start justify-between gap-3">
                            <p className={cn('truncate font-semibold', !row.active && 'text-muted-foreground')}>{row.name}</p>
                            {pill(row, 'sm')}
                        </div>
                        {rowActions(row, true)}
                    </>
                )}
            />

            <WProjectAreaFormDialogOrganism
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                situation={
                    editing
                        ? situation(editing)
                              .replace(/^Ativo$/, 'Ativa')
                              .replace(/^Desativado/, 'Desativada')
                        : null
                }
                onSubmit={submit}
            />

            <WConfirmDialogMolecule
                open={confirm !== null}
                onOpenChange={(next) => !next && setConfirm(null)}
                title={current?.title ?? ''}
                description={confirm && current ? current.text(confirm.row) : undefined}
                confirmLabel={current?.label}
                destructive
                loading={rowAction.busy}
                onConfirm={() => confirm && current?.run(confirm.row)}
            />
        </div>
    );
}
