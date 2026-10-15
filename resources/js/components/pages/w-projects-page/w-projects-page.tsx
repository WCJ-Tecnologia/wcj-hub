import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Archive, ArchiveRestore, CalendarRange, FolderKanban, Pencil, Plus, Settings, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WListBodyMolecule } from '@/components/molecules/w-list-body-molecule/w-list-body-molecule';
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';
import {
    BRASIL,
    WClientFormDialogOrganism,
    type ClientFormData,
    type Country,
} from '@/components/organisms/w-client-form-dialog-organism/w-client-form-dialog-organism';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { WProjectFiltersOrganism, type ProjectFilters } from '@/components/organisms/w-project-filters-organism/w-project-filters-organism';
import {
    WProjectFormDialogOrganism,
    type NamedOption,
    type ProjectFormData,
} from '@/components/organisms/w-project-form-dialog-organism/w-project-form-dialog-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useListVisit } from '@/hooks/use-list-visit';
import { useRowAction } from '@/hooks/use-row-action';
import { cn, dateBr } from '@/lib/utils';
import type { Paginated, SharedProps } from '@/types';

/** A linha como o ProjectPresenter manda. */
export interface ProjectRow {
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
    /** Decidido pela ProjectPolicy no servidor; a tela só esconde o botão. */
    can: { manage: boolean; delete: boolean };
}

export interface WProjectsPageProps {
    projects: Paginated<ProjectRow>;
    filters: ProjectFilters;
    filterOptions: { clients: NamedOption[]; managers: NamedOption[] };
    /** Só para quem cria projeto (Administrador e gestor). */
    formOptions: { clients: NamedOption[]; managers: NamedOption[] } | null;
    /** Hoje no fuso de São Paulo, o Início padrão (F2 §02). */
    today: string;
    /** Para o "Novo cliente" do formulário (N6). */
    countries: Country[];
}

type ConfirmKind = 'finish' | 'reopen' | 'delete';

// Encerrado fica em cinza (F2 §03).
const muted = (row: ProjectRow) => cn(row.finished_at !== null && 'text-muted-foreground');

/** Tela Projetos (Inertia: `projects/index`; F2 §03 a §05): lista, criar/editar por modal, encerrar, reabrir e excluir. */
export function WProjectsPage({ projects, filters, filterOptions, formOptions, today, countries }: WProjectsPageProps) {
    const currentUser = usePage<SharedProps>().props.auth.user;
    const canCreate = Boolean(currentUser?.abilities.manage_projects) && formOptions !== null;
    const list = useListVisit();
    const rowAction = useRowAction();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ProjectRow | null>(null);
    const [confirmManager, setConfirmManager] = useState(false);
    const [confirm, setConfirm] = useState<{ kind: ConfirmKind; row: ProjectRow } | null>(null);

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

    // "Novo cliente" sem sair do formulário do projeto (N6, F2 §02): ao salvar, o servidor devolve o
    // id no flash e a lista de clientes já vem com ele; o projeto fica com o cliente escolhido.
    const EMPTY_CLIENT: ClientFormData = { name: '', country: BRASIL, document: '' };
    const [clientOpen, setClientOpen] = useState(false);
    const clientForm = useForm<ClientFormData>(EMPTY_CLIENT);
    const openNewClient = () => {
        clientForm.setData(EMPTY_CLIENT);
        clientForm.clearErrors();
        setClientOpen(true);
    };
    const submitClient = (event: FormEvent) => {
        event.preventDefault();
        clientForm.post('/projetos/clientes', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const createdId = (page.props as unknown as SharedProps).flash?.created_id;
                if (createdId) form.setData('client_id', createdId);
                form.clearErrors('client_id');
                setClientOpen(false);
            },
        });
    };

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

    // Ao terminar, a ação da linha fecha a confirmação que a disparou.
    const closeConfirm = { onFinish: () => setConfirm(null) };

    const CONFIRM: Record<
        ConfirmKind,
        { title: string; text: (row: ProjectRow) => string; label: string; destructive?: boolean; run: (row: ProjectRow) => void }
    > = {
        finish: {
            title: 'Encerrar projeto',
            text: (row) => `O projeto tem ${row.open_cards} cards fora da etapa de papel Pronto. Eles continuam no quadro. Encerrar?`,
            label: 'Encerrar',
            run: (row) => rowAction.post(`/projetos/${row.id}/encerrar`, row, closeConfirm),
        },
        reopen: {
            title: 'Reabrir projeto',
            text: (row) => `${row.name} volta a receber tarefas.`,
            label: 'Reabrir',
            run: (row) => rowAction.post(`/projetos/${row.id}/reabrir`, row, closeConfirm),
        },
        delete: {
            title: 'Excluir projeto',
            text: (row) => `Excluir de verdade ${row.name}. Não dá para desfazer. Só sai o projeto sem tarefa, sprint e pacote.`,
            label: 'Excluir',
            destructive: true,
            run: (row) => rowAction.del(`/projetos/${row.id}`, row, closeConfirm),
        },
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
        <WRowActionsMolecule
            labeled={labeled}
            busy={rowAction.isBusy(row)}
            disabled={rowAction.isLocked(row)}
            actions={[
                // Configurar abre pessoas, etapas, áreas e padrões; quem só vê o projeto abre só leitura (F2 §03, §09).
                {
                    label: 'Configurar',
                    ariaLabel: `Configurar ${row.name}`,
                    icon: Settings,
                    onClick: () => router.visit(`/projetos/${row.id}/configuracao`),
                },
                // Sprints do projeto (F5 §01): quem vê o projeto vê as sprints.
                {
                    label: 'Sprints',
                    ariaLabel: `Sprints de ${row.name}`,
                    icon: CalendarRange,
                    onClick: () => router.visit(`/projetos/${row.id}/sprints`),
                },
                { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row), hidden: !row.can.manage },
                {
                    label: 'Encerrar',
                    ariaLabel: `Encerrar ${row.name}`,
                    icon: Archive,
                    onClick: () => setConfirm({ kind: 'finish', row }),
                    hidden: !row.can.manage || row.finished_at !== null,
                },
                {
                    label: 'Reabrir',
                    ariaLabel: `Reabrir ${row.name}`,
                    icon: ArchiveRestore,
                    onClick: () => setConfirm({ kind: 'reopen', row }),
                    hidden: !row.can.manage || row.finished_at === null,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${row.name}`,
                    icon: Trash2,
                    onClick: () => setConfirm({ kind: 'delete', row }),
                    destructive: true,
                    hidden: !row.can.delete,
                },
            ]}
        />
    );

    const situation = (row: ProjectRow, size: 'default' | 'sm' = 'default') => (
        <WStatusPillAtom
            kind="project"
            status={row.finished_at === null}
            label={row.finished_at ? `Encerrado em ${row.finished_at}` : undefined}
            size={size}
        />
    );

    const columns: WColumn<ProjectRow>[] = [
        {
            key: 'key_prefix',
            header: 'Prefixo',
            cell: (row) => (
                <Badge variant="outline" className={cn('font-mono', muted(row))}>
                    {row.key_prefix}
                </Badge>
            ),
        },
        { key: 'name', header: 'Nome', cell: (row) => <span className={cn('font-medium', muted(row))}>{row.name}</span> },
        { key: 'client', header: 'Cliente', className: 'text-muted-foreground', cell: (row) => row.client.name },
        { key: 'manager', header: 'Gestor', className: 'text-muted-foreground', cell: (row) => row.manager.name },
        {
            key: 'type',
            header: 'Tipo',
            cell: (row) => <span className={muted(row)}>{row.due_on ? 'Projeto de entrega' : 'Produto contínuo'}</span>,
        },
        {
            key: 'starts_on',
            header: 'Início',
            className: 'tabular-nums',
            cell: (row) => <span className={muted(row)}>{dateBr(row.starts_on)}</span>,
        },
        {
            key: 'due_on',
            header: 'Entrega',
            className: 'tabular-nums',
            cell: (row) => <span className={muted(row)}>{dateBr(row.due_on)}</span>,
        },
        { key: 'situation', header: 'Situação', cell: (row) => situation(row) },
        { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
    ];

    // Quem não cria projeto aqui é o dev: o estado vazio fala com ele (F2 §03).
    const isDev = !currentUser?.abilities.manage_projects;
    const current = confirm ? CONFIRM[confirm.kind] : null;

    return (
        <WAppLayoutTemplate
            eyebrow="Projetos"
            title="Projetos"
            subtitle="Os projetos que você vê, com cliente, gestor e prazo"
            actions={newButton}
            width="wide"
        >
            <Head title="Projetos" />

            <WProjectFiltersOrganism filters={filters} clients={filterOptions.clients} managers={filterOptions.managers} />

            <WListBodyMolecule list={list} failedTitle="Não foi possível carregar os projetos" loadingText="Carregando projetos…">
                <WDataTableOrganism
                    caption="Lista de projetos"
                    columns={columns}
                    rows={projects.data}
                    rowKey={(row) => row.id}
                    emptyState={
                        <WEmptyStateMolecule
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
                                    <p className={cn('truncate font-semibold', muted(row))}>
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
            </WListBodyMolecule>

            <WPaginationMolecule meta={projects} />

            {formOptions && (
                <WProjectFormDialogOrganism
                    open={open}
                    onOpenChange={setOpen}
                    form={form}
                    editing={editing !== null}
                    clientName={editing?.client.name}
                    prefixLocked={editing?.prefix_locked ?? false}
                    clients={formOptions.clients}
                    managers={managerOptions}
                    onSubmit={submit}
                    onNewClient={openNewClient}
                />
            )}

            {formOptions && (
                <WClientFormDialogOrganism
                    open={clientOpen}
                    onOpenChange={setClientOpen}
                    form={clientForm}
                    editing={false}
                    countries={countries}
                    onSubmit={submitClient}
                />
            )}

            <WConfirmDialogMolecule
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

            <WConfirmDialogMolecule
                open={confirm !== null}
                onOpenChange={(next) => !next && setConfirm(null)}
                title={current?.title ?? ''}
                description={confirm && current ? current.text(confirm.row) : undefined}
                confirmLabel={current?.label}
                destructive={current?.destructive}
                loading={rowAction.busy}
                onConfirm={() => confirm && current?.run(confirm.row)}
            />
        </WAppLayoutTemplate>
    );
}
