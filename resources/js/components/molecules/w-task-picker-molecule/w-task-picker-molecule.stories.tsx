import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { TaskRef } from '@/types/quadro';

import { PICK_CARDS } from '../../../../../.storybook/fixtures-ficha';
import { WTaskPickerMolecule, type WTaskPickerMoleculeProps } from './w-task-picker-molecule';

/** Com estado local: escolher troca a vista para "chave + título + Trocar/Limpar". */
function TaskPickerDemo(props: WTaskPickerMoleculeProps) {
    const [value, setValue] = useState<TaskRef | null>(props.value);
    return (
        <WTaskPickerMolecule
            {...props}
            value={value}
            onChange={(card) => {
                action('onChange')(card);
                setValue(card);
            }}
        />
    );
}

const meta = {
    title: 'Molecules/Task Picker',
    component: WTaskPickerMolecule,
    render: (args) => <TaskPickerDemo {...args} />,
    tags: ['autodocs'],
    argTypes: {
        mode: { control: 'radio', options: ['parent', 'origin'] },
        disabled: { control: 'boolean' },
        error: { control: 'text' },
        cards: { control: false },
        value: { control: false },
        onChange: { control: false },
    },
    args: { cards: PICK_CARDS, mode: 'parent', value: null, label: 'Card pai', onChange: () => undefined },
    decorators: [
        (Story) => (
            <div className="w-[28rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskPickerMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Digite "sgm-1" ou "portal": o SGM-10 (já é pai) e o SGM-20 (já é filho) aparecem desabilitados com o motivo (P57). */
export const CardPai: Story = {};
/** Tarefa de origem (P25): qualquer card do projeto. */
export const TarefaDeOrigem: Story = { args: { mode: 'origin', label: 'Tarefa de origem' } };
export const ComValor: Story = {
    args: { value: { id: 10, key: 'SGM-10', title: 'Portal do cidadão: 2ª via de tributos', url: '/projetos/1/cards/SGM-10' } },
};
export const SoLeitura: Story = { args: { ...ComValor.args, disabled: true } };
export const ComErro: Story = { args: { error: 'SGM-10 já é pai de outros cards; um nível só.' } };
