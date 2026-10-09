import type { Meta, StoryObj } from '@storybook/react-vite';

import { WProductWordmarkAtom } from './w-product-wordmark-atom';

const meta = {
    title: 'Atoms/Product Wordmark',
    component: WProductWordmarkAtom,
    tags: ['autodocs'],
    argTypes: { className: { control: 'text' } },
    args: { className: 'text-muted-foreground' },
    parameters: { docs: { description: { component: 'Nome do produto (APP_NAME), discreto, no rodapé do menu lateral.' } } },
} satisfies Meta<typeof WProductWordmarkAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
