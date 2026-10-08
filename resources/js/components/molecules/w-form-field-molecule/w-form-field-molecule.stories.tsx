import type { Meta, StoryObj } from '@storybook/react-vite';

import { Input } from '@/components/shadcn/input';

import { WFormFieldMolecule } from './w-form-field-molecule';

const meta = {
    title: 'Molecules/Form Field',
    component: WFormFieldMolecule,
    tags: ['autodocs'],
    argTypes: {
        label: { control: 'text' },
        htmlFor: { control: 'text' },
        error: { control: 'text' },
        hint: { control: 'text' },
        required: { control: 'boolean' },
        children: { control: false },
    },
    args: {
        label: 'E-mail',
        htmlFor: 'email',
        hint: 'A pessoa recebe a senha temporária neste endereço.',
        required: true,
        children: <Input type="email" placeholder="voce@empresa.com" />,
    },
    decorators: [
        (Story) => (
            <div className="w-80">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WFormFieldMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComDica: Story = {};
export const ComErro: Story = { args: { error: 'Este e-mail já está em uso.' } };
export const Opcional: Story = { args: { label: 'Carga diária (horas)', htmlFor: 'daily_hours', required: false, hint: undefined } };
