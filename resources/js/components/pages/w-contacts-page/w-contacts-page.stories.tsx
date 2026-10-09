import type { Meta, StoryObj } from '@storybook/react-vite';

import { CLIENTS, CONTACTS, onePage } from '../../../../../.storybook/fixtures';
import { WContactsPage } from './w-contacts-page';

const FILTERS = { search: '', client_id: null, show_inactive: false };
const CLIENT_CHOICES = CLIENTS.map(({ id, name }) => ({ id, name }));

const meta = {
    title: 'Pages/Contatos',
    component: WContactsPage,
    tags: ['autodocs'],
    argTypes: { contacts: { control: 'object' }, filters: { control: 'object' }, clients: { control: 'object' } },
    args: { contacts: onePage(CONTACTS.filter((contact) => contact.active)), filters: FILTERS, clients: CLIENT_CHOICES },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/contacts' } },
} satisfies Meta<typeof WContactsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComDesativadosEAnonimizado: Story = { args: { contacts: onePage(CONTACTS), filters: { ...FILTERS, show_inactive: true } } };
export const Vazia: Story = { args: { contacts: onePage([]) } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
