import type { Meta, StoryObj } from '@storybook/react-vite';

import { WChangePasswordPage } from './w-change-password-page';

const meta = {
    title: 'Pages/Primeiro acesso',
    component: WChangePasswordPage,
    tags: ['autodocs'],
    argTypes: { firstName: { control: 'text' } },
    args: { firstName: 'Bruno' },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/first-access' } },
} satisfies Meta<typeof WChangePasswordPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
