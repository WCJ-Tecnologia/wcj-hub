import { Head, router, useForm, usePage } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import {
    ROLE,
    WUserFormDialogOrganism,
    type UserFormData,
} from '@/components/organisms/w-user-form-dialog-organism/w-user-form-dialog-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import type { Paginated, SharedProps } from '@/types';

export interface UserRow {
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

export interface WUsersPageProps {
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

/** O Radix Select não aceita item com valor vazio: "todos" usa este valor e vira `undefined` no request. */
const ALL = 'all';

/** "8.00" → "8 h"; "7.50" → "7,5 h". */
const formatHours = (hours: string | null) => (hours === null ? '—' : `${Number(hours).toLocaleString('pt-BR')} h`);

/** Tela Cadastros › Usuários (Inertia: `admin/users/index`). */
export function WUsersPage({ users, filters, roles }: WUsersPageProps) {
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
            { search: filters.search || undefined, role_id: value === ALL ? undefined : value },
            { preserveState: true, replace: true },
        );

    const newButton = canManage ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Novo usuário
        </Button>
    ) : undefined;

    const rowActions = (row: UserRow, labeled = false) =>
        canManage && (
            <WRowActionsMolecule
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

    const columns: WColumn<UserRow>[] = [
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
        { key: 'active', header: 'Status', cell: (row) => <WStatusPillAtom kind="user" status={row.active} /> },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    return (
        <WAppLayoutTemplate eyebrow="Cadastros" title="Usuários" subtitle="Quem acessa o sistema e com qual papel" actions={newButton}>
            <Head title="Usuários" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_14rem]">
                <WSearchInputMolecule
                    url="/admin/users"
                    value={filters.search}
                    params={{ role_id: filters.role_id }}
                    placeholder="Buscar por nome ou e-mail…"
                    label="Buscar usuários"
                />
                <Select value={filters.role_id ? String(filters.role_id) : ALL} onValueChange={filterByRole}>
                    <SelectTrigger aria-label="Papel">
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
            </div>

            <WDataTableOrganism
                caption="Lista de usuários"
                columns={columns}
                rows={users.data}
                rowKey={(row) => row.id}
                emptyState={
                    <WEmptyStateMolecule
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
                            <WStatusPillAtom kind="user" status={row.active} size="sm" />
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

            <WPaginationMolecule meta={users} />

            <WUserFormDialogOrganism
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

            <WConfirmDialogMolecule
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

            <WConfirmDialogMolecule
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
        </WAppLayoutTemplate>
    );
}
