import type { Meta, StoryObj } from '@storybook/react-vite';

import { STAGE_ROLES, STAGES } from '../../../../../.storybook/fixtures-configuracao';
import { WBoardStagesTabOrganism } from './w-board-stages-tab-organism';

const meta = {
    title: 'Organisms/Etapas do quadro',
    component: WBoardStagesTabOrganism,
    tags: ['autodocs'],
    args: { projectId: 1, stages: STAGES, roles: STAGE_ROLES, canManage: true },
    parameters: { layout: 'padded', inertia: { url: '/projetos/1/configuracao?aba=etapas' } },
} satisfies Meta<typeof WBoardStagesTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Gestor: Story = {};
export const SoLeitura: Story = { args: { canManage: false, roles: [] } };
