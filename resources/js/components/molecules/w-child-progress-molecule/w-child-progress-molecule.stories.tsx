import type { Meta, StoryObj } from '@storybook/react-vite';

import { WChildProgressMolecule } from './w-child-progress-molecule';

const meta = {
    title: 'Molecules/Child Progress',
    component: WChildProgressMolecule,
    tags: ['autodocs'],
    argTypes: { progress: { control: 'object' } },
    args: { progress: { todo: 2, doing: 1, done: 3 } },
    decorators: [
        (Story) => (
            <div className="w-64">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WChildProgressMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Metade: Story = {};
export const Comecando: Story = { args: { progress: { todo: 4, doing: 0, done: 0 } } };
export const Concluido: Story = { args: { progress: { todo: 0, doing: 0, done: 5 } } };
export const UmDeCada: Story = { args: { progress: { todo: 1, doing: 1, done: 1 } } };
