import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import { STAGES } from '../../../../../.storybook/fixtures-configuracao';
import { WRemoveBoardStageDialogOrganism } from './w-remove-board-stage-dialog-organism';

const meta = {
    title: 'Organisms/Remover etapa',
    component: WRemoveBoardStageDialogOrganism,
    tags: ['autodocs'],
    args: {
        open: true,
        onOpenChange: fn(),
        stage: STAGES[6],
        destinations: STAGES.filter((stage) => stage.id !== 7),
        loading: false,
        onConfirm: fn(),
    },
    parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof WRemoveBoardStageDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComCards: Story = {};
export const SemCards: Story = { args: { stage: STAGES[3] } };
export const ErroDoServidor: Story = { args: { error: 'Escolha outra etapa ativa deste projeto.' } };
