import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Building2, Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, type Column } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { FormField } from '@/components/form-field';
import { Pagination } from '@/components/pagination';
import { RowActions } from '@/components/row-actions';
import { SearchInput } from '@/components/search-input';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DocumentInput } from '@/components/ui/masked-input';
import AppLayout from '@/layouts/app-layout';
import { formatDocument } from '@/lib/masks';
import type { Paginated, SharedProps } from '@/types';

interface EnterpriseRow {
    id: number;
    name: string;
    nickname: string;
    document: string | null;
    active: boolean;
    /** Só vem para quem pode editar. */
    deactivated_at?: string | null;
    deactivated_by?: string | null;
    users_count: number;
}

interface Props {
    enterprises: Paginated<EnterpriseRow>;
    filters: { search: string };
}

const EMPTY = { name: '', nickname: '', document: '', active: true };

/**
 * O CRUD gabarito. Toda listagem de um projeto derivado segue esta forma:
 * SearchInput → EmptyState → DataTable → Pagination → Dialog com useForm →
 * ConfirmDialog. Ver .ai/stacks/ui.md, seção 4.
 */
export default function EnterprisesIndex({ enterprises, filters }: Props) {
    const user = usePage<SharedProps>().props.auth.user;
    const canManage = Boolean(user?.abilities.manage_enterprises);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<EnterpriseRow | null>(null);
    const [confirmDelete, setConfirmDelete] = useState<EnterpriseRow | null>(null);
    const [deleting, setDeleting] = useState(false);
    const form = useForm(EMPTY);

    const openCreate = () => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: EnterpriseRow) => {
        form.setData({ name: row.name, nickname: row.nickname, document: row.document ?? '', active: row.active });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/admin/enterprises/${editing.id}`, options);
        else form.post('/admin/enterprises', options);
    };

    const destroy = () => {
        if (!confirmDelete) return;
        router.delete(`/admin/enterprises/${confirmDelete.id}`, {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setConfirmDelete(null);
            },
        });
    };

    const newButton = canManage ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Nova empresa
        </Button>
    ) : undefined;

    const rowActions = (row: EnterpriseRow, labeled = false) =>
        canManage && (
            <RowActions
                labeled={labeled}
                actions={[
                    { label: 'Editar', ariaLabel: `Editar ${row.nickname}`, icon: Pencil, onClick: () => openEdit(row) },
                    {
                        label: 'Excluir',
                        ariaLabel: `Excluir ${row.nickname}`,
                        icon: Trash2,
                        onClick: () => setConfirmDelete(row),
                        destructive: true,
                    },
                ]}
            />
        );

    const columns: Column<EnterpriseRow>[] = [
        { key: 'name', header: 'Empresa', className: 'font-medium' },
        { key: 'nickname', header: 'Apelido', className: 'text-muted-foreground' },
        {
            key: 'document',
            header: 'CNPJ',
            className: 'text-muted-foreground tabular-nums',
            cell: (row) => formatDocument(row.document) || '—',
        },
        {
            key: 'users_count',
            header: 'Usuários',
            cell: (row) => (
                <span className="inline-flex items-center gap-1.5 text-muted-foreground tabular-nums">
                    <Users className="size-3.5" aria-hidden /> {row.users_count}
                </span>
            ),
        },
        { key: 'active', header: 'Status', cell: (row) => <StatusPill kind="enterprise" status={row.active} /> },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    return (
        <AppLayout
            eyebrow="Cadastros"
            title="Empresas"
            subtitle="Empresas e filiais que delimitam o escopo dos usuários"
            actions={newButton}
        >
            <Head title="Empresas" />

            <SearchInput
                url="/admin/enterprises"
                value={filters.search}
                placeholder="Buscar por nome, apelido ou CNPJ…"
                label="Buscar empresas"
                className="mb-4"
            />

            <DataTable
                caption="Lista de empresas"
                columns={columns}
                rows={enterprises.data}
                rowKey={(row) => row.id}
                emptyState={
                    <EmptyState
                        icon={<Building2 className="size-5" aria-hidden />}
                        title="Nenhuma empresa cadastrada"
                        description="Cadastre a primeira empresa para começar a organizar os usuários por escopo."
                        action={newButton}
                    />
                }
                mobileCard={(row) => (
                    <>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate font-semibold">{row.name}</p>
                                <p className="truncate text-xs text-muted-foreground">{row.nickname}</p>
                            </div>
                            <StatusPill kind="enterprise" status={row.active} size="sm" />
                        </div>
                        <div className="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                            <span className="tabular-nums">{formatDocument(row.document) || 'Sem CNPJ'}</span>
                            <span className="inline-flex items-center gap-1">
                                <Users className="size-3.5" aria-hidden /> {row.users_count} usuários
                            </span>
                        </div>
                        {rowActions(row, true)}
                    </>
                )}
            />

            <Pagination meta={enterprises} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Editar empresa' : 'Nova empresa'}</DialogTitle>
                        <DialogDescription>A empresa delimita o que o perfil Financeiro enxerga.</DialogDescription>
                    </DialogHeader>
                    <DialogBody>
                        <form id="enterprise-form" onSubmit={submit} className="space-y-4">
                            <FormField label="Razão social" htmlFor="name" required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            </FormField>
                            <FormField
                                label="Apelido"
                                htmlFor="nickname"
                                required
                                error={form.errors.nickname}
                                hint="Nome curto usado nas listagens."
                            >
                                <Input value={form.data.nickname} onChange={(e) => form.setData('nickname', e.target.value)} />
                            </FormField>
                            <FormField label="CNPJ" htmlFor="document" error={form.errors.document}>
                                <DocumentInput
                                    variant="cnpj"
                                    value={form.data.document}
                                    onChange={(value) => form.setData('document', value)}
                                />
                            </FormField>
                            <div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="active"
                                        checked={form.data.active}
                                        onCheckedChange={(checked) => form.setData('active', checked === true)}
                                        aria-describedby={editing?.deactivated_at ? 'active-note' : undefined}
                                    />
                                    <Label htmlFor="active" className="cursor-pointer">
                                        Empresa ativa
                                    </Label>
                                </div>
                                {editing?.deactivated_at && (
                                    <p id="active-note" className="mt-1.5 text-xs text-muted-foreground">
                                        Desativada em {editing.deactivated_at}
                                        {editing.deactivated_by && `, por ${editing.deactivated_by}`}.
                                    </p>
                                )}
                            </div>
                        </form>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)} disabled={form.processing}>
                            Cancelar
                        </Button>
                        <Button type="submit" form="enterprise-form" disabled={form.processing}>
                            {editing ? 'Salvar alterações' : 'Cadastrar empresa'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={confirmDelete !== null}
                onOpenChange={(next) => !next && setConfirmDelete(null)}
                title="Excluir empresa"
                description={
                    <>
                        Excluir <strong className="text-foreground">{confirmDelete?.name}</strong>? Só é possível excluir empresa sem
                        usuários nem filiais. Se ela já está em uso, desmarque &quot;Empresa ativa&quot; em Editar.
                    </>
                }
                confirmLabel="Excluir"
                destructive
                loading={deleting}
                onConfirm={destroy}
            />
        </AppLayout>
    );
}
