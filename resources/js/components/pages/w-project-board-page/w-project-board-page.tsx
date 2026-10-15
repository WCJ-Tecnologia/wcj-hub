/**
 * Rotas que esta tela chama (confirmadas no B3, PR 1; `routes/web.php`, grupo do quadro):
 *
 * - `GET   /projetos/{id}/quadro?search=&mine=1&developer_id=…`  esta tela, com os filtros (F3 §05)
 * - `POST  /projetos/{id}/cards`                                  criar rápido `{ title }` (P17, R4)
 * - `POST  /projetos/{id}/cards/suporte`                          card de suporte (P52)
 * - `POST  /projetos/{id}/cards/{chave}/mover`                    `MovePayload` (P13, P15, P39, P45, R5)
 * - `PATCH /projetos/{id}/cards/{chave}`                          `{ priority }`, `{ area_id }` ou `{ type_id }` (P18, P19)
 * - `POST  /projetos/{id}/cards/{chave}/participantes`            `{ user_id, add }` (P58)
 * - `POST  /projetos/{id}/tipos`, `PUT /projetos/{id}/tipos/{tid}`  `{ name, color }`: tipo do projeto, criar e editar pelo quadro (gestor e Administrador; 14/10)
 * - `POST  /projetos/{id}/areas`, `PUT /projetos/{id}/areas/{aid}`  `{ name, color }`: área do projeto, as mesmas rotas da configuração (B2), agora com a cor
 */
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, CalendarRange, Settings } from 'lucide-react';
import { useEffect, useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { toast } from 'sonner';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import type { TagPayload } from '@/components/organisms/w-tag-popover-organism/w-tag-popover-organism';
import { WBoardFiltersOrganism } from '@/components/organisms/w-board-filters-organism/w-board-filters-organism';
import { WKanbanBoardOrganism } from '@/components/organisms/w-kanban-board-organism/w-kanban-board-organism';
import {
    WSupportCardDialogOrganism,
    type SupportCardPayload,
} from '@/components/organisms/w-support-card-dialog-organism/w-support-card-dialog-organism';
import { Button } from '@/components/shadcn/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useBoardMove } from '@/hooks/use-board-move';
import { applyFilters, hasActiveFilters } from '@/lib/quadro';
import type { SharedProps } from '@/types';
import type {
    AreaRef,
    BoardAbilities,
    BoardCard,
    BoardFilters,
    BoardOptions,
    BoardOrder,
    BoardProject,
    BoardStage,
    MoveIntent,
    MovePayload,
    PersonRef,
    PrioritySlug,
    TaskTypeRef,
} from '@/types/quadro';

export interface WProjectBoardPageProps {
    project: BoardProject;
    /** Etapas ativas, na ordem de F2. */
    stages: BoardStage[];
    /** Os cards do quadro, já filtrados pelo servidor (`TaskPresenter`). */
    cards: BoardCard[];
    abilities: BoardAbilities;
    options: BoardOptions;
    /** Os filtros da URL, como o controller leu. */
    filters: BoardFilters;
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo. */
    today: string;
}

/** A ordem do quadro é de cada pessoa, lembrada no navegador (P13). */
const ORDER_KEY = 'hub.quadro.ordem';

function readOrder(): BoardOrder {
    try {
        return window.localStorage.getItem(ORDER_KEY) === 'manual' ? 'manual' : 'priority';
    } catch {
        return 'priority';
    }
}

function saveOrder(order: BoardOrder) {
    try {
        window.localStorage.setItem(ORDER_KEY, order);
    } catch {
        // Sem armazenamento (aba anônima, bloqueio): a ordem vale só até recarregar.
    }
}

/** Os filtros na URL: só o que está ligado. */
function toQuery(filters: BoardFilters): Record<string, string | number | string[]> {
    const query: Record<string, string | number | string[]> = {};
    if (filters.search.trim()) query.search = filters.search.trim();
    if (filters.mine) query.mine = 1;
    (['developer_id', 'qa_id', 'type_id', 'area_id'] as const).forEach((key) => {
        if (filters[key]) query[key] = filters[key];
    });
    if (filters.priority) query.priority = filters.priority;
    if (filters.marks.length > 0) query.marks = filters.marks;
    if (filters.old_done) query.old_done = 1;
    if (filters.archived) query.archived = 1;
    return query;
}

/** "Agora" no fuso de São Paulo, para o *Quando* do card de suporte. */
function nowInSaoPaulo(): { date: string; time: string } {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-CA', {
            timeZone: 'America/Sao_Paulo',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(new Date())
            .map((part) => [part.type, part.value]),
    );
    return { date: `${parts.year}-${parts.month}-${parts.day}`, time: `${parts.hour}:${parts.minute}` };
}

/**
 * O card muda de coluna, e de posição, na hora; volta se o servidor recusar. Com `sequence`
 * (arrastar), a coluna de destino é renumerada na ordem em que ficou na tela (P13).
 */
function moveLocally(cards: BoardCard[], card: BoardCard, payload: MovePayload): BoardCard[] {
    const moved = { ...card, stage_id: payload.stage_id };
    const others = cards.filter((item) => item.id !== card.id);
    if (!payload.sequence) return [...others, moved];

    const positions = new Map(payload.sequence.map((id, index) => [id, index + 1]));
    return [...others, moved].map((item) => (positions.has(item.id) ? { ...item, position: positions.get(item.id)! } : item));
}

/** Link do cabeçalho que ainda não existe: focável, com a dica, sem navegar. */
function SoonAction({ icon, label, hint }: { icon: ReactNode; label: string; hint: string }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="outline"
                    aria-disabled="true"
                    className="cursor-not-allowed opacity-60"
                    onClick={(event: MouseEvent) => event.preventDefault()}
                >
                    {icon} {label}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{hint}</TooltipContent>
        </Tooltip>
    );
}

/**
 * O quadro do projeto (Inertia: `projects/board`, `/projetos/{id}/quadro`; F3 §01, §03 a §05):
 * filtros, colunas com arrastar e soltar, criar rápido, card de suporte e os diálogos de movimento
 * (`useBoardMove`). Mover é otimista: o card muda de coluna antes da resposta e volta se der erro.
 */
export function WProjectBoardPage({ project, stages, cards, abilities, options, filters, today }: WProjectBoardPageProps) {
    const currentUserId = usePage<SharedProps>().props.auth.user?.id ?? null;
    const base = `/projetos/${project.id}`;

    // Cópia local dos cards (movimento otimista) e dos filtros (resposta instantânea); trocam quando o servidor manda outros.
    const [local, setLocal] = useState(cards);
    const [seenCards, setSeenCards] = useState(cards);
    const [current, setCurrent] = useState(filters);
    const [seenFilters, setSeenFilters] = useState(filters);
    if (seenCards !== cards) {
        setSeenCards(cards);
        setLocal(cards);
    }
    if (seenFilters !== filters) {
        setSeenFilters(filters);
        setCurrent(filters);
    }

    const [order, setOrder] = useState<BoardOrder>(readOrder);
    const [creating, setCreating] = useState(false);
    const [createError, setCreateError] = useState<string>();
    const [support, setSupport] = useState<{ now: { date: string; time: string } } | null>(null);
    const [supportBusy, setSupportBusy] = useState(false);
    const [supportErrors, setSupportErrors] = useState<Record<string, string>>();
    const [moving, setMoving] = useState(false);
    const [moveErrors, setMoveErrors] = useState<Record<string, string>>();

    // Busca com espera de 300 ms antes de ir ao servidor; o resto vai na hora.
    const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useEffect(
        () => () => {
            if (searchTimer.current) clearTimeout(searchTimer.current);
        },
        [],
    );

    const visitFilters = (next: BoardFilters) =>
        router.get(`${base}/quadro`, toQuery(next), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['cards', 'filters'],
        });

    const changeFilters = (next: BoardFilters) => {
        setCurrent(next);
        if (searchTimer.current) clearTimeout(searchTimer.current);
        if (next.search !== current.search) searchTimer.current = setTimeout(() => visitFilters(next), 300);
        else visitFilters(next);
    };

    const changeOrder = (next: BoardOrder) => {
        setOrder(next);
        saveOrder(next);
    };

    /** Troca um card no estado local; devolve o desfazer. */
    const patchLocal = (card: BoardCard, changes: Partial<BoardCard>) => {
        setLocal((list) => list.map((item) => (item.id === card.id ? { ...item, ...changes } : item)));
        return () => setLocal((list) => list.map((item) => (item.id === card.id ? card : item)));
    };

    const move = useBoardMove({
        project,
        abilities,
        options,
        today,
        processing: moving,
        errors: moveErrors,
        onDenied: (reason) => toast.error(reason),
        onCommit: (intent: MoveIntent, payload: MovePayload) => {
            const before = local;
            setLocal((list) => moveLocally(list, intent.card, payload));
            router.post(
                `${base}/cards/${intent.card.key}/mover`,
                { ...payload },
                {
                    preserveScroll: true,
                    onStart: () => {
                        setMoving(true);
                        setMoveErrors(undefined);
                    },
                    onSuccess: () => move.cancel(),
                    onError: (errors: Record<string, string>) => {
                        setLocal(before);
                        setMoveErrors(errors);
                        // Sem diálogo aberto, o erro não teria onde aparecer.
                        if (!move.step) toast.error(Object.values(errors)[0] ?? `Não foi possível mover ${intent.card.key}`);
                    },
                    onFinish: () => setMoving(false),
                },
            );
        },
    });

    const createCard = (title: string) =>
        router.post(
            `${base}/cards`,
            { title },
            {
                preserveScroll: true,
                onStart: () => {
                    setCreating(true);
                    setCreateError(undefined);
                },
                onError: (errors: Record<string, string>) => setCreateError(errors.title ?? Object.values(errors)[0]),
                onFinish: () => setCreating(false),
            },
        );

    const createSupport = (payload: SupportCardPayload) =>
        router.post(
            `${base}/cards/suporte`,
            { ...payload },
            {
                preserveScroll: true,
                onStart: () => {
                    setSupportBusy(true);
                    setSupportErrors(undefined);
                },
                onSuccess: () => setSupport(null),
                onError: (errors: Record<string, string>) => setSupportErrors(errors),
                onFinish: () => setSupportBusy(false),
            },
        );

    const update = (
        card: BoardCard,
        changes: Partial<BoardCard>,
        data: Parameters<typeof router.post>[1],
        url = `${base}/cards/${card.key}`,
        method: 'patch' | 'post' = 'patch',
    ) => {
        const undo = patchLocal(card, changes);
        router[method](url, data, {
            preserveScroll: true,
            onError: () => {
                undo();
                toast.error(`Não foi possível salvar ${card.key}`);
            },
        });
    };

    const changePriority = (card: BoardCard, priority: PrioritySlug) => update(card, { priority }, { priority });
    const changeArea = (card: BoardCard, area: AreaRef | null) => update(card, { area }, { area_id: area?.id ?? null });
    const changeType = (card: BoardCard, type: TaskTypeRef) => update(card, { type }, { type_id: type.id });
    // Criar e editar tipo e área pelo quadro (14/10): o servidor devolve `options` e os cards de novo; só o nome e a cor da
    // etiqueta editada mudam na hora nos cards, e voltam se o servidor recusar.
    const createTag = (path: string, payload: TagPayload, what: string) =>
        router.post(
            `${base}/${path}`,
            { ...payload },
            { preserveScroll: true, onError: (errors) => toast.error(Object.values(errors)[0] ?? `Não foi possível criar ${what}`) },
        );
    const updateTag = (path: string, payload: TagPayload, what: string, apply: (card: BoardCard) => BoardCard) => {
        const before = local;
        setLocal((list) => list.map(apply));
        router.put(
            `${base}/${path}`,
            { ...payload },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setLocal(before);
                    toast.error(Object.values(errors)[0] ?? `Não foi possível salvar ${what}`);
                },
            },
        );
    };
    const createType = (payload: TagPayload) => createTag('tipos', payload, 'o tipo');
    const updateType = (type: TaskTypeRef, payload: TagPayload) =>
        updateTag(`tipos/${type.id}`, payload, `o tipo ${type.label}`, (item) =>
            item.type?.id === type.id ? { ...item, type: { ...item.type, label: payload.name, color: payload.color } } : item,
        );
    const createArea = (payload: TagPayload) => createTag('areas', payload, 'a área');
    const updateArea = (area: AreaRef, payload: TagPayload) =>
        updateTag(`areas/${area.id}`, payload, `a área ${area.name}`, (item) =>
            item.area?.id === area.id ? { ...item, area: { ...item.area, ...payload } } : item,
        );
    const toggleParticipant = (card: BoardCard, person: PersonRef, add: boolean) =>
        update(
            card,
            { participants: add ? [...card.participants, person] : card.participants.filter((item) => item.id !== person.id) },
            { user_id: person.id, add },
            `${base}/cards/${card.key}/participantes`,
            'post',
        );

    const visible = applyFilters(local, current, currentUserId, stages);
    const supportStage = stages.find((stage) => stage.role === 'support');

    return (
        <WAppLayoutTemplate
            eyebrow="Projetos"
            title={project.name}
            subtitle={`${project.key_prefix} · ${project.open_sprint ? `${project.open_sprint.name} aberta` : 'sem sprint aberta'}`}
            width="full"
            actions={
                <>
                    <SoonAction icon={<CalendarRange aria-hidden />} label="Sprints" hint="Em breve (B5)" />
                    {/* A configuração do projeto (B2 passo 2): quem só vê o projeto abre só leitura (F2 §09). */}
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/configuracao`}>
                            <Settings aria-hidden /> Configuração
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title={`Quadro · ${project.name}`} />

            <div className="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-4">
                <WBoardFiltersOrganism
                    filters={current}
                    onChange={changeFilters}
                    options={options}
                    order={order}
                    onOrderChange={changeOrder}
                />

                {current.archived ? (
                    // A lista de arquivados (Chave, Título, Tipo, Arquivado em, Motivo) vem do B3; o kit só tem o vazio (F3 §03).
                    <WEmptyStateMolecule
                        icon={<Archive className="size-5" aria-hidden />}
                        title="Nenhum card arquivado"
                        description="Arquivar fica na ficha do card, com motivo."
                    />
                ) : (
                    <WKanbanBoardOrganism
                        stages={stages}
                        cards={visible}
                        order={order}
                        project={project}
                        abilities={abilities}
                        options={options}
                        filtered={hasActiveFilters(current) && local.length > 0}
                        onMove={move.start}
                        onOrderChange={changeOrder}
                        onCreate={createCard}
                        creating={creating}
                        createError={createError}
                        onCreateSupport={() => setSupport({ now: nowInSaoPaulo() })}
                        onChangePriority={changePriority}
                        onChangeArea={changeArea}
                        onToggleParticipant={toggleParticipant}
                        onChangeType={changeType}
                        onCreateType={abilities.manage_types ? createType : undefined}
                        onUpdateType={abilities.manage_types ? updateType : undefined}
                        onCreateArea={abilities.manage_areas ? createArea : undefined}
                        onUpdateArea={abilities.manage_areas ? updateArea : undefined}
                    />
                )}
            </div>

            {move.dialogs}

            {abilities.create_support && (
                <WSupportCardDialogOrganism
                    open={support !== null}
                    onOpenChange={(open) => !open && !supportBusy && setSupport(null)}
                    contacts={options.contacts}
                    channels={options.channels}
                    now={support?.now ?? { date: today, time: '00:00' }}
                    stageName={supportStage?.name}
                    loading={supportBusy}
                    errors={supportErrors}
                    onConfirm={createSupport}
                />
            )}
        </WAppLayoutTemplate>
    );
}
