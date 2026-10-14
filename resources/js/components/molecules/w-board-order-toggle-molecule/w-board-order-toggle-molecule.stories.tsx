import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { BoardOrder } from '@/types/quadro';

import { WBoardOrderToggleMolecule, type WBoardOrderToggleMoleculeProps } from './w-board-order-toggle-molecule';

/** Com estado local, para o clique trocar o botão marcado. */
function OrderToggleDemo(props: WBoardOrderToggleMoleculeProps) {
    const [value, setValue] = useState<BoardOrder>(props.value);
    return (
        <WBoardOrderToggleMolecule
            {...props}
            value={value}
            onChange={(next) => {
                action('onChange')(next);
                setValue(next);
            }}
        />
    );
}

const meta = {
    title: 'Molecules/Board Order Toggle',
    component: WBoardOrderToggleMolecule,
    tags: ['autodocs'],
    argTypes: {
        value: { control: 'inline-radio', options: ['priority', 'manual'] },
        onChange: { control: false },
    },
    args: { value: 'priority', onChange: action('onChange') },
    render: (args) => <OrderToggleDemo key={args.value} {...args} />,
} satisfies Meta<typeof WBoardOrderToggleMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Prioridade: Story = {};
export const Manual: Story = { args: { value: 'manual' } };
