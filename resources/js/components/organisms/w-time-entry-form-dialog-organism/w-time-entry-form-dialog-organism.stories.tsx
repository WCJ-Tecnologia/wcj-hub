import type { Meta, StoryObj } from '@storybook/react-vite';

import { TIME_ENTRIES, TIME_ENTRY_TASKS } from '../../../../../.storybook/fixtures-horas';
import { WTimeEntryFormDialogOrganism } from './w-time-entry-form-dialog-organism';

const meta = {
    title: 'Organisms/Time Entry Form Dialog',
    component: WTimeEntryFormDialogOrganism,
    tags: ['autodocs'],
    args: { open: true, onOpenChange: () => {}, entry: null, tasks: TIME_ENTRY_TASKS, today: '2026-10-15' },
} satisfies Meta<typeof WTimeEntryFormDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const LancarHoras: Story = {};
export const Corrigir: Story = { args: { entry: TIME_ENTRIES[0] } };
