import type { Meta, StoryObj } from '@storybook/react-vite';

import { WLoginPage } from './w-login-page';

const meta = {
    title: 'Pages/Login',
    component: WLoginPage,
    tags: ['autodocs'],
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/login', props: { auth: { user: null } } } },
} satisfies Meta<typeof WLoginPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const DepoisDeTrocarASenha: Story = {
    parameters: {
        inertia: { url: '/admin/login', props: { auth: { user: null }, flash: { success: 'Senha alterada. Entre com a senha nova.' } } },
    },
};
