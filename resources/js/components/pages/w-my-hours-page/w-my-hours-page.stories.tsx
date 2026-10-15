import type { Meta, StoryObj } from '@storybook/react-vite';

import {
    TIME_ENTRIES,
    TIME_ENTRY_FILTERS,
    TIME_ENTRY_PROJECTS,
    TIME_ENTRY_TASKS,
    TIME_ENTRY_TOTALS,
} from '../../../../../.storybook/fixtures-horas';
import { WMyHoursPage } from './w-my-hours-page';

const meta = {
    title: 'Pages/Minhas horas',
    component: WMyHoursPage,
    tags: ['autodocs'],
    args: {
        entries: TIME_ENTRIES,
        totals: TIME_ENTRY_TOTALS,
        projects: TIME_ENTRY_PROJECTS,
        tasks: TIME_ENTRY_TASKS,
        filters: TIME_ENTRY_FILTERS,
        today: '2026-10-15',
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/minhas-horas' } },
} satisfies Meta<typeof WMyHoursPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const SemanaAtual: Story = {};
export const Vazia: Story = { args: { entries: [], totals: { days: [], total: 0 } } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
