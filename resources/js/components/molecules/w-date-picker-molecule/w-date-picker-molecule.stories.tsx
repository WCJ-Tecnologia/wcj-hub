import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';

import { WDatePickerMolecule } from './w-date-picker-molecule';

interface DemoProps {
    initial: string;
    label: string;
    placeholder: string;
    disabled: boolean;
    clearable: boolean;
}

/** Controla o valor como a tela faz; o valor gravado (AAAA-MM-DD) aparece abaixo. */
function DatePickerDemo({ initial, label, placeholder, disabled, clearable }: DemoProps) {
    const [value, setValue] = useState<string | null>(initial || null);

    return (
        <div className="w-64 space-y-2">
            <WDatePickerMolecule
                value={value}
                onChange={setValue}
                label={label}
                placeholder={placeholder}
                disabled={disabled}
                clearable={clearable}
            />
            <p className="text-xs text-muted-foreground">
                Valor enviado: <code>{JSON.stringify(value)}</code>
            </p>
        </div>
    );
}

const meta = {
    title: 'Molecules/Date Picker',
    component: DatePickerDemo,
    tags: ['autodocs'],
    argTypes: {
        initial: { control: 'text', description: 'Valor inicial em AAAA-MM-DD' },
        label: { control: 'text' },
        placeholder: { control: 'text' },
        disabled: { control: 'boolean' },
        clearable: { control: 'boolean' },
    },
    args: { initial: '', label: 'De', placeholder: 'De (dd/mm/aaaa)', disabled: false, clearable: true },
} satisfies Meta<typeof DatePickerDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Vazio: Story = {};
export const ComData: Story = { args: { initial: '2026-10-02' } };
export const Desabilitado: Story = { args: { initial: '2026-10-02', disabled: true } };
