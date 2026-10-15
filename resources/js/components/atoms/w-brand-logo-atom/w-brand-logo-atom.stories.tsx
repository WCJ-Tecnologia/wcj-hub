import type { Meta, StoryObj } from '@storybook/react-vite';

import { WBrandLogoAtom } from './w-brand-logo-atom';

const meta = {
    title: 'Atoms/Brand Logo',
    component: WBrandLogoAtom,
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'inline-radio', options: ['full', 'horizontal', 'symbol'] },
        surface: { control: 'inline-radio', options: ['light', 'dark', 'auto'] },
        size: { control: 'inline-radio', options: ['xs', 'sm', 'md', 'lg', 'xl'] },
        href: { control: 'text', description: 'Destino do link. Use `false` (aba Code) para renderizar sem link.' },
    },
    args: { variant: 'horizontal', surface: 'auto', size: 'md', href: '/admin/dashboard' },
    parameters: {
        docs: {
            description: {
                component:
                    'Logo do WCJ Hub nos arquivos oficiais (completo e horizontal do produto; símbolo da WCJ). Versão pela altura (completo ≥ 40 px, horizontal 16–40 px, símbolo < 16 px); `surface` escolhe a cor para o fundo.',
            },
        },
    },
} satisfies Meta<typeof WBrandLogoAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Sobre a página: troca de cor com o tema (barra do Storybook). */
export const HorizontalNaPagina: Story = {};

/** Painel do login: logo completo sobre o ink da sidebar. */
export const CompletoNoEscuro: Story = {
    args: { variant: 'full', surface: 'dark', size: 'xl', href: false },
    decorators: [
        (Story) => (
            <div className="rounded-md bg-sidebar p-6">
                <Story />
            </div>
        ),
    ],
};

/** Sidebar recolhida: só o símbolo. */
export const SimboloNoEscuro: Story = {
    args: { variant: 'symbol', surface: 'dark', size: 'xs' },
    decorators: [
        (Story) => (
            <div className="rounded-md bg-sidebar p-4">
                <Story />
            </div>
        ),
    ],
};

/** Logo completo sobre a página (troca com o tema). */
export const CompletoNaPagina: Story = { args: { variant: 'full', surface: 'auto', size: 'xl', href: false } };
