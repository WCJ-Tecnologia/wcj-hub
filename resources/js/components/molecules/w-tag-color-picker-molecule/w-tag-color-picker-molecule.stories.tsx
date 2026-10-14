import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import { WTagAtom } from '@/components/atoms/w-tag-atom/w-tag-atom';
import type { TagColor } from '@/types/quadro';

import { WTagColorPickerMolecule, type WTagColorPickerMoleculeProps } from './w-tag-color-picker-molecule';

/** Com estado local e a etiqueta de exemplo mudando junto. */
function PickerDemo(props: WTagColorPickerMoleculeProps) {
    const [value, setValue] = useState<TagColor | null>(props.value);
    return (
        <div className="grid gap-3">
            <WTagColorPickerMolecule
                {...props}
                value={value}
                onChange={(color) => {
                    action('onChange')(color);
                    setValue(color);
                }}
            />
            <WTagAtom label="Melhoria" color={value} kind="type" />
        </div>
    );
}

const meta = {
    title: 'Molecules/Tag Color Picker',
    component: WTagColorPickerMolecule,
    render: (args) => <PickerDemo {...args} />,
    tags: ['autodocs'],
    argTypes: { disabled: { control: 'boolean' }, value: { control: false }, onChange: { control: false } },
    args: { value: 'blue', onChange: () => undefined },
} satisfies Meta<typeof WTagColorPickerMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SemCor: Story = { args: { value: null } };
export const Desabilitado: Story = { args: { disabled: true } };
