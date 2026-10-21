import type { Meta, StoryObj } from '@storybook/react-vite';

import { HISTORY_MOVEMENTS } from '../../../../../.storybook/fixtures-indicadores';
import { WTaskTimelineOrganism } from './w-task-timeline-organism';

const meta = {
    title: 'Organisms/Indicadores/Linha do tempo',
    component: WTaskTimelineOrganism,
    tags: ['autodocs'],
    args: { movements: HISTORY_MOVEMENTS },
} satisfies Meta<typeof WTaskTimelineOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComRetrabalhoEPausa: Story = {};
export const SoACriacao: Story = { args: { movements: [{ ...HISTORY_MOVEMENTS[0], days: 6, open: true }] } };
