import type { Meta, StoryObj } from '@storybook/react-vite';

import { WResetPasswordPage } from './w-reset-password-page';

const meta = {
    title: 'Pages/Nova senha',
    component: WResetPasswordPage,
    tags: ['autodocs'],
    argTypes: { email: { control: 'text' }, token: { control: 'text' } },
    args: { email: 'ana@exemplo.wcj.test', token: 'token-de-exemplo' },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/reset-password', props: { auth: { user: null } } } },
} satisfies Meta<typeof WResetPasswordPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
