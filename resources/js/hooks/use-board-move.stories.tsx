import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import { Button } from '@/components/shadcn/button';
import type { BoardAbilities, BoardProject, MoveIntent, MovePayload } from '@/types/quadro';

import {
    ABILITIES_DEV,
    ABILITIES_GESTOR,
    BOARD_OPTIONS,
    BOARD_PROJECT,
    BOARD_PROJECT_SEM_SPRINT,
    CARD_COMPLETO,
    CARDS,
    STAGES,
} from '../../../.storybook/fixtures-quadro';
import { useBoardMove } from './use-board-move';

const [BACKLOG, TODO, DOING, PAUSED, , IN_TEST, DONE] = STAGES;
const SGM_21 = CARDS.find((item) => item.key === 'SGM-21')!;
const SGM_14 = CARDS.find((item) => item.key === 'SGM-14')!;

interface DemoProps {
    /** Simula o tempo do request depois do último diálogo, em ms. */
    latency: number;
    onCommit: (intent: MoveIntent, payload: MovePayload) => void;
    onDenied: (reason: string) => void;
}

interface Last {
    move: string;
    payload: MovePayload;
}

/** Um `useBoardMove` por perfil: o do gestor num projeto sem sprint aberta e o do dev. */
function useFlow(
    project: BoardProject,
    abilities: BoardAbilities,
    props: DemoProps,
    setLast: (last: Last) => void,
    setDenied: (reason: string) => void,
) {
    const [busy, setBusy] = useState(false);
    const flow = useBoardMove({
        project,
        abilities,
        options: BOARD_OPTIONS,
        today: '2026-10-06',
        processing: busy,
        onDenied: (reason) => {
            props.onDenied(reason);
            setDenied(reason);
        },
        onCommit: (intent, payload) => {
            props.onCommit(intent, payload);
            setLast({ move: `${intent.card.key}: ${intent.from.name} → ${intent.to.name}`, payload });
            // Simula a visita do Inertia: spinner no diálogo e, no sucesso, cancel().
            setBusy(true);
            setTimeout(() => {
                setBusy(false);
                flow.cancel();
            }, props.latency);
        },
    });
    return flow;
}

/** Botões que chamam `start()` como o arrastar do quadro faria; abaixo, o último payload e a última recusa. */
function BoardMoveFlowDemo(props: DemoProps) {
    const [last, setLast] = useState<Last | null>(null);
    const [denied, setDenied] = useState<string | null>(null);
    const gestor = useFlow(BOARD_PROJECT_SEM_SPRINT, ABILITIES_GESTOR, props, setLast, setDenied);
    const dev = useFlow(BOARD_PROJECT, ABILITIES_DEV, props, setLast, setDenied);

    const moves = [
        { label: 'Backlog → To do (sem sprint)', run: () => gestor.start({ card: SGM_21, from: BACKLOG, to: TODO, index: null }) },
        { label: 'Doing → Paused', run: () => gestor.start({ card: CARD_COMPLETO, from: DOING, to: PAUSED, index: null }) },
        { label: 'In test → Doing (retrabalho)', run: () => gestor.start({ card: SGM_14, from: IN_TEST, to: DOING, index: 0 }) },
        { label: 'In test → Done (aviso)', run: () => gestor.start({ card: SGM_14, from: IN_TEST, to: DONE, index: null }) },
        { label: 'Backlog → To do como dev (negado)', run: () => dev.start({ card: SGM_21, from: BACKLOG, to: TODO, index: null }) },
    ];

    return (
        <div className="w-full max-w-2xl space-y-4">
            <div className="flex flex-wrap gap-2">
                {moves.map((move) => (
                    <Button key={move.label} variant="outline" size="sm" onClick={move.run}>
                        {move.label}
                    </Button>
                ))}
            </div>
            <section aria-label="Último movimento enviado" className="space-y-1">
                <p className="font-mono text-xs tracking-wide text-muted-foreground uppercase">onCommit</p>
                <pre className="overflow-x-auto rounded-md border border-border bg-muted p-3 text-xs">
                    {last ? `${last.move}\n${JSON.stringify(last.payload, null, 2)}` : 'Nenhum movimento ainda.'}
                </pre>
            </section>
            <section aria-label="Última recusa" className="space-y-1">
                <p className="font-mono text-xs tracking-wide text-muted-foreground uppercase">onDenied</p>
                <pre className="overflow-x-auto rounded-md border border-border bg-muted p-3 text-xs">
                    {denied ?? 'Nenhuma recusa ainda.'}
                </pre>
            </section>
            {gestor.dialogs}
            {dev.dialogs}
        </div>
    );
}

const meta = {
    title: 'Organisms/Board Move Flow',
    component: BoardMoveFlowDemo,
    tags: ['autodocs'],
    argTypes: { latency: { control: { type: 'number', min: 0, step: 100 } } },
    args: { latency: 800, onCommit: fn(), onDenied: fn() },
    parameters: { layout: 'padded' },
} satisfies Meta<typeof BoardMoveFlowDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
