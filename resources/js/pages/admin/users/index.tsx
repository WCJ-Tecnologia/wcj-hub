import { Head, router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, History, MailPlus, Pencil, Plus, Trash2, UserCheck, UserX, Users } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { Pagination } from '@/components/pagination';
import { RowActions } from '@/components/row-actions';
import { SearchInput } from '@/components/search-input';
import { ShowInactiveToggle } from '@/components/show-inactive-toggle';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { ROLE, UserFormDialog, type UserFormData } from '@/components/users/user-form-dialog';
import { situation, UserTable, type UserRow } from '@/components/users/user-table';
import { useListVisit } from '@/hooks/use-list-visit';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { Paginated, SharedProps } from '@/types';

interface Props {
    users: Paginated<UserRow>;
    filters: { search: string; role_id: number | null; show_inactive: boolean };
    roles: { id: number; label: string }[];
}

type ConfirmKind = 'resend' | 'deactivate' | 'delete' | 'disconnect';

const EMPTY: UserFormData = { role_id: ROLE.DEVELOPER, name: '', email: '', daily_hours: '', ai_write_allowed: true };

export default function UsersIndex({ users, filters, roles }: Props) {
    const abilities = usePage<SharedProps>().props.auth.user?.abilities;
    const list = useListVisit();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [confirm, setConfirm] = useState<{ kind: ConfirmKind; row: UserRow } | null>(null);
    const [busy, setBusy] = useState(false);
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

    const rowRequest = {
        preserveScroll: true,
        onStart: () => setBusy(true),
        onFinish: () => {
            setBusy(false);
            setConfirm(null);
        },
    };
    const rowPost = (url: string) => router.post(url, {}, rowRequest);

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
            run: (row) => rowPost(`/admin/users/${row.id}/reset-password`),
        },
        deactivate: {
            title: () => 'Desativar usuário',
            text: (row) => `${row.name} perde o acesso imediatamente. Continuar?`,
            label: 'Desativar',
            destructive: true,
            run: (row) => rowPost(`/admin/users/${row.id}/deactivate`),
        },
        delete: {
            title: (row) => `Excluir ${row.name}?`,
            text: () => 'Excluir de verdade. Não dá para desfazer.',
            label: 'Excluir',
            destructive: true,
            run: (row) => router.delete(`/admin/users/${row.id}`, rowRequest),
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
        <RowActions
            labeled={labeled}
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
                    onClick: () => rowPost(`/admin/users/${row.id}/reactivate`),
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
        <AppLayout eyebrow="Administração" title="Usuários" subtitle="Quem acessa o Hub, com qual papel e carga diária" actions={newButton}>
            <Head title="Usuários" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <SearchInput
                    url="/admin/users"
                    value={filters.search}
                    params={filterParams}
                    placeholder="Buscar por nome ou e-mail…"
                    label="Buscar usuários"
                />
                <Select
                    value={String(filters.role_id ?? '')}
                    onChange={(event) => visit({ role_id: event.target.value || undefined })}
                    aria-label="Papel"
                >
                    <option value="">Todos os papéis</option>
                    {roles.map((role) => (
                        <option key={role.id} value={role.id}>
                            {role.label}
                        </option>
                    ))}
                </Select>
                <ShowInactiveToggle
                    checked={filters.show_inactive}
                    onChange={(checked) => visit({ show_inactive: checked ? 1 : undefined })}
                />
            </div>

            {list.failed ? (
                <EmptyState
                    icon={<AlertTriangle className="size-5" aria-hidden />}
                    title="Não foi possível carregar os usuários"
                    description="Confira a conexão e tente de novo."
                    action={<Button onClick={list.retry}>Tentar de novo</Button>}
                />
            ) : (
                <div aria-busy={list.loading} className={cn('transition-opacity', list.loading && 'opacity-60')}>
                    <p role="status" className="sr-only">
                        {list.loading ? 'Carregando usuários…' : ''}
                    </p>
                    <UserTable
                        rows={users.data}
                        actions={rowActions}
                        emptyState={
                            <EmptyState
                                icon={<Users className="size-5" aria-hidden />}
                                title={filtered ? 'Nenhum usuário com esses filtros' : 'Nenhum usuário ainda'}
                                description="Convide as pessoas que vão acessar o Hub. Cada uma recebe o acesso por e-mail."
                                action={newButton}
                            />
                        }
                    />
                </div>
            )}

            <Pagination meta={users} />

            <UserFormDialog
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                situation={editing ? situation(editing) : null}
                roles={roles}
                onSubmit={submit}
            />

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(next) => !next && setConfirm(null)}
                title={confirm && current ? current.title(confirm.row) : ''}
                description={confirm && current ? current.text(confirm.row) : undefined}
                confirmLabel={current?.label}
                destructive={current?.destructive}
                loading={busy || form.processing}
                onConfirm={() => confirm && current?.run(confirm.row)}
            />
        </AppLayout>
    );
}
