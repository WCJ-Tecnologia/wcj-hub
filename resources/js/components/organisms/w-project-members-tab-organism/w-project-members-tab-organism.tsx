import { useForm } from '@inertiajs/react';
import { UserMinus, UserPlus, Users } from 'lucide-react';
import { useState } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WAllocatePeopleDialogOrganism } from '@/components/organisms/w-allocate-people-dialog-organism/w-allocate-people-dialog-organism';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Button } from '@/components/shadcn/button';
import { useRowAction } from '@/hooks/use-row-action';
import { deactivation } from '@/lib/situation';
import { cn } from '@/lib/utils';
import type { MemberRow, PersonOption } from '@/types/configuracao';

export interface WProjectMembersTabOrganismProps {
    projectId: number;
    members: MemberRow[];
    /** Quem ainda pode entrar; vazio quando não há ninguém ou quando a pessoa não gere. */
    people: PersonOption[];
    canManage: boolean;
}

/** "6,5 h" a partir do decimal do servidor; sem carga, um traço. */
const hours = (value: string | null) => (value === null ? '—' : `${Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 2 })} h`);

/**
 * Aba Pessoas (F2 §02 a §04; P5, N11): quem está no projeto, com papel, carga diária e situação;
 * "Alocar pessoas" e "Tirar do projeto" para quem gere. Usuário desativado continua na lista, em
 * cinza. A escrita vai para `POST`/`DELETE /projetos/{id}/pessoas`.
 */
export function WProjectMembersTabOrganism({ projectId, members, people, canManage }: WProjectMembersTabOrganismProps) {
    const rowAction = useRowAction();
    const [allocateOpen, setAllocateOpen] = useState(false);
    const [removing, setRemoving] = useState<MemberRow | null>(null);
    const allocateForm = useForm<{ user_ids: number[] }>({ user_ids: [] });

    const allocate = (userIds: number[]) => {
        allocateForm.transform(() => ({ user_ids: userIds }));
        allocateForm.post(`/projetos/${projectId}/pessoas`, { preserveScroll: true, onSuccess: () => setAllocateOpen(false) });
    };
    // O erro pode vir na lista ("user_ids") ou num item ("user_ids.0"): o primeiro que houver.
    const allocateError = Object.entries(allocateForm.errors as Record<string, string>).find(([key]) => key.startsWith('user_ids'))?.[1];

    const remove = (row: MemberRow) =>
        rowAction.del(`/projetos/${projectId}/pessoas/${row.user_id}`, row, { onFinish: () => setRemoving(null) });

    const muted = (row: MemberRow) => cn(!row.active && 'text-muted-foreground');
    const newButton = canManage ? (
        <Button
            onClick={() => {
                allocateForm.clearErrors();
                setAllocateOpen(true);
            }}
        >
            <UserPlus className="size-4" aria-hidden /> Alocar pessoas
        </Button>
    ) : undefined;

    const rowActions = (row: MemberRow, labeled = false) => (
        <WRowActionsMolecule
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            actions={[
                {
                    label: 'Tirar do projeto',
                    ariaLabel: `Tirar ${row.name} do projeto`,
                    icon: UserMinus,
                    destructive: true,
                    onClick: () => setRemoving(row),
                    hidden: !canManage,
                },
            ]}
        />
    );

    const situation = (row: MemberRow, size: 'default' | 'sm' = 'default') => (
        <WStatusPillAtom kind="user" status={row.active} label={row.active ? undefined : `Desativado ${deactivation(row)}`} size={size} />
    );

    const columns: WColumn<MemberRow>[] = [
        { key: 'name', header: 'Nome', cell: (row) => <span className={cn('font-medium', muted(row))}>{row.name}</span> },
        { key: 'role_label', header: 'Papel', className: 'text-muted-foreground' },
        {
            key: 'daily_hours',
            header: 'Carga diária',
            className: 'tabular-nums',
            cell: (row) => <span className={muted(row)}>{hours(row.daily_hours)}</span>,
        },
        { key: 'situation', header: 'Situação', cell: (row) => situation(row) },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    const removeText = (row: MemberRow) =>
        row.tasks_as_dev + row.tasks_as_qa > 0
            ? `${row.name} é dev de ${row.tasks_as_dev} e QA de ${row.tasks_as_qa} tarefas deste projeto. Elas ficam sem dev e sem QA. Continuar?`
            : `Tirar ${row.name} do projeto? A pessoa deixa de ver o projeto na hora, também pela IA.`;

    return (
        <div data-slot="w-project-members-tab-organism" className="space-y-4">
            {members.length > 0 && newButton && <div className="flex justify-end">{newButton}</div>}

            <WDataTableOrganism
                caption="Pessoas do projeto"
                columns={columns}
                rows={members}
                rowKey={(row) => row.id}
                emptyState={
                    <WEmptyStateMolecule
                        icon={<Users className="size-5" aria-hidden />}
                        title="Ninguém alocado ainda."
                        description="Só quem está alocado vê o projeto, mexe no quadro e pode ser dev ou QA de uma tarefa."
                        action={newButton}
                    />
                }
                mobileCard={(row) => (
                    <>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className={cn('truncate font-semibold', muted(row))}>{row.name}</p>
                                <p className="text-xs text-muted-foreground">
                                    {row.role_label} · {hours(row.daily_hours)}
                                </p>
                            </div>
                            {situation(row, 'sm')}
                        </div>
                        {rowActions(row, true)}
                    </>
                )}
            />

            <WAllocatePeopleDialogOrganism
                open={allocateOpen}
                onOpenChange={setAllocateOpen}
                people={people}
                loading={allocateForm.processing}
                error={allocateError}
                onConfirm={allocate}
            />

            <WConfirmDialogMolecule
                open={removing !== null}
                onOpenChange={(next) => !next && setRemoving(null)}
                title="Tirar do projeto"
                description={removing ? removeText(removing) : undefined}
                confirmLabel="Tirar do projeto"
                destructive
                loading={rowAction.busy}
                onConfirm={() => removing && remove(removing)}
            />
        </div>
    );
}
