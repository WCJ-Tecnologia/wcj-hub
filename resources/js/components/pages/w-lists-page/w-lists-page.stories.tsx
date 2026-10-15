import type { Meta, StoryObj } from '@storybook/react-vite';

import { REWORK_REASONS } from '../../../../../.storybook/fixtures-configuracao';
import { WListsPage } from './w-lists-page';

const meta = {
    title: 'Pages/Listas',
    component: WListsPage,
    tags: ['autodocs'],
    argTypes: { reworkReasons: { control: 'object' } },
    args: { reworkReasons: REWORK_REASONS.filter((reason) => reason.active), filters: { show_inactive: false }, can: { create: true } },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/listas' } },
} satisfies Meta<typeof WListsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComDesativados: Story = { args: { reworkReasons: REWORK_REASONS, filters: { show_inactive: true } } };
export const Vazia: Story = { args: { reworkReasons: [] } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
