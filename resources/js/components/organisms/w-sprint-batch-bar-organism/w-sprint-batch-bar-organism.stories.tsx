import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import {
    BOARD_PROJECT,
    BOARD_PROJECT_SEM_SPRINT,
    CARDS_MONTAR_SPRINT,
    MONTAR_SPRINT_MARCADAS,
} from '../../../../../.storybook/fixtures-quadro';
import { WSprintBatchBarOrganism } from './w-sprint-batch-bar-organism';

const MARCADAS = CARDS_MONTAR_SPRINT.filter((card) => MONTAR_SPRINT_MARCADAS.includes(card.id));

const meta = {
    title: 'Organisms/Sprint Batch Bar',
    component: WSprintBatchBarOrganism,
    tags: ['autodocs'],
    args: { project: BOARD_PROJECT_SEM_SPRINT, selected: MARCADAS, onCancel: fn(), onConfirm: fn() },
    parameters: { layout: 'fullscreen' },
    decorators: [
        (Story) => (
            <div className="min-h-64 bg-muted">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WSprintBatchBarOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Sem sprint aberta: "3 selecionadas · 52 h previstas (1 sem horas)" e *Iniciar sprint*. */
export const SemSprintAberta: Story = {};
/** Com sprint aberta: *Pôr na Sprint 3*. */
export const ComSprintAberta: Story = { args: { project: BOARD_PROJECT } };
/** Nada marcado ainda: o botão principal fica desabilitado. */
export const NenhumaMarcada: Story = { args: { selected: [] } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
