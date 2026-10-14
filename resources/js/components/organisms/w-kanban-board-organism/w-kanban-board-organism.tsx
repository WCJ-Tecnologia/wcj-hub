import {
    closestCorners,
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    pointerWithin,
    useSensor,
    useSensors,
    type Announcements,
    type CollisionDetection,
    type DragEndEvent,
    type DragOverEvent,
    type DragStartEvent,
    type Over,
    type ScreenReaderInstructions,
} from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { Info, LayoutGrid, SearchX } from 'lucide-react';
import { useMemo, useState } from 'react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import {
    WKanbanColumnOrganism,
    type BoardDndData,
    type ColumnCardCallbacks,
} from '@/components/organisms/w-kanban-column-organism/w-kanban-column-organism';
import { WTaskCardOrganism } from '@/components/organisms/w-task-card-organism/w-task-card-organism';
import { sortCards } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { BoardAbilities, BoardCard, BoardOptions, BoardOrder, BoardProject, BoardStage, MoveIntent } from '@/types/quadro';

export interface WKanbanBoardOrganismProps extends Omit<ColumnCardCallbacks, 'onMove'> {
    /** Etapas ativas do projeto, na ordem de F2. */
    stages: BoardStage[];
    /** Os cards que o quadro mostra (já filtrados); o quadro agrupa por `stage_id` e ordena. */
    cards: BoardCard[];
    order: BoardOrder;
    project: BoardProject;
    abilities: BoardAbilities;
    options: Pick<BoardOptions, 'areas' | 'members'>;
    /**
     * Pedido de movimento: do arrastar (`index` e `sequence` = onde o card foi solto e a coluna na
     * ordem que ficou, como no Trello) e do *Mover para…* do card (os dois nulos). O quadro não abre
     * diálogo nem decide regra: a página passa ao `useBoardMove`.
     */
    onMove: (intent: MoveIntent) => void;
    /**
     * Soltar numa posição com a ordem em *Prioridade* troca o seletor para *Manual* (senão a
     * prioridade desfaria o que a pessoa acabou de arrastar). A página guarda a escolha.
     */
    onOrderChange?: (order: BoardOrder) => void;
    /** Há filtro ligado: sem card, a mensagem é a do filtro, não a do projeto vazio (F3 §05). */
    filtered?: boolean;
    onCreate?: (title: string) => void;
    creating?: boolean;
    createError?: string;
    onCreateSupport?: () => void;
    className?: string;
}

/** Solta onde o ponteiro está, preferindo o card à coluna; no teclado (sem ponteiro), o mais perto. */
const collision: CollisionDetection = (args) => {
    const within = pointerWithin(args);
    const overCards = within.filter((item) => String(item.id).startsWith('card-'));
    if (overCards.length > 0) return overCards;
    if (within.length > 0) return within;
    return closestCorners(args);
};

const dataOf = (over: Pick<Over, 'data'> | null) => over?.data.current as BoardDndData | undefined;

const screenReaderInstructions: ScreenReaderInstructions = {
    draggable:
        'Para mover o card, tecle Espaço, use as setas para escolher a coluna, Espaço para soltar e Esc para cancelar. O menu Ações do card também tem Mover para.',
};

/**
 * O quadro kanban (F3 §01, §03, §04): uma coluna por etapa, com rolagem horizontal, arrastar e
 * soltar (dnd-kit) entre colunas e dentro da coluna, com o card caindo onde foi solto, como no
 * Trello. Cada soltar vira um `MoveIntent` em `onMove`; a página decide o resto.
 */
export function WKanbanBoardOrganism({
    stages,
    cards,
    order,
    project,
    abilities,
    options,
    onMove,
    onOrderChange,
    filtered = false,
    onCreate,
    creating,
    createError,
    onCreateSupport,
    className,
    ...callbacks
}: WKanbanBoardOrganismProps) {
    const [active, setActive] = useState<BoardCard | null>(null);
    const [overStage, setOverStage] = useState<number | null>(null);

    const sensors = useSensors(
        // 5 px antes de pegar: o clique no título (link) e no menu continuam cliques, e o card solto não abre a ficha.
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const columns = useMemo(() => {
        const byStage = new Map<number, BoardCard[]>(stages.map((stage) => [stage.id, []]));
        cards.forEach((card) => byStage.get(card.stage_id)?.push(card));
        return new Map([...byStage].map(([id, list]) => [id, sortCards(list, order)]));
    }, [stages, cards, order]);

    const stageById = (id: number | undefined) => stages.find((stage) => stage.id === id);

    // Lê o card do próprio evento: o estado `active` ainda é o do último desenho quando o dnd-kit anuncia.
    const keyOf = (item: Pick<Over, 'data'>) => {
        const data = dataOf(item);
        return data?.type === 'card' ? data.card.key : 'O card';
    };
    const announcements: Announcements = {
        onDragStart: ({ active: item }) => `Pegou ${keyOf(item)}. Use as setas para mover entre colunas e Espaço para soltar.`,
        onDragOver: ({ active: item, over }) => {
            const stage = stageById(dataOf(over)?.stage_id);
            return stage ? `${keyOf(item)} sobre ${stage.name}.` : `${keyOf(item)} fora das colunas.`;
        },
        onDragEnd: ({ active: item, over }) => {
            const stage = stageById(dataOf(over)?.stage_id);
            return stage ? `${keyOf(item)} solto em ${stage.name}.` : `${keyOf(item)} solto fora das colunas; nada mudou.`;
        },
        onDragCancel: ({ active: item }) => `Movimento cancelado. ${keyOf(item)} ficou onde estava.`,
    };

    const reset = () => {
        setActive(null);
        setOverStage(null);
    };

    const handleStart = ({ active: item }: DragStartEvent) => {
        const data = dataOf(item);
        setActive(data?.type === 'card' ? data.card : null);
    };

    const handleOver = ({ over }: DragOverEvent) => setOverStage(dataOf(over)?.stage_id ?? null);

    const handleEnd = ({ active: item, over }: DragEndEvent) => {
        reset();
        const data = dataOf(item);
        const target = dataOf(over);
        if (data?.type !== 'card' || !target || !over) return;

        const card = data.card;
        const from = stageById(card.stage_id);
        const to = stageById(target.stage_id);
        if (!from || !to) return;

        // A coluna como está na tela, sem o card que anda; o card entra onde foi solto (sobre um card =
        // no lugar dele; no vazio da coluna = no fim). É a ordem que a pessoa viu, não a gravada.
        const column = (columns.get(to.id) ?? []).filter((item) => item.id !== card.id);
        const overIndex = target.type === 'card' ? column.findIndex((item) => item.id === target.card.id) : -1;
        const index = overIndex === -1 ? column.length : overIndex;

        if (from.id === to.id) {
            const current = (columns.get(to.id) ?? []).findIndex((item) => item.id === card.id);
            if (index === current) return;
        }

        const sequence = [...column.slice(0, index).map((item) => item.id), card.id, ...column.slice(index).map((item) => item.id)];
        // Em *Prioridade* a coluna se reordenaria sozinha e desfaria o soltar: vira *Manual* (14/10).
        if (order !== 'manual') onOrderChange?.('manual');
        onMove({ card, from, to, index, sequence });
    };

    // *Mover para…* do menu: o mesmo pedido do arrastar, sem posição.
    const menuMove = (card: BoardCard, to: BoardStage) => {
        const from = stageById(card.stage_id);
        if (from) onMove({ card, from, to, index: null, sequence: null });
    };

    const empty = cards.length === 0;

    return (
        <div data-slot="w-kanban-board-organism" className={cn('flex min-h-0 min-w-0 flex-col gap-3', className)}>
            {project.finished && (
                <p className="flex items-center gap-2 rounded-md border border-border bg-muted px-3 py-2 text-sm text-muted-foreground">
                    <Info className="size-4 shrink-0" aria-hidden />
                    Projeto encerrado: não recebe card novo; mover, comentar e anexar seguem.
                </p>
            )}

            {empty &&
                (filtered ? (
                    <WEmptyStateMolecule
                        icon={<SearchX className="size-5" aria-hidden />}
                        title="Nenhum card com esses filtros"
                        description="Limpe ou troque os filtros para ver os cards do quadro."
                    />
                ) : (
                    <WEmptyStateMolecule
                        icon={<LayoutGrid className="size-5" aria-hidden />}
                        title="Nenhum card ainda"
                        description="Escreva o título no topo do Backlog e tecle Enter."
                    />
                ))}

            <DndContext
                sensors={sensors}
                collisionDetection={collision}
                accessibility={{ announcements, screenReaderInstructions }}
                onDragStart={handleStart}
                onDragOver={handleOver}
                onDragEnd={handleEnd}
                onDragCancel={reset}
            >
                <div
                    role="region"
                    aria-label="Colunas do quadro"
                    // Focável: no teclado, as setas rolam as colunas (scrollable-region-focusable). `relative` prende os
                    // `sr-only` (absolutos) das colunas aqui dentro; sem ele, a página inteira ganhava rolagem horizontal.
                    tabIndex={0}
                    className="relative flex h-[calc(100dvh-14rem)] min-h-96 snap-x snap-mandatory gap-3 overflow-x-auto overscroll-x-contain rounded-md pb-2 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 md:snap-none"
                >
                    {stages.map((stage) => (
                        <WKanbanColumnOrganism
                            key={stage.id}
                            stage={stage}
                            cards={columns.get(stage.id) ?? []}
                            abilities={abilities}
                            stages={stages}
                            areas={options.areas}
                            members={options.members}
                            highlighted={active !== null && overStage === stage.id && active.stage_id !== stage.id}
                            onCreate={onCreate}
                            creating={creating}
                            createError={createError}
                            onCreateSupport={onCreateSupport}
                            onMove={menuMove}
                            {...callbacks}
                        />
                    ))}
                </div>

                {/* Sem animação de volta: o card só muda de coluna quando a página confirma (diálogos, servidor). */}
                <DragOverlay dropAnimation={null}>
                    {active && (
                        <WTaskCardOrganism
                            card={active}
                            stage={stageById(active.stage_id) ?? stages[0]}
                            areas={options.areas}
                            members={options.members}
                            dragging
                            className="w-68"
                        />
                    )}
                </DragOverlay>
            </DndContext>
        </div>
    );
}
