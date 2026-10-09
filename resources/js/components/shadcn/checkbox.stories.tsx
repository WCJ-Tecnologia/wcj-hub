import type { Meta, StoryObj } from '@storybook/react-vite';

import { Checkbox } from './checkbox';
import { Label } from './label';

const meta = {
    title: 'Shadcn/Checkbox',
    component: Checkbox,
    tags: ['autodocs'],
    argTypes: {
        checked: { control: 'boolean' },
        disabled: { control: 'boolean' },
    },
    args: { checked: true, disabled: false, id: 'active' },
    render: (args) => (
        <div className="flex items-center gap-2">
            <Checkbox {...args} />
            <Label htmlFor="active">Usuário ativo</Label>
        </div>
    ),
} satisfies Meta<typeof Checkbox>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Marcado: Story = {};
export const Desmarcado: Story = { args: { checked: false } };
export const Desabilitado: Story = { args: { disabled: true } };
