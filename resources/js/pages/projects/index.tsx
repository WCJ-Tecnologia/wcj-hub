import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Archive, ArchiveRestore, FolderKanban, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, type Column } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { Pagination } from '@/components/pagination';
import { ProjectFiltersBar, type ProjectFilters } from '@/components/projects/project-filters';
import { ProjectFormDialog, type NamedOption, type ProjectFormData } from '@/components/projects/project-form-dialog';
import { RowActions } from '@/components/row-actions';
import { StatusPill } from '@/components/status-pill';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dateBr } from '@/lib/utils';
import type { Paginated, SharedProps } from '@/types';

interface ProjectRow {
    id: number;
    key_prefix: string;
    name: string;
    client: NamedOption;
    manager: NamedOption;
    starts_on: string;
    due_on: string | null;
    /** DD/MM/AAAA no fuso local; null = aberto. */
    finished_at: string | null;
    prefix_locked: boolean;
    /** Cards fora da etapa de papel Pronto, para a confirmação de encerrar. */
    open_cards: number;
    can: { manage: boolean; delete: boolean };
}

interface Props {
    projects: Paginated<ProjectRow>;
    filters: ProjectFilters;
    filterOptions: { clients: NamedOption[]; managers: NamedOption[] };
    /** Só para quem cria projeto (Administrador e gestor). */
    formOptions: { clients: NamedOption[]; managers: NamedOption[] } | null;
    today: string;
}

type Pending = { kind: 'finish' | 'reopen' | 'delete'; row: ProjectRow } | null;

export default function ProjectsIndex({ projects, filters, filterOptions, formOptions, today }: Props) {
    const currentUser = usePage<SharedProps>().props.auth.user;
    const canCreate = Boolean(currentUser?.abilities.manage_projects) && formOptions !== null;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ProjectRow | null>(null);
    const [confirmManager, setConfirmManager] = useState(false);
    const [pending, setPending] = useState<Pending>(null);
    const [busy, setBusy] = useState(false);

    const empty = (): ProjectFormData => ({
        client_id: '',
        // O Gestor de projetos que cria já vem como responsável (F2 §02).
        manager_id: currentUser?.role === 'project_manager' ? currentUser.id : '',
        key_prefix: '',
        name: '',
        type: 'delivery',
        starts_on: today,
        due_on: '',
    });
    const form = useForm<ProjectFormData>(empty());

    const openCreate = () => {
        form.setData(empty());
        form.clearErrors();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (row: ProjectRow) => {
        form.setData({
            client_id: row.client.id,
            manager_id: row.manager.id,
            key_prefix: row.key_prefix,
            name: row.name,
            type: row.due_on ? 'delivery' : 'continuous',
            starts_on: row.starts_on,
            due_on: row.due_on ?? '',
        });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const send = () => {
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/projetos/${editing.id}`, options);
        else form.post('/projetos', options);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        // Trocar o responsável pede confirmação (F2 §04).
        if (editing && form.data.manager_id !== editing.manager.id) setConfirmManager(true);
        else send();
    };

    const runPending = () => {
        if (!pending) return;
        const url = `/projetos/${pending.row.id}`;
        const options = {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setPending(null);
            },
        };
        if (pending.kind === 'delete') router.delete(url, options);
        else router.post(`${url}/${pending.kind === 'finish' ? 'encerrar' : 'reabrir'}`, {}, options);
    };

    // O responsável atual pode não estar mais na lista (desativado ou sem o papel): continua escolhível.
    const managerOptions =
        editing && !formOptions?.managers.some((m) => m.id === editing.manager.id)
            ? [editing.manager, ...(formOptions?.managers ?? [])]
            : (formOptions?.managers ?? []);

    const newButton = canCreate ? (
        <Button onClick={openCreate}>
            <Plus className="size-4" aria-hidden /> Novo projeto
        </Button>
    ) : undefined;

    const rowActions = (row: ProjectRow, labeled = false) => (
        <RowActions
            labeled={labeled}
            actions={[
                { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row), hidden: !row.can.manage },
                {
                    label: 'Encerrar',
                    ariaLabel: `Encerrar ${row.name}`,
                    icon: Archive,
                    onClick: () => setPending({ kind: 'finish', row }),
                    hidden: !row.can.manage || row.finished_at !== null,
                },
                {
                    label: 'Reabrir',
                    ariaLabel: `Reabrir ${row.name}`,
                    icon: ArchiveRestore,
                    onClick: () => setPending({ kind: 'reopen', row }),
                    hidden: !row.can.manage || row.finished_at === null,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${row.name}`,
                    icon: Trash2,
                    onClick: () => setPending({ kind: 'delete', row }),
                    destructive: true,
                    hidden: !row.can.delete,
                },
            ]}
        />
    );

    const situation = (row: ProjectRow, size: 'default' | 'sm' = 'default') => (
        <StatusPill
            kind="project"
            status={row.finished_at === null}
            label={row.finished_at ? `Encerrado em ${row.finished_at}` : undefined}
            size={size}
        />
    );

    const columns: Column<ProjectRow>[] = [
        {
            key: 'key_prefix',
            header: 'Prefixo',
            cell: (row) => (
                <Badge variant="outline" className="font-mono">
                    {row.key_prefix}
                </Badge>
            ),
        },
        { key: 'name', header: 'Nome', cell: (row) => <span className="font-medium">{row.name}</span> },
        { key: 'client', header: 'Cliente', className: 'text-muted-foreground', cell: (row) => row.client.name },
        { key: 'manager', header: 'Gestor', className: 'text-muted-foreground', cell: (row) => row.manager.name },
        { key: 'type', header: 'Tipo', cell: (row) => (row.due_on ? 'Projeto de entrega' : 'Produto contínuo') },
        { key: 'starts_on', header: 'Início', className: 'tabular-nums', cell: (row) => dateBr(row.starts_on) },
        { key: 'due_on', header: 'Entrega', className: 'tabular-nums', cell: (row) => dateBr(row.due_on) },
        { key: 'situation', header: 'Situação', cell: (row) => situation(row) },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    // Quem não cria projeto aqui é o dev: o estado vazio fala com ele (F2 §03).
    const isDev = !currentUser?.abilities.manage_projects;

    return (
        <AppLayout
            eyebrow="Projetos"
            title="Projetos"
            subtitle="Os projetos que você vê, com cliente, gestor e prazo"
            actions={newButton}
            width="wide"
        >
            <Head title="Projetos" />

            <ProjectFiltersBar filters={filters} clients={filterOptions.clients} managers={filterOptions.managers} />

            <DataTable
                caption="Lista de projetos"
                columns={columns}
                rows={projects.data}
                rowKey={(row) => row.id}
                emptyState={
                    <EmptyState
                        icon={<FolderKanban className="size-5" aria-hidden />}
                        title={isDev ? 'Você ainda não está alocado em nenhum projeto' : 'Nenhum projeto ainda.'}
                        description={
                            isDev
                                ? 'Quando o gestor alocar você, o projeto aparece aqui.'
                                : 'Crie o projeto escolhendo o cliente e o gestor. O quadro padrão vem pronto.'
                        }
                        action={newButton}
                    />
                }
                mobileCard={(row) => (
                    <>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate font-semibold">
                                    <span className="font-mono text-muted-foreground">{row.key_prefix}</span> {row.name}
                                </p>
                                <p className="truncate text-xs text-muted-foreground">
                                    {row.client.name} · {row.manager.name}
                                </p>
                            </div>
                            {situation(row, 'sm')}
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground tabular-nums">
                            {row.due_on ? `Entrega ${dateBr(row.due_on)}` : 'Produto contínuo'} · Início {dateBr(row.starts_on)}
                        </p>
                        {rowActions(row, true)}
                    </>
                )}
            />

            <Pagination meta={projects} />

            {formOptions && (
                <ProjectFormDialog
                    open={open}
                    onOpenChange={setOpen}
                    form={form}
                    editing={editing !== null}
                    clientName={editing?.client.name}
                    prefixLocked={editing?.prefix_locked ?? false}
                    clients={formOptions.clients}
                    managers={managerOptions}
                    onSubmit={submit}
                />
            )}

            <ConfirmDialog
                open={confirmManager}
                onOpenChange={setConfirmManager}
                title="Trocar o gestor responsável"
                description={`${editing?.manager.name ?? ''} deixa de ser o responsável e só continua gerindo se estiver alocado. Continuar?`}
                confirmLabel="Trocar e salvar"
                loading={form.processing}
                onConfirm={() => {
                    setConfirmManager(false);
                    send();
                }}
            />

            <ConfirmDialog
                open={pending !== null}
                onOpenChange={(next) => !next && setPending(null)}
                title={pending?.kind === 'delete' ? 'Excluir projeto' : pending?.kind === 'finish' ? 'Encerrar projeto' : 'Reabrir projeto'}
                description={
                    pending?.kind === 'delete'
                        ? `Excluir de verdade ${pending.row.name}. Não dá para desfazer. Só sai o projeto sem tarefa, sprint e pacote.`
                        : pending?.kind === 'finish'
                          ? `O projeto tem ${pending.row.open_cards} cards fora da etapa de papel Pronto. Eles continuam no quadro. Encerrar?`
                          : `${pending?.row.name ?? ''} volta a receber tarefas.`
                }
                confirmLabel={pending?.kind === 'delete' ? 'Excluir' : pending?.kind === 'finish' ? 'Encerrar' : 'Reabrir'}
                destructive={pending?.kind === 'delete'}
                loading={busy}
                onConfirm={runPending}
            />
        </AppLayout>
    );
}
