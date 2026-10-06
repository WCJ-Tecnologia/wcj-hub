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
import { USER_TYPE, UserFormDialog, type UserFormData } from '@/components/users/user-form-dialog';
import AppLayout from '@/layouts/app-layout';
import { formatPhone } from '@/lib/masks';
import type { Paginated, SharedProps } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string | null;
    document: string | null;
    phone: string | null;
    active: boolean;
    /** Só vem para quem pode editar. */
    deactivated_at?: string | null;
    deactivated_by?: string | null;
    user_type_id: number;
    type_label: string;
    last_login_at: string | null;
    must_change_password: boolean;
    enterprises: { id: number; nickname: string }[];
}

interface Props {
    users: Paginated<UserRow>;
    filters: { search: string; user_type_id: number | null };
    userTypes: { id: number; label: string }[];
    enterpriseOptions: { id: number; nickname: string }[];
}

const EMPTY: UserFormData = {
    user_type_id: USER_TYPE.ADMIN,
    name: '',
    email: '',
    document: '',
    phone: '',
    active: true,
    enterprise_ids: [],
};

export default function UsersIndex({ users, filters, userTypes, enterpriseOptions }: Props) {
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
    const canResetPassword = (row: UserRow) => Boolean(currentUser?.abilities.reset_passwords && row.email);

    const openCreate = () => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: UserRow) => {
        form.setData({
            user_type_id: row.user_type_id,
            name: row.name,
            email: row.email ?? '',
            document: row.document ?? '',
            phone: row.phone ?? '',
            active: row.active,
            enterprise_ids: row.enterprises.map((enterprise) => enterprise.id),
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

    const filterByType = (value: string) =>
        router.get(
            '/admin/users',
            { search: filters.search || undefined, user_type_id: value || undefined },
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
                        hidden: !canResetPassword(row),
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
                    {row.last_login_at && <p className="text-xs text-muted-foreground">Último acesso {row.last_login_at}</p>}
                    {row.must_change_password && (
                        <p className="mt-1">
                            <Badge variant="warning">Trocar senha no próximo acesso</Badge>
                        </p>
                    )}
                </>
            ),
        },
        { key: 'type_label', header: 'Perfil', cell: (row) => <Badge variant="info">{row.type_label}</Badge> },
        {
            key: 'contact',
            header: 'Contato',
            className: 'text-muted-foreground',
            cell: (row) => (
                <>
                    <p>{row.email ?? '—'}</p>
                    {row.phone && <p className="text-xs tabular-nums">{formatPhone(row.phone)}</p>}
                </>
            ),
        },
        {
            key: 'enterprises',
            header: 'Empresas',
            className: 'text-muted-foreground',
            cell: (row) => (row.enterprises.length === 0 ? '—' : row.enterprises.map((enterprise) => enterprise.nickname).join(', ')),
        },
        { key: 'active', header: 'Status', cell: (row) => <StatusPill kind="user" status={row.active} /> },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    return (
        <AppLayout eyebrow="Cadastros" title="Usuários" subtitle="Quem acessa o sistema e com qual perfil" actions={newButton}>
            <Head title="Usuários" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto]">
                <SearchInput
                    url="/admin/users"
                    value={filters.search}
                    params={{ user_type_id: filters.user_type_id }}
                    placeholder="Buscar por nome, e-mail ou documento…"
                    label="Buscar usuários"
                />
                <Select
                    value={String(filters.user_type_id ?? '')}
                    onChange={(event) => filterByType(event.target.value)}
                    aria-label="Perfil"
                >
                    <option value="">Todos os perfis</option>
                    {userTypes.map((type) => (
                        <option key={type.id} value={type.id}>
                            {type.label}
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
                                <p className="truncate text-xs text-muted-foreground">{row.email ?? 'Sem e-mail'}</p>
                            </div>
                            <StatusPill kind="user" status={row.active} size="sm" />
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <Badge variant="info">{row.type_label}</Badge>
                            {row.must_change_password && <Badge variant="warning">Trocar senha</Badge>}
                            {row.enterprises.map((enterprise) => (
                                <Badge key={enterprise.id} variant="outline">
                                    {enterprise.nickname}
                                </Badge>
                            ))}
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
                userTypes={userTypes}
                enterpriseOptions={enterpriseOptions}
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
