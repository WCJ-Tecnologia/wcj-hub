import type { Meta, StoryObj } from '@storybook/react-vite';

import { SELECTED_SPRINT } from '../../../../../.storybook/fixtures-sprints';
import { WCapacityPanelOrganism } from './w-capacity-panel-organism';

const meta = {
    title: 'Organisms/Capacity Panel',
    component: WCapacityPanelOrganism,
    tags: ['autodocs'],
    args: { capacity: SELECTED_SPRINT.capacity! },
} satisfies Meta<typeof WCapacityPanelOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComAcimaDaCapacidade: Story = {};
export const Vazio: Story = {
    args: { capacity: { business_days: 10, developers: [], without_developer: { tasks: 0, hours: 0 }, without_hours: { tasks: 0 } } },
};
