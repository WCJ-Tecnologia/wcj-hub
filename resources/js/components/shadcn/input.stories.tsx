import type { Meta, StoryObj } from '@storybook/react-vite';

import { Input } from './input';

const meta = {
    title: 'Shadcn/Input',
    component: Input,
    tags: ['autodocs'],
    argTypes: {
        type: { control: 'select', options: ['text', 'email', 'password', 'number', 'search'] },
        placeholder: { control: 'text' },
        disabled: { control: 'boolean' },
        'aria-invalid': { control: 'boolean' },
    },
    args: { type: 'text', placeholder: 'voce@empresa.com', disabled: false, 'aria-label': 'Campo de exemplo' },
    decorators: [
        (Story) => (
            <div className="w-80">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof Input>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Invalido: Story = { args: { 'aria-invalid': true, defaultValue: 'email-invalido' } };
export const Desabilitado: Story = { args: { disabled: true, defaultValue: 'O e-mail da conta não muda por aqui' } };
