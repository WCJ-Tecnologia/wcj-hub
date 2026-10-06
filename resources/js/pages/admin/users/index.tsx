import { Head, router, useForm, usePage } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, type Column } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { Pagination } from '@/components/pagination';
import { RowActions } from '@/components/row-actions';
import { SearchInput } from '@/components/search-input';
import { StatusPill } from '@/components/status-pill';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { ROLE, UserFormDialog, type UserFormData } from '@/components/users/user-form-dialog';
import AppLayout from '@/layouts/app-layout';
import type { Paginated, SharedProps } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string;
    active: boolean;
    /** Só vem para quem pode editar. */
    deactivated_at?: string | null;
    deactivated_by?: string | null;
    role_id: number;
    role_label: string;
    /** decimal(6,2) como texto ("8.00"); null = sem carga (N3). */
    daily_hours: string | null;
    must_change_password: boolean;
}

interface Props {
    users: Paginated<UserRow>;
    filters: { search: string; role_id: number | null };
    roles: { id: number; label: string }[];
}

const EMPTY: UserFormData = {
    role_id: ROLE.DEVELOPER,
    name: '',
    email: '',
    daily_hours: '',
    active: true,
};

/** "8.00" → "8 h"; "7.50" → "7,5 h". */
const formatHours = (hours: string | null) => (hours === null ? '—' : `${Number(hours).toLocaleString('pt-BR')} h`);

export default function UsersIndex({ users, filters, roles }: Props) {
    const currentUser = usePage<SharedProps>().props.auth.user;
    const canManage = Boolean(currentUser?.abilities.manage_users);
    const canDelete = Boolean(currentUser?.abilities.delete_records);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [confirmDelete, setConfirmDelete] = useState<UserRow | null>(null);
    const [confirmReset, setConfirmReset] = useState<UserRow | null>(null);
    const [busy, setBusy] = useState(false);
    const form = useForm<UserFormData>(EMPTY);

    const firstName = (name: string) => name.trim().split(/\s+/)[0] || name;
    const canResetPassword = () => Boolean(currentUser?.abilities.reset_passwords);

    const openCreate = () => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: UserRow) => {
        form.setData({
            role_id: row.role_id,
            name: row.name,
            email: row.email,
            daily_hours: row.daily_hours ?? '',
            active: row.active,
        });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/admin/users/${editing.id}`, options);
        else form.post('/admin/users', options);
    };

    /** Opções comuns às ações de linha (excluir, reenviar senha). */
    const rowRequest = (done: () => void) => ({
        preserveScroll: true,
        onStart: () => setBusy(true),
        onFinish: () => {
            setBusy(false);
            done();
        },
    });

    const filterByRole = (value: string) =>
        router.get(
            '/admin/users',
            { search: filters.search || undefined, role_id: value || undefined },
            { preserveState: true, replace: true },
        );

    const newButton = canManage ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Novo usuário
        </Button>
    ) : undefined;

    const rowActions = (row: UserRow, labeled = false) =>
        canManage && (
            <RowActions
                labeled={labeled}
                actions={[
                    {
                        label: 'Enviar senha',
                        ariaLabel: `Enviar nova senha para ${row.name}`,
                        icon: KeyRound,
                        onClick: () => setConfirmReset(row),
                        hidden: !canResetPassword(),
                    },
                    { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row) },
                    {
                        label: 'Excluir',
                        ariaLabel: `Excluir ${row.name}`,
                        icon: Trash2,
                        onClick: () => setConfirmDelete(row),
                        destructive: true,
                        hidden: !canDelete,
                    },
                ]}
            />
        );

    const columns: Column<UserRow>[] = [
        {
            key: 'name',
            header: 'Nome',
            cell: (row) => (
                <>
                    <p className="font-medium">{row.name}</p>
                    {row.must_change_password && (
                        <p className="mt-1">
                            <Badge variant="warning">Trocar senha no próximo acesso</Badge>
                        </p>
                    )}
                </>
            ),
        },
        { key: 'role_label', header: 'Papel', cell: (row) => <Badge variant="info">{row.role_label}</Badge> },
        { key: 'email', header: 'E-mail', className: 'text-muted-foreground', cell: (row) => row.email },
        {
            key: 'daily_hours',
            header: 'Carga diária',
            className: 'text-muted-foreground tabular-nums',
            cell: (row) => formatHours(row.daily_hours),
        },
        { key: 'active', header: 'Status', cell: (row) => <StatusPill kind="user" status={row.active} /> },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    return (
        <AppLayout eyebrow="Cadastros" title="Usuários" subtitle="Quem acessa o sistema e com qual papel" actions={newButton}>
            <Head title="Usuários" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto]">
                <SearchInput
                    url="/admin/users"
                    value={filters.search}
                    params={{ role_id: filters.role_id }}
                    placeholder="Buscar por nome ou e-mail…"
                    label="Buscar usuários"
                />
                <Select value={String(filters.role_id ?? '')} onChange={(event) => filterByRole(event.target.value)} aria-label="Papel">
                    <option value="">Todos os papéis</option>
                    {roles.map((role) => (
                        <option key={role.id} value={role.id}>
                            {role.label}
                        </option>
                    ))}
                </Select>
            </div>

            <DataTable
                caption="Lista de usuários"
                columns={columns}
                rows={users.data}
                rowKey={(row) => row.id}
                emptyState={
                    <EmptyState
                        icon={<Users className="size-5" aria-hidden />}
                        title="Nenhum usuário encontrado"
                        description="Cadastre as pessoas que vão acessar o sistema. Cada uma recebe uma senha temporária por e-mail."
                        action={newButton}
                    />
                }
                mobileCard={(row) => (
                    <>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate font-semibold">{row.name}</p>
                                <p className="truncate text-xs text-muted-foreground">{row.email}</p>
                            </div>
                            <StatusPill kind="user" status={row.active} size="sm" />
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <Badge variant="info">{row.role_label}</Badge>
                            <Badge variant="outline">{formatHours(row.daily_hours)}</Badge>
                            {row.must_change_password && <Badge variant="warning">Trocar senha</Badge>}
                        </div>
                        {rowActions(row, true)}
                    </>
                )}
            />

            <Pagination meta={users} />

            <UserFormDialog
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                deactivatedNote={
                    editing?.deactivated_at
                        ? `Desativado em ${editing.deactivated_at}${editing.deactivated_by ? `, por ${editing.deactivated_by}` : ''}.`
                        : null
                }
                roles={roles}
                onSubmit={submit}
            />

            <ConfirmDialog
                open={confirmDelete !== null}
                onOpenChange={(next) => !next && setConfirmDelete(null)}
                title="Excluir usuário"
                description={
                    <>
                        Excluir <strong className="text-foreground">{confirmDelete?.name}</strong>? Só é possível excluir quem nunca entrou
                        no sistema. Se a pessoa já usou, desmarque &quot;Usuário ativo&quot; em Editar.
                    </>
                }
                confirmLabel="Excluir"
                destructive
                loading={busy}
                onConfirm={() =>
                    confirmDelete &&
                    router.delete(
                        `/admin/users/${confirmDelete.id}`,
                        rowRequest(() => setConfirmDelete(null)),
                    )
                }
            />

            <ConfirmDialog
                open={confirmReset !== null}
                onOpenChange={(next) => !next && setConfirmReset(null)}
                title="Enviar senha temporária"
                description={
                    <>
                        Vamos mandar uma senha temporária para <strong className="text-foreground">{confirmReset?.email}</strong>. No
                        próximo acesso, {confirmReset ? firstName(confirmReset.name) : 'a pessoa'} escolhe uma senha nova. A atual deixa de
                        valer na hora.
                    </>
                }
                confirmLabel="Enviar agora"
                loading={busy}
                onConfirm={() =>
                    confirmReset &&
                    router.post(
                        `/admin/users/${confirmReset.id}/reset-password`,
                        {},
                        rowRequest(() => setConfirmReset(null)),
                    )
                }
            />
        </AppLayout>
    );
}
