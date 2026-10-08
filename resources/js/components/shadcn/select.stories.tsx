import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './select';

const ROLES = ['Administrador', 'Gestor de projetos', 'Desenvolvedor', 'Comercial'];

interface DemoProps {
    placeholder: string;
    disabled: boolean;
    invalid: boolean;
    size: 'default' | 'sm';
}

/** Radix Select: lista com o visual do tema, teclado e ARIA. "Todos" usa um valor sentinela ('all'), nunca ''. */
function SelectDemo({ placeholder, disabled, invalid, size }: DemoProps) {
    const [value, setValue] = useState<string>('');

    return (
        <div className="w-64">
            <Select value={value} onValueChange={setValue} disabled={disabled}>
                <SelectTrigger aria-label="Papel" aria-invalid={invalid} size={size}>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos os papéis</SelectItem>
                    {ROLES.map((role) => (
                        <SelectItem key={role} value={role}>
                            {role}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

const meta = {
    title: 'Shadcn/Select',
    component: SelectDemo,
    tags: ['autodocs'],
    argTypes: {
        placeholder: { control: 'text' },
        disabled: { control: 'boolean' },
        invalid: { control: 'boolean' },
        size: { control: 'inline-radio', options: ['default', 'sm'] },
    },
    args: { placeholder: 'Escolha um papel', disabled: false, invalid: false, size: 'default' },
} satisfies Meta<typeof SelectDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Invalido: Story = { args: { invalid: true } };
export const Desabilitado: Story = { args: { disabled: true } };
