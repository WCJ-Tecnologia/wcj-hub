import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Building2, History, Pencil, Plus, Power, RotateCcw, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { BRASIL, ClientFormDialog, type ClientFormData, type Country } from '@/components/clients/client-form-dialog';
import { ClientTable, situation, type ClientRow } from '@/components/clients/client-table';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { ListBody } from '@/components/list-body';
import { Pagination } from '@/components/pagination';
import { RowActions } from '@/components/row-actions';
import { SearchInput } from '@/components/search-input';
import { ShowInactiveToggle } from '@/components/show-inactive-toggle';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { useListVisit } from '@/hooks/use-list-visit';
import { useRowAction } from '@/hooks/use-row-action';
import AppLayout from '@/layouts/app-layout';
import type { Paginated, SharedProps } from '@/types';

interface Props {
    clients: Paginated<ClientRow>;
    filters: { search: string; country: string | null; show_inactive: boolean };
    countries: Country[];
    can: { create: boolean };
}

type ConfirmKind = 'deactivate' | 'delete';

const EMPTY: ClientFormData = { name: '', country: BRASIL, document: '' };

/** F1 §04: o aviso conta os projetos abertos, que continuam (N11). */
const deactivateText = (row: ClientRow) => {
    const open = row.open_projects ?? 0;
    if (open === 0) return `O cliente ${row.name} sai das listas de escolha. Desativar?`;
    if (open === 1) return `O cliente ${row.name} tem 1 projeto aberto; ele continua. Desativar?`;
    return `O cliente ${row.name} tem ${open} projetos abertos; eles continuam. Desativar?`;
};

export default function ClientsIndex({ clients, filters, countries, can }: Props) {
    const abilities = usePage<SharedProps>().props.auth.user?.abilities;
    const list = useListVisit();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ClientRow | null>(null);
    const [confirm, setConfirm] = useState<{ kind: ConfirmKind; row: ClientRow } | null>(null);
    const rowAction = useRowAction();
    const form = useForm<ClientFormData>(EMPTY);

    const openCreate = () => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: ClientRow) => {
        form.setData({ name: row.name, country: row.country, document: row.document ?? '' });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/admin/clients/${editing.id}`, options);
        else form.post('/admin/clients', options);
    };

    // Ao terminar, a ação da linha fecha a confirmação que a disparou.
    const closeConfirm = { onFinish: () => setConfirm(null) };

    const filterParams = {
        search: filters.search || undefined,
        country: filters.country ?? undefined,
        show_inactive: filters.show_inactive ? 1 : undefined,
    };
    const visit = (next: Record<string, string | number | undefined>) =>
        router.get('/admin/clients', { ...filterParams, ...next }, { preserveState: true, replace: true });

    const CONFIRM = {
        deactivate: {
            title: () => 'Desativar cliente',
            text: deactivateText,
            label: 'Desativar',
            run: (row: ClientRow) => rowAction.post(`/admin/clients/${row.id}/deactivate`, row, closeConfirm),
        },
        delete: {
            title: (row: ClientRow) => `Excluir ${row.name}?`,
            text: () => 'Excluir de verdade. Não dá para desfazer.',
            label: 'Excluir',
            run: (row: ClientRow) => rowAction.del(`/admin/clients/${row.id}`, row, closeConfirm),
        },
    } satisfies Record<ConfirmKind, unknown>;

    const newButton = can.create ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Novo cliente
        </Button>
    ) : undefined;

    const rowActions = (row: ClientRow, labeled = false) => (
        <RowActions
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            actions={[
                { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row), hidden: !row.can.update },
                {
                    label: 'Desativar',
                    ariaLabel: `Desativar ${row.name}`,
                    icon: Power,
                    onClick: () => setConfirm({ kind: 'deactivate', row }),
                    hidden: !row.can.deactivate,
                },
                {
                    label: 'Reativar',
                    ariaLabel: `Reativar ${row.name}`,
                    icon: RotateCcw,
                    onClick: () => rowAction.post(`/admin/clients/${row.id}/reactivate`, row),
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
                    onClick: () => router.visit(`/admin/audit-logs?type=client&id=${row.id}`),
                    hidden: !abilities?.view_audit_logs,
                },
            ]}
        />
    );

    const filtered = Boolean(filters.search || filters.country);
    const current = confirm ? CONFIRM[confirm.kind] : null;

    return (
        <AppLayout
            eyebrow="Administração"
            title="Clientes"
            subtitle="Empresas atendidas pela WCJ, inclusive a própria WCJ"
            actions={newButton}
        >
            <Head title="Clientes" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <SearchInput
                    url="/admin/clients"
                    value={filters.search}
                    params={filterParams}
                    placeholder="Buscar por nome, CNPJ ou identificador…"
                    label="Buscar clientes"
                />
                <Select
                    value={filters.country ?? ''}
                    onChange={(event) => visit({ country: event.target.value || undefined })}
                    aria-label="País"
                >
                    <option value="">Todos os países</option>
                    {countries.map((country) => (
                        <option key={country.code} value={country.code}>
                            {country.name}
                        </option>
                    ))}
                </Select>
                <ShowInactiveToggle
                    checked={filters.show_inactive}
                    onChange={(checked) => visit({ show_inactive: checked ? 1 : undefined })}
                />
            </div>

            <ListBody list={list} failedTitle="Não foi possível carregar os clientes" loadingText="Carregando clientes…">
                <ClientTable
                    rows={clients.data}
                    actions={rowActions}
                    emptyState={
                        <EmptyState
                            icon={<Building2 className="size-5" aria-hidden />}
                            title={filtered ? 'Nenhum cliente com esses filtros' : 'Nenhum cliente ainda'}
                            description="Cadastre as empresas atendidas. Todo projeto nasce ligado a um cliente."
                            action={newButton}
                        />
                    }
                />
            </ListBody>

            <Pagination meta={clients} />

            <ClientFormDialog
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                situation={editing ? situation(editing) : null}
                countries={countries}
                onSubmit={submit}
            />

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(next) => !next && setConfirm(null)}
                title={confirm && current ? current.title(confirm.row) : ''}
                description={confirm && current ? current.text(confirm.row) : undefined}
                confirmLabel={current?.label}
                destructive
                loading={rowAction.busy}
                onConfirm={() => confirm && current?.run(confirm.row)}
            />
        </AppLayout>
    );
}
