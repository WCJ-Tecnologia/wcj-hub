import type { Meta, StoryObj } from '@storybook/react-vite';

import { PRIORITIES } from '@/lib/quadro';

import { WPriorityMarkAtom } from './w-priority-mark-atom';

const meta = {
    title: 'Atoms/Priority Mark',
    component: WPriorityMarkAtom,
    tags: ['autodocs'],
    argTypes: {
        priority: { control: 'select', options: PRIORITIES },
        variant: { control: 'inline-radio', options: ['bar', 'chip'] },
    },
    args: { priority: 'high', variant: 'bar' },
    decorators: [
        (Story, { args }) =>
            args.variant === 'chip' ? (
                <Story />
            ) : (
                <div className="relative w-56 overflow-hidden rounded-md border border-border bg-card py-3 pr-3 pl-4 text-sm">
                    <Story />
                    Card de exemplo
                </div>
            ),
    ],
} satisfies Meta<typeof WPriorityMarkAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Faixa: Story = {};
export const Chip: Story = { args: { variant: 'chip' } };

/** As quatro, em faixa e em chip. */
export const Todas: Story = {
    args: { variant: 'chip' },
    argTypes: { priority: { control: false }, variant: { control: false } },
    render: () => <TodasDemo />,
};

function TodasDemo() {
    return (
        <div className="grid gap-3">
            {PRIORITIES.map((priority) => (
                <div key={priority} className="flex items-center gap-4">
                    <div className="relative w-40 overflow-hidden rounded-md border border-border bg-card py-3 pr-3 pl-4 text-sm">
                        <WPriorityMarkAtom priority={priority} />
                        Faixa
                    </div>
                    <WPriorityMarkAtom priority={priority} variant="chip" className="text-sm" />
                </div>
            ))}
        </div>
    );
}
