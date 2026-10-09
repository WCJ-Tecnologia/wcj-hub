import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER, manyPages, onePage, ROLES, USERS } from '../../../../../.storybook/fixtures';
import { WUsersPage } from './w-users-page';

const FILTERS = { search: '', role_id: null, show_inactive: false };

const meta = {
    title: 'Pages/Usuários',
    component: WUsersPage,
    tags: ['autodocs'],
    argTypes: { users: { control: 'object' }, filters: { control: 'object' }, roles: { control: 'object' } },
    args: { users: onePage(USERS.filter((user) => user.active)), filters: FILTERS, roles: ROLES },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/users' } },
} satisfies Meta<typeof WUsersPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Filtrado: Story = { args: { users: onePage(USERS.slice(1, 2)), filters: { ...FILTERS, search: 'bruno', role_id: 3 } } };
export const ComDesativados: Story = { args: { users: onePage(USERS), filters: { ...FILTERS, show_inactive: true } } };
export const ComPaginacao: Story = { args: { users: manyPages(USERS, 4) } };
export const Vazia: Story = { args: { users: onePage([]) } };
export const SemPermissaoDeGerir: Story = { parameters: { inertia: { url: '/admin/users', props: asUser(DEVELOPER) } } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
