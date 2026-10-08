import type { Meta, StoryObj } from '@storybook/react-vite';

import { WBrandLogoAtom } from './w-brand-logo-atom';

const meta = {
    title: 'Atoms/Brand Logo',
    component: WBrandLogoAtom,
    tags: ['autodocs'],
    argTypes: {
        size: { control: 'inline-radio', options: ['sm', 'md', 'lg'] },
        showText: { control: 'boolean' },
        href: { control: 'text', description: 'Destino do link. Use `false` (aba Code) para renderizar sem link.' },
    },
    args: { size: 'md', showText: true, href: '/admin/dashboard' },
    parameters: { docs: { description: { component: 'Logo e nome da marca, lidos de `app.brand` (APP_BRAND_NAME, APP_BRAND_LOGO).' } } },
} satisfies Meta<typeof WBrandLogoAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComNome: Story = {};
export const SoLogo: Story = { args: { showText: false } };
export const Grande: Story = { args: { size: 'lg' } };
export const SemLink: Story = { args: { href: false } };
