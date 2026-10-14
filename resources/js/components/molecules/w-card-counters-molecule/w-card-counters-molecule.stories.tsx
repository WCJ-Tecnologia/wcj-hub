import type { Meta, StoryObj } from '@storybook/react-vite';

import { WCardCountersMolecule } from './w-card-counters-molecule';

const meta = {
    title: 'Molecules/Card Counters',
    component: WCardCountersMolecule,
    tags: ['autodocs'],
    argTypes: {
        dueOn: { control: 'text' },
        attachmentsCount: { control: { type: 'number', min: 0 } },
        commentsCount: { control: { type: 'number', min: 0 } },
        late: { control: 'boolean' },
    },
    args: { dueOn: '2026-10-20', attachmentsCount: 3, commentsCount: 5, late: false },
} satisfies Meta<typeof WCardCountersMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Todos: Story = {};
export const Atrasado: Story = { args: { dueOn: '2026-10-09', late: true } };
export const SoComentarios: Story = { args: { dueOn: null, attachmentsCount: 0, commentsCount: 1 } };
export const SoData: Story = { args: { attachmentsCount: 0, commentsCount: 0 } };
