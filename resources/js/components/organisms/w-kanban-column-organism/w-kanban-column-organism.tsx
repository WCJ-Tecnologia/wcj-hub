import { useDroppable } from '@dnd-kit/core';
import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Info, Plus } from 'lucide-react';
import { useId, useState } from 'react';

import { WQuickCreateMolecule } from '@/components/molecules/w-quick-create-molecule/w-quick-create-molecule';
import { WTaskCardOrganism, type WTaskCardOrganismProps } from '@/components/organisms/w-task-card-organism/w-task-card-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { cn } from '@/lib/utils';
import type { AreaRef, BoardAbilities, BoardCard, BoardStage, PersonRef, TaskTypeRef } from '@/types/quadro';

/** Id do card no dnd-kit (`card-17`); o da coluna é `stage-3`. O quadro lê os dois no `onDragEnd`. */
export const cardDndId = (card: Pick<BoardCard, 'id'>) => `card-${card.id}`;
export const stageDndId = (stage: Pick<BoardStage, 'id'>) => `stage-${stage.id}`;

/** O que o quadro lê de `active.data` e `over.data` no `onDragEnd`. */
export type BoardDndData = { type: 'card'; card: BoardCard; stage_id: number } | { type: 'stage'; stage_id: number };

/** Os callbacks que a coluna repassa a cada card. */
export type ColumnCardCallbacks = Pick<
    WTaskCardOrganismProps,
    | 'onMove'
    | 'onChangePriority'
    | 'onChangeArea'
    | 'onToggleParticipant'
    | 'onChangeType'
    | 'onCreateType'
    | 'onUpdateType'
    | 'onCreateArea'
    | 'onUpdateArea'
    | 'onOpen'
>;

export interface WKanbanColumnOrganismProps extends ColumnCardCallbacks {
    stage: BoardStage;
    /** Os cards desta coluna, já ordenados (`sortCards`). */
    cards: BoardCard[];
    /** Só muda o que acontece ao arrastar dentro da coluna (P13). */
    abilities: BoardAbilities;
    /** As etapas do projeto, para o *Mover para…* do card. */
    stages: BoardStage[];
    areas: AreaRef[];
    /** Tipos do projeto (P18): opções do popover do tipo (14/10). */
    types?: TaskTypeRef[];
    members: PersonRef[];
    /** O card arrastado está sobre esta coluna: destaca a área de soltar. */
    highlighted?: boolean;
    /** Criar rápido (só no Backlog, com `abilities.create_card`). */
    onCreate?: (title: string) => void;
    creating?: boolean;
    createError?: string;
    /** *Novo card de suporte* (só no Support, com `abilities.create_support`). */
    onCreateSupport?: () => void;
}

/**
 * Uma coluna do quadro (F3 §03): cabeçalho com o nome e a contagem, o criar rápido no Backlog, o
 * *Novo card de suporte* no Support e a lista de cards, com rolagem própria. A coluna inteira é
 * área de soltar (`stage-<id>`), para receber card mesmo vazia.
 */
export function WKanbanColumnOrganism({
    stage,
    cards,
    abilities,
    stages,
    areas,
    types = [],
    members,
    highlighted = false,
    onCreate,
    creating = false,
    createError,
    onCreateSupport,
    ...callbacks
}: WKanbanColumnOrganismProps) {
    const titleId = useId();
    // O campo do criar rápido só aparece pelo "+" do cabeçalho (14/10): sem ele fixo, os cards de todas as colunas alinham.
    const [composing, setComposing] = useState(false);
    const { setNodeRef } = useDroppable({ id: stageDndId(stage), data: { type: 'stage', stage_id: stage.id } satisfies BoardDndData });

    const showQuickCreate = stage.role === 'queue' && abilities.create_card && onCreate;
    const showSupport = stage.role === 'support' && abilities.create_support && onCreateSupport;

    return (
        <section
            data-slot="w-kanban-column-organism"
            data-stage={stage.id}
            data-over={highlighted || undefined}
            aria-labelledby={titleId}
            className={cn(
                // O "poço" dos cards: no claro, o cinza cheio da marca; no escuro, mais escuro que o card, para ele parecer em relevo.
                'flex h-full w-72 shrink-0 snap-start flex-col rounded-lg border border-transparent bg-muted transition-colors dark:bg-background/60',
                highlighted && 'border-primary/50 bg-accent/60',
            )}
        >
            <header className="flex items-center gap-2 px-3 pt-3 pb-2">
                <h2 id={titleId} className="flex min-w-0 items-center gap-2 text-sm font-semibold">
                    <span className="truncate">{stage.name}</span>
                    <Badge variant="secondary" className="font-mono tabular-nums">
                        {cards.length}
                        <span className="sr-only">{cards.length === 1 ? ' card' : ' cards'}</span>
                    </Badge>
                </h2>
                {showQuickCreate && (
                    <Button
                        type="button"
                        variant={composing ? 'secondary' : 'ghost'}
                        size="icon"
                        aria-label={composing ? 'Fechar o novo card' : 'Novo card'}
                        aria-pressed={composing}
                        title="Novo card: título e Enter"
                        className="ml-auto size-6 text-muted-foreground"
                        onClick={() => setComposing((open) => !open)}
                    >
                        <Plus aria-hidden />
                    </Button>
                )}
                {showSupport && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Novo card de suporte"
                        title="Novo card de suporte"
                        className="ml-auto size-6 text-muted-foreground"
                        onClick={onCreateSupport}
                    >
                        <Plus aria-hidden />
                    </Button>
                )}
                {stage.role === 'done' && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={`Quais cards aparecem em ${stage.name}`}
                                className="ml-auto size-6 text-muted-foreground"
                            >
                                <Info aria-hidden />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent className="max-w-64">
                            Mostra a sprint aberta e a última encerrada; os mais antigos pelo filtro Mostrar prontos antigos
                        </TooltipContent>
                    </Tooltip>
                )}
            </header>

            {showQuickCreate && composing && (
                <div className="px-2 pb-2">
                    <WQuickCreateMolecule
                        onCreate={onCreate}
                        busy={creating}
                        error={createError}
                        autoFocus
                        onCancel={() => setComposing(false)}
                    />
                </div>
            )}

            <div ref={setNodeRef} className="flex min-h-0 flex-1 flex-col overflow-y-auto px-2 pb-2">
                <SortableContext
                    id={stageDndId(stage)}
                    items={cards.map(cardDndId)}
                    // Em qualquer ordem os vizinhos abrem espaço: o card cai onde for solto (F3 §04, 14/10).
                    strategy={verticalListSortingStrategy}
                >
                    <ul className="grid gap-2">
                        {cards.map((card) => (
                            <SortableCard
                                key={card.id}
                                card={card}
                                stage={stage}
                                stages={stages}
                                areas={areas}
                                types={types}
                                members={members}
                                draggable={abilities.move}
                                {...callbacks}
                            />
                        ))}
                    </ul>
                </SortableContext>
                {/* Coluna vazia: só a contagem 0 (F3 §03) e uma área discreta onde soltar. */}
                {cards.length === 0 && <div aria-hidden className="min-h-24 flex-1 rounded-md border border-dashed border-border" />}
            </div>
        </section>
    );
}

interface SortableCardProps extends ColumnCardCallbacks {
    card: BoardCard;
    stage: BoardStage;
    stages: BoardStage[];
    areas: AreaRef[];
    types: TaskTypeRef[];
    members: PersonRef[];
    /** Sem `abilities.move` o card não arrasta (e o *Mover para…* não aparece). */
    draggable: boolean;
}

/**
 * O item da lista é o que arrasta. Os `attributes` do `useSortable` vão sem o `role="button"`: o card
 * tem link e botões dentro, e botão com controle dentro é `nested-interactive` no axe. O item segue
 * focável (`tabIndex`) para o teclado do dnd-kit, e só ele ativa o arrastar por teclado
 * (`setActivatorNodeRef`): Enter no título abre a ficha, não pega o card.
 */
function SortableCard({ card, stage, stages, areas, types, members, draggable, onMove, ...callbacks }: SortableCardProps) {
    const { attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition, isDragging } = useSortable({
        id: cardDndId(card),
        data: { type: 'card', card, stage_id: card.stage_id } satisfies BoardDndData,
        disabled: !draggable,
    });
    // eslint-disable-next-line @typescript-eslint/no-unused-vars -- o role sai de propósito (ver acima)
    const { role, ...a11y } = attributes;

    return (
        <li
            ref={(node) => {
                setNodeRef(node);
                setActivatorNodeRef(node);
            }}
            {...a11y}
            {...listeners}
            aria-roledescription="card arrastável"
            data-card={card.key}
            style={{ transform: CSS.Translate.toString(transform), transition }}
            className={cn(
                'touch-manipulation rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                draggable && 'cursor-grab',
                // O lugar de onde o card saiu fica marcado; quem anda com o ponteiro é o DragOverlay.
                isDragging && 'opacity-40',
            )}
        >
            <WTaskCardOrganism
                card={card}
                stage={stage}
                stages={stages}
                areas={areas}
                types={types}
                members={members}
                onMove={draggable ? onMove : undefined}
                {...callbacks}
            />
        </li>
    );
}
