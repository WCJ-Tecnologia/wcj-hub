import type { Meta, StoryObj } from '@storybook/react-vite';

import { WForgotPasswordPage } from './w-forgot-password-page';

const meta = {
    title: 'Pages/Esqueci minha senha',
    component: WForgotPasswordPage,
    tags: ['autodocs'],
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/forgot-password', props: { auth: { user: null } } } },
} satisfies Meta<typeof WForgotPasswordPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const LinkEnviado: Story = {
    parameters: {
        inertia: {
            url: '/admin/forgot-password',
            props: { auth: { user: null }, flash: { success: 'Se o e-mail existir, o link chega em instantes.' } },
        },
    },
};
