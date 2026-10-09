import { Head, router, useForm, usePage } from '@inertiajs/react';
import { History, MailPlus, Pencil, Plus, Trash2, UserCheck, UserX, Users } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WListBodyMolecule } from '@/components/molecules/w-list-body-molecule/w-list-body-molecule';
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { WShowInactiveToggleMolecule } from '@/components/molecules/w-show-inactive-toggle-molecule/w-show-inactive-toggle-molecule';
import {
    ROLE,
    WUserFormDialogOrganism,
    type UserFormData,
} from '@/components/organisms/w-user-form-dialog-organism/w-user-form-dialog-organism';
import { WUserTableOrganism, type UserRow } from '@/components/organisms/w-user-table-organism/w-user-table-organism';
import { Button } from '@/components/shadcn/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useListVisit } from '@/hooks/use-list-visit';
import { useRowAction } from '@/hooks/use-row-action';
import { situation } from '@/lib/situation';
import type { Paginated, SharedProps } from '@/types';

export type { UserRow };

export interface WUsersPageProps {
    users: Paginated<UserRow>;
    filters: { search: string; role_id: number | null; show_inactive: boolean };
    roles: { id: number; label: string }[];
}

type ConfirmKind = 'resend' | 'deactivate' | 'delete' | 'disconnect';

const EMPTY: UserFormData = { role_id: ROLE.DEVELOPER, name: '', email: '', daily_hours: '', ai_write_allowed: true };

/** O Radix Select não aceita item com valor vazio: "todos" usa este valor e vira `role_id` ausente no request. */
const ALL = 'all';

/** Tela Administração › Usuários (Inertia: `admin/users/index`; F1 §03). */
export function WUsersPage({ users, filters, roles }: WUsersPageProps) {
    const abilities = usePage<SharedProps>().props.auth.user?.abilities;
    const list = useListVisit();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [confirm, setConfirm] = useState<{ kind: ConfirmKind; row: UserRow } | null>(null);
    const rowAction = useRowAction();
    const form = useForm<UserFormData>(EMPTY);

    const openCreate = () => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: UserRow) => {
        const { role_id, name, email, ai_write_allowed } = row;
        form.setData({ role_id, name, email, daily_hours: row.daily_hours ?? '', ai_write_allowed });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const save = () => {
        const options = { preserveScroll: true, onSuccess: () => setOpen(false), onFinish: () => setConfirm(null) };
        if (editing) form.put(`/admin/users/${editing.id}`, options);
        else form.post('/admin/users', options);
    };

    // IA4, IA6: trocar o papel ou a marca da IA derruba as conexões de IA da pessoa; avisa antes.
    const submit = (event: FormEvent) => {
        event.preventDefault();
        const disconnects = editing && (form.data.role_id !== editing.role_id || form.data.ai_write_allowed !== editing.ai_write_allowed);
        if (disconnects) setConfirm({ kind: 'disconnect', row: editing });
        else save();
    };

    // Ao terminar, a ação da linha fecha a confirmação que a disparou.
    const closeConfirm = { onFinish: () => setConfirm(null) };

    const filterParams = {
        search: filters.search || undefined,
        role_id: filters.role_id ?? undefined,
        show_inactive: filters.show_inactive ? 1 : undefined,
    };
    const visit = (next: Record<string, string | number | undefined>) =>
        router.get('/admin/users', { ...filterParams, ...next }, { preserveState: true, replace: true });

    const CONFIRM: Record<
        ConfirmKind,
        {
            title: (row: UserRow) => string;
            text: (row: UserRow) => ReactNode;
            label: string;
            destructive?: boolean;
            run: (row: UserRow) => void;
        }
    > = {
        resend: {
            title: () => 'Reenviar convite',
            text: (row) => `Vamos mandar uma senha provisória nova para ${row.email}. A anterior deixa de valer na hora.`,
            label: 'Reenviar',
            run: (row) => rowAction.post(`/admin/users/${row.id}/reset-password`, row, closeConfirm),
        },
        deactivate: {
            title: () => 'Desativar usuário',
            text: (row) => `${row.name} perde o acesso imediatamente. Continuar?`,
            label: 'Desativar',
            destructive: true,
            run: (row) => rowAction.post(`/admin/users/${row.id}/deactivate`, row, closeConfirm),
        },
        delete: {
            title: (row) => `Excluir ${row.name}?`,
            text: () => 'Excluir de verdade. Não dá para desfazer.',
            label: 'Excluir',
            destructive: true,
            run: (row) => rowAction.del(`/admin/users/${row.id}`, row, closeConfirm),
        },
        disconnect: {
            title: () => 'Desconectar a IA',
            text: (row) => `As conexões de IA de ${row.name} serão desconectadas. Continuar?`,
            label: 'Salvar',
            run: save,
        },
    };

    const newButton = abilities?.manage_users ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Convidar usuário
        </Button>
    ) : undefined;

    // Cada botão da linha vem decidido pela UserPolicy no servidor (row.can), como em Clientes e
    // Contatos; a tela só esconde. Histórico é da tela de auditoria, por isso a ability global.
    const rowActions = (row: UserRow, labeled = false) => (
        <WRowActionsMolecule
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            // Até cinco ações: no desktop, três por linha, para a tabela caber sem rolagem lateral.
            className={labeled ? undefined : 'ml-auto w-30 flex-wrap'}
            actions={[
                {
                    label: 'Reenviar convite',
                    ariaLabel: `Reenviar convite para ${row.name}`,
                    icon: MailPlus,
                    onClick: () => setConfirm({ kind: 'resend', row }),
                    hidden: !row.can.resend_invite,
                },
                { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row), hidden: !row.can.update },
                {
                    label: 'Desativar',
                    ariaLabel: `Desativar ${row.name}`,
                    icon: UserX,
                    onClick: () => setConfirm({ kind: 'deactivate', row }),
                    hidden: !row.can.deactivate,
                },
                {
                    label: 'Reativar',
                    ariaLabel: `Reativar ${row.name}`,
                    icon: UserCheck,
                    onClick: () => rowAction.post(`/admin/users/${row.id}/reactivate`, row),
                    hidden: !row.can.reactivate,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${row.name}`,
                    icon: Trash2,
                    onClick: () => setConfirm({ kind: 'delete', row }),
                    destructive: true,
                    hidden: !row.can.delete,
                },
                {
                    label: 'Histórico',
                    ariaLabel: `Histórico de ${row.name}`,
                    icon: History,
                    onClick: () => router.visit(`/admin/audit-logs?type=user&id=${row.id}`),
                    hidden: !abilities?.view_audit_logs,
                },
            ]}
        />
    );

    const filtered = Boolean(filters.search || filters.role_id);
    const current = confirm ? CONFIRM[confirm.kind] : null;

    return (
        <WAppLayoutTemplate eyebrow="Administração" title="Usuários" subtitle="Quem acessa o Hub, com qual papel e carga diária" actions={newButton}>
            <Head title="Usuários" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <WSearchInputMolecule
                    url="/admin/users"
                    value={filters.search}
                    params={filterParams}
                    placeholder="Buscar por nome ou e-mail…"
                    label="Buscar usuários"
                />
                <Select
                    value={filters.role_id ? String(filters.role_id) : ALL}
                    onValueChange={(value) => visit({ role_id: value === ALL ? undefined : value })}
                >
                    <SelectTrigger aria-label="Papel" className="sm:w-56">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos os papéis</SelectItem>
                        {roles.map((role) => (
                            <SelectItem key={role.id} value={String(role.id)}>
                                {role.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <WShowInactiveToggleMolecule
                    checked={filters.show_inactive}
                    onChange={(checked) => visit({ show_inactive: checked ? 1 : undefined })}
                />
            </div>

            <WListBodyMolecule list={list} failedTitle="Não foi possível carregar os usuários" loadingText="Carregando usuários…">
                <WUserTableOrganism
                    rows={users.data}
                    actions={rowActions}
                    emptyState={
                        <WEmptyStateMolecule
                            icon={<Users className="size-5" aria-hidden />}
                            title={filtered ? 'Nenhum usuário com esses filtros' : 'Nenhum usuário ainda'}
                            description="Convide as pessoas que vão acessar o Hub. Cada uma recebe o acesso por e-mail."
                            action={newButton}
                        />
                    }
                />
            </WListBodyMolecule>

            <WPaginationMolecule meta={users} />

            <WUserFormDialogOrganism
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                situation={editing ? situation(editing) : null}
                roles={roles}
                onSubmit={submit}
            />

            <WConfirmDialogMolecule
                open={confirm !== null}
                onOpenChange={(next) => !next && setConfirm(null)}
                title={confirm && current ? current.title(confirm.row) : ''}
                description={confirm && current ? current.text(confirm.row) : undefined}
                confirmLabel={current?.label}
                destructive={current?.destructive}
                loading={rowAction.busy || form.processing}
                onConfirm={() => confirm && current?.run(confirm.row)}
            />
        </WAppLayoutTemplate>
    );
}
