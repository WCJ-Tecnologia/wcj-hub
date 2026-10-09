import type { Meta, StoryObj } from '@storybook/react-vite';

import { WProfilePage } from './w-profile-page';

const meta = {
    title: 'Pages/Meu perfil',
    component: WProfilePage,
    tags: ['autodocs'],
    argTypes: { profile: { control: 'object' } },
    args: { profile: { name: 'Ana Souza', email: 'ana@exemplo.wcj.test', role_label: 'Administrador' } },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/profile' } },
} satisfies Meta<typeof WProfilePage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
