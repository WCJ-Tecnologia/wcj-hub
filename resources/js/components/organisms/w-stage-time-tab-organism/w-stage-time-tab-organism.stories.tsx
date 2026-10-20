import type { Meta, StoryObj } from '@storybook/react-vite';

import { STAGE_TIME } from '../../../../../.storybook/fixtures-indicadores';
import { WStageTimeTabOrganism } from './w-stage-time-tab-organism';

const meta = {
    title: 'Organisms/Indicadores/Tempo por etapa',
    component: WStageTimeTabOrganism,
    tags: ['autodocs'],
    args: { data: STAGE_TIME },
} satisfies Meta<typeof WStageTimeTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const SprintAberta: Story = {};
export const Vazia: Story = { args: { data: { rows: [] } } };
