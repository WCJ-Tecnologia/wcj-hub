import { Head, router, useForm } from '@inertiajs/react';
import { ListChecks, Pencil, Plus, ToggleLeft, ToggleRight, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WShowInactiveToggleMolecule } from '@/components/molecules/w-show-inactive-toggle-molecule/w-show-inactive-toggle-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import {
    WReworkReasonFormDialogOrganism,
    type ReworkReasonFormData,
} from '@/components/organisms/w-rework-reason-form-dialog-organism/w-rework-reason-form-dialog-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useRowAction } from '@/hooks/use-row-action';
import { deactivation, situation } from '@/lib/situation';
import { cn } from '@/lib/utils';
import type { ReworkReasonRow } from '@/types/configuracao';

export interface WListsPageProps {
    reworkReasons: ReworkReasonRow[];
    filters: { show_inactive: boolean };
    can: { create: boolean };
}

type ConfirmKind = 'deactivate' | 'delete';

const EMPTY: ReworkReasonFormData = { label: '', requires_text: false };
const BASE = '/admin/rework-reasons';

/**
 * Administração → Listas (Inertia: `admin/lists/index`; F2 §02 a §04; G7, P40): os motivos de
 * retrabalho, lista geral só do Administrador. O "Outro" fica por último e não se desativa nem se
 * exclui. Os tipos de tarefa saíram daqui em 14/10: são do projeto (F3 §11 item 9).
 */
export function WListsPage({ reworkReasons, filters, can }: WListsPageProps) {
    const rowAction = useRowAction();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ReworkReasonRow | null>(null);
    const [confirm, setConfirm] = useState<{ kind: ConfirmKind; row: ReworkReasonRow } | null>(null);
    const form = useForm<ReworkReasonFormData>(EMPTY);

    const openCreate = () => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: ReworkReasonRow) => {
        form.setData({ label: row.label, requires_text: row.requires_text });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`${BASE}/${editing.id}`, options);
        else form.post(BASE, options);
    };

    const closeConfirm = { onFinish: () => setConfirm(null) };
    const CONFIRM: Record<
        ConfirmKind,
        { title: string; text: (row: ReworkReasonRow) => string; label: string; run: (row: ReworkReasonRow) => void }
    > = {
        deactivate: {
            title: 'Desativar motivo',
            text: (row) => `${row.label} sai da lista de escolha. As voltas para desenvolvimento que já o usam continuam com ele.`,
            label: 'Desativar',
            run: (row) => rowAction.post(`${BASE}/${row.id}/deactivate`, row, closeConfirm),
        },
        delete: {
            title: 'Excluir motivo',
            text: (row) => `Excluir de verdade ${row.label}. Não dá para desfazer. Só sai o motivo que nunca foi usado.`,
            label: 'Excluir',
            run: (row) => rowAction.del(`${BASE}/${row.id}`, row, closeConfirm),
        },
    };

    const newButton = can.create ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Novo motivo
        </Button>
    ) : undefined;

    const rowActions = (row: ReworkReasonRow, labeled = false) => (
        <WRowActionsMolecule
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            actions={[
                { label: 'Editar', ariaLabel: `Editar ${row.label}`, icon: Pencil, onClick: () => openEdit(row), hidden: !row.can.update },
                {
                    label: 'Desativar',
                    ariaLabel: `Desativar ${row.label}`,
                    icon: ToggleLeft,
                    onClick: () => setConfirm({ kind: 'deactivate', row }),
                    hidden: !row.can.deactivate,
                },
                {
                    label: 'Reativar',
                    ariaLabel: `Reativar ${row.label}`,
                    icon: ToggleRight,
                    onClick: () => rowAction.post(`${BASE}/${row.id}/reactivate`, row),
                    hidden: !row.can.reactivate,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${row.label}`,
                    icon: Trash2,
                    destructive: true,
                    onClick: () => setConfirm({ kind: 'delete', row }),
                    hidden: !row.can.delete,
                },
            ]}
        />
    );

    const pill = (row: ReworkReasonRow, size: 'default' | 'sm' = 'default') => (
        <WStatusPillAtom kind="user" status={row.active} label={row.active ? undefined : `Desativado ${deactivation(row)}`} size={size} />
    );
    const muted = (row: ReworkReasonRow) => cn(!row.active && 'text-muted-foreground');

    const columns: WColumn<ReworkReasonRow>[] = [
        {
            key: 'label',
            header: 'Nome',
            cell: (row) => (
                <span className={cn('font-medium', muted(row))}>
                    {row.label}
                    {row.fixed && (
                        <Badge variant="info" className="ml-2 align-middle">
                            Fixo
                        </Badge>
                    )}
                </span>
            ),
        },
        {
            key: 'requires_text',
            header: 'Pede texto',
            cell: (row) => <span className={muted(row)}>{row.requires_text ? 'Sim' : 'Não'}</span>,
        },
        { key: 'situation', header: 'Situação', cell: (row) => pill(row) },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    const current = confirm ? CONFIRM[confirm.kind] : null;

    return (
        <WAppLayoutTemplate eyebrow="Administração" title="Listas" subtitle="Listas gerais que todo projeto usa" actions={newButton}>
            <Head title="Listas" />

            <section aria-labelledby="rework-reasons-title" className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="rework-reasons-title" className="font-heading text-lg font-semibold">
                            Motivos de retrabalho
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Usados em toda volta para desenvolvimento, na reprovação da homologação e pela IA.
                        </p>
                    </div>
                    <WShowInactiveToggleMolecule
                        checked={filters.show_inactive}
                        onChange={(checked) =>
                            router.get('/admin/listas', checked ? { show_inactive: 1 } : {}, { preserveState: true, replace: true })
                        }
                    />
                </div>

                <WDataTableOrganism
                    caption="Motivos de retrabalho"
                    columns={columns}
                    rows={reworkReasons}
                    rowKey={(row) => row.id}
                    emptyState={
                        <WEmptyStateMolecule
                            icon={<ListChecks className="size-5" aria-hidden />}
                            title="Nenhum motivo ativo."
                            description="Crie os motivos que o time escolhe ao devolver um card para desenvolvimento."
                            action={newButton}
                        />
                    }
                    mobileCard={(row) => (
                        <>
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className={cn('truncate font-semibold', muted(row))}>{row.label}</p>
                                    <p className="text-xs text-muted-foreground">{row.requires_text ? 'Pede texto' : 'Não pede texto'}</p>
                                </div>
                                {pill(row, 'sm')}
                            </div>
                            {rowActions(row, true)}
                        </>
                    )}
                />
            </section>

            <WReworkReasonFormDialogOrganism
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                fixed={editing?.fixed}
                situation={editing ? situation(editing) : null}
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
        </WAppLayoutTemplate>
    );
}
