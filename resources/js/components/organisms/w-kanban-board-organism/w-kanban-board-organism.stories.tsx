import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';
import { fn } from 'storybook/test';

import { Toaster } from '@/components/shadcn/sonner';
import type { BoardCard, BoardOrder } from '@/types/quadro';

import {
    ABILITIES_DEV,
    ABILITIES_ENCERRADO,
    ABILITIES_GESTOR,
    BOARD_OPTIONS,
    BOARD_PROJECT,
    CARDS,
    CARDS_MONTAR_SPRINT,
    CARDS_VAZIO,
    MONTAR_SPRINT_MARCADAS,
    STAGES,
} from '../../../../../.storybook/fixtures-quadro';
import { WKanbanBoardOrganism, type WKanbanBoardOrganismProps } from './w-kanban-board-organism';

/**
 * O quadro com estado local: cada `onMove` aparece no painel Actions e o card cai onde foi solto na
 * hora, sem diálogos; soltar com a ordem em Prioridade troca para Manual (`onOrderChange`). Os
 * diálogos e as recusas estão na story da página.
 */
function KanbanBoardDemo(props: WKanbanBoardOrganismProps) {
    const [cards, setCards] = useState<BoardCard[]>(props.cards);
    const [order, setOrder] = useState<BoardOrder>(props.order);

    return (
        <>
            <WKanbanBoardOrganism
                {...props}
                cards={cards}
                order={order}
                onOrderChange={(next) => {
                    action('onOrderChange')(next);
                    setOrder(next);
                }}
                onMove={(intent) => {
                    props.onMove(intent);
                    setCards((list) => {
                        const moved = { ...intent.card, stage_id: intent.to.id };
                        const others = list.filter((item) => item.id !== intent.card.id);
                        if (!intent.sequence) return [...others, moved];
                        const positions = new Map(intent.sequence.map((id, index) => [id, index + 1]));
                        return [...others, moved].map((item) =>
                            positions.has(item.id) ? { ...item, position: positions.get(item.id)! } : item,
                        );
                    });
                }}
                onChangePriority={(card, priority) => {
                    action('onChangePriority')(card.key, priority);
                    setCards((list) => list.map((item) => (item.id === card.id ? { ...item, priority } : item)));
                }}
            />
            <Toaster />
        </>
    );
}

/** *Montar sprint* (D-GER-35): o quadro em modo de seleção, com as marcadas guardadas aqui. */
function SelectionDemo(props: WKanbanBoardOrganismProps) {
    const [selected, setSelected] = useState<number[]>(MONTAR_SPRINT_MARCADAS);

    return (
        <WKanbanBoardOrganism
            {...props}
            selection={{
                selected: new Set(selected),
                onChange: (ids) => {
                    action('selection.onChange')(ids);
                    setSelected(ids);
                },
            }}
        />
    );
}

const meta = {
    title: 'Organisms/Kanban Board',
    component: WKanbanBoardOrganism,
    tags: ['autodocs'],
    argTypes: {
        cards: { control: 'object' },
        order: { control: 'inline-radio', options: ['priority', 'manual'] },
        project: { control: 'object' },
        abilities: { control: 'object' },
        filtered: { control: 'boolean' },
        creating: { control: 'boolean' },
        createError: { control: 'text' },
        stages: { control: false },
        options: { control: false },
        onMove: { control: false },
        onOrderChange: { control: false },
        onCreate: { control: false },
        onCreateSupport: { control: false },
        onChangePriority: { control: false },
        onChangeArea: { control: false },
        onToggleParticipant: { control: false },
        onOpen: { control: false },
    },
    args: {
        stages: STAGES,
        cards: CARDS,
        order: 'priority',
        project: BOARD_PROJECT,
        abilities: ABILITIES_GESTOR,
        options: BOARD_OPTIONS,
        onMove: fn(),
        onCreate: fn(),
        onCreateSupport: fn(),
        onChangeArea: fn(),
        onToggleParticipant: fn(),
    },
    parameters: { layout: 'padded' },
    render: (args) => <KanbanBoardDemo key={`${args.order}-${args.cards.length}`} {...args} />,
} satisfies Meta<typeof WKanbanBoardOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor: arraste entre colunas (e para uma coluna que ficou vazia) e veja o `onMove` no Actions. */
export const Padrao: Story = {};
/** Dev: cria no Backlog, não tem *Novo card de suporte*; o quadro não recusa nada, quem recusa é a página (`classifyMove`). */
export const ComoDev: Story = { args: { abilities: ABILITIES_DEV } };
/** Já em ordem manual: o arraste reordena sem trocar o seletor; o `onMove` leva `index` e `sequence` (P13). */
export const OrdemManual: Story = { args: { order: 'manual' } };
export const Vazio: Story = { args: { cards: CARDS_VAZIO } };
export const FiltroSemResultado: Story = { args: { cards: [], filtered: true } };
export const ProjetoEncerrado: Story = { args: { project: { ...BOARD_PROJECT, finished: true }, abilities: ABILITIES_ENCERRADO } };
/** 375 px: uma coluna por vez, com rolagem horizontal e encaixe. */
export const Celular: Story = {
    decorators: [
        (Story) => (
            <div className="w-[375px] overflow-hidden border border-border">
                <Story />
            </div>
        ),
    ],
};
/**
 * *Montar sprint* ligado (D-GER-35), com 3 marcadas: caixa em cada card do Backlog e *Selecionar todas*;
 * as outras colunas esmaecem e nada arrasta. A barra e o diálogo estão nas stories da página.
 */
export const ModoMontarSprint: Story = {
    args: { cards: CARDS_MONTAR_SPRINT },
    render: (args) => <SelectionDemo {...args} />,
};
