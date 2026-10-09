import type { Meta, StoryObj } from '@storybook/react-vite';

import { CLIENTS, CONTACTS } from '../../../../../.storybook/fixtures';
import { WClientShowPage } from './w-client-show-page';

const WCJ = CLIENTS[0];

const meta = {
    title: 'Pages/Ficha do cliente',
    component: WClientShowPage,
    tags: ['autodocs'],
    argTypes: { client: { control: 'object' }, contacts: { control: 'object' }, can_create_contact: { control: 'boolean' } },
    args: { client: WCJ, contacts: CONTACTS.filter((contact) => contact.client_id === WCJ.id), can_create_contact: true },
    parameters: { layout: 'fullscreen', inertia: { url: `/admin/clients/${WCJ.id}` } },
} satisfies Meta<typeof WClientShowPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SemContatos: Story = { args: { contacts: [] } };
export const SemVerContatos: Story = { args: { contacts: null, can_create_contact: false } };
export const Desativado: Story = { args: { client: CLIENTS[2], contacts: [], can_create_contact: false } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
