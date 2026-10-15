import type { Meta, StoryObj } from '@storybook/react-vite';

import { SPRINT_ABERTA, SPRINT_CLOSING } from '../../../../../.storybook/fixtures-sprints';
import { WCloseSprintDialogOrganism } from './w-close-sprint-dialog-organism';

const meta = {
    title: 'Organisms/Close Sprint Dialog',
    component: WCloseSprintDialogOrganism,
    tags: ['autodocs'],
    args: { open: true, onOpenChange: () => {}, projectId: 1, sprint: SPRINT_ABERTA, closing: SPRINT_CLOSING, sprintDays: 10 },
} satisfies Meta<typeof WCloseSprintDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComTarefasPendentes: Story = {};
export const TudoPronto: Story = { args: { closing: { ...SPRINT_CLOSING, tasks: [] } } };
