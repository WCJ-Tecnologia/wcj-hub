import type { Meta, StoryObj } from '@storybook/react-vite';

import { WSearchInputMolecule } from './w-search-input-molecule';

const meta = {
    title: 'Molecules/Search Input',
    component: WSearchInputMolecule,
    tags: ['autodocs'],
    argTypes: {
        url: { control: 'text' },
        value: { control: 'text' },
        placeholder: { control: 'text' },
        label: { control: 'text' },
        params: { control: 'object' },
    },
    args: {
        url: '/admin/users',
        value: '',
        placeholder: 'Buscar por nome ou e-mail…',
        label: 'Buscar usuários',
        params: { role_id: null },
    },
    parameters: { docs: { description: { component: 'Digite: após 300 ms a navegação aparece no painel Actions (`router.get`).' } } },
    decorators: [
        (Story) => (
            <div className="w-96">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WSearchInputMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Vazio: Story = {};
export const ComBusca: Story = { args: { value: 'ana' } };
