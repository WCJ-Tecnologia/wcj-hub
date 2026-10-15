import type { Meta, StoryObj } from '@storybook/react-vite';

import { SPRINT_ABERTA, SPRINT_PROPOSTA } from '../../../../../.storybook/fixtures-sprints';
import { WSprintFormDialogOrganism } from './w-sprint-form-dialog-organism';

const meta = {
    title: 'Organisms/Sprint Form Dialog',
    component: WSprintFormDialogOrganism,
    tags: ['autodocs'],
    args: { open: true, onOpenChange: () => {}, projectId: 1, sprintDays: 10, sprint: null, proposal: SPRINT_PROPOSTA },
} satisfies Meta<typeof WSprintFormDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const NovaSprint: Story = {};
export const Editar: Story = { args: { sprint: SPRINT_ABERTA, proposal: null } };
