import type { Meta, StoryObj } from '@storybook/react-vite';

import { MY_TASKS } from '../../../../../.storybook/fixtures-indicadores';
import { WMyTasksTabOrganism } from './w-my-tasks-tab-organism';

const meta = {
    title: 'Organisms/Indicadores/Minhas tarefas e horas',
    component: WMyTasksTabOrganism,
    tags: ['autodocs'],
    args: { data: MY_TASKS },
} satisfies Meta<typeof WMyTasksTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const DevComAtrasada: Story = {};
export const Vazia: Story = { args: { data: { rows: [] } } };
