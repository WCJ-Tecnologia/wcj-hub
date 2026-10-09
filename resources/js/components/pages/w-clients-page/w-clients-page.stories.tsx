import type { Meta, StoryObj } from '@storybook/react-vite';

import { CLIENTS, COUNTRIES, onePage } from '../../../../../.storybook/fixtures';
import { WClientsPage } from './w-clients-page';

const FILTERS = { search: '', country: null, show_inactive: false };

const meta = {
    title: 'Pages/Clientes',
    component: WClientsPage,
    tags: ['autodocs'],
    argTypes: { clients: { control: 'object' }, filters: { control: 'object' }, countries: { control: 'object' }, can: { control: 'object' } },
    args: { clients: onePage(CLIENTS.filter((client) => client.active)), filters: FILTERS, countries: COUNTRIES, can: { create: true } },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/clients' } },
} satisfies Meta<typeof WClientsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComDesativados: Story = { args: { clients: onePage(CLIENTS), filters: { ...FILTERS, show_inactive: true } } };
export const SemPoderCriar: Story = { args: { can: { create: false } } };
export const Vazia: Story = { args: { clients: onePage([]) } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
