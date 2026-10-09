import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';

import { DocumentInput, MoneyInput, PhoneInput } from './masked-input';

interface DemoProps {
    kind: 'money' | 'cpf' | 'cnpj' | 'document' | 'phone';
    initial: string;
}

/** Guarda o valor cru (dígitos ou decimal) e mostra formatado. O valor cru aparece abaixo. */
function MaskedDemo({ kind, initial }: DemoProps) {
    const [value, setValue] = useState(initial);
    const common = { value, onChange: setValue, 'aria-label': 'Campo com máscara' };

    return (
        <div className="w-72 space-y-2">
            {kind === 'money' && <MoneyInput {...common} />}
            {kind === 'phone' && <PhoneInput {...common} />}
            {kind === 'cpf' && <DocumentInput {...common} variant="cpf" />}
            {kind === 'cnpj' && <DocumentInput {...common} variant="cnpj" />}
            {kind === 'document' && <DocumentInput {...common} variant="auto" />}
            <p className="text-xs text-muted-foreground">
                Valor gravado: <code>{JSON.stringify(value)}</code>
            </p>
        </div>
    );
}

const meta = {
    title: 'Shadcn/Masked Input',
    component: MaskedDemo,
    tags: ['autodocs'],
    argTypes: {
        kind: { control: 'select', options: ['money', 'cpf', 'cnpj', 'document', 'phone'] },
        initial: { control: 'text' },
    },
    args: { kind: 'money', initial: '1234.5' },
} satisfies Meta<typeof MaskedDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Dinheiro: Story = {};
export const Telefone: Story = { args: { kind: 'phone', initial: '11987654321' } };
export const CpfOuCnpj: Story = { args: { kind: 'document', initial: '12345678000190' } };
