import type { Meta, StoryObj } from '@storybook/react-vite';

import { Label } from './label';
import { Switch } from './switch';

const meta = {
    title: 'Shadcn/Switch',
    component: Switch,
    tags: ['autodocs'],
    argTypes: {
        checked: { control: 'boolean' },
        disabled: { control: 'boolean' },
    },
    args: { checked: false, disabled: false, id: 'notify' },
    render: (args) => (
        <div className="flex items-center gap-2">
            <Switch {...args} />
            <Label htmlFor="notify">Receber avisos por e-mail</Label>
        </div>
    ),
} satisfies Meta<typeof Switch>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Desligado: Story = {};
export const Ligado: Story = { args: { checked: true } };
