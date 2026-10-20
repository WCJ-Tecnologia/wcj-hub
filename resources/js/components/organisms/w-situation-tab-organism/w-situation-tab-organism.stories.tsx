import type { Meta, StoryObj } from '@storybook/react-vite';

import { SITUATION, SITUATION_VAZIA } from '../../../../../.storybook/fixtures-indicadores';
import { WSituationTabOrganism } from './w-situation-tab-organism';

const meta = {
    title: 'Organisms/Indicadores/Situação',
    component: WSituationTabOrganism,
    tags: ['autodocs'],
    args: { data: SITUATION },
} satisfies Meta<typeof WSituationTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ProjetoEmAndamento: Story = {};
export const ProjetoVazio: Story = { args: { data: SITUATION_VAZIA } };
