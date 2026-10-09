import type { Meta, StoryObj } from '@storybook/react-vite';

import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/shadcn/sidebar';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import { WAppSidebarOrganism } from './w-app-sidebar-organism';

const meta = {
    title: 'Organisms/App Sidebar',
    component: WAppSidebarOrganism,
    tags: ['autodocs'],
    argTypes: { nav: { control: 'object' } },
    parameters: {
        layout: 'fullscreen',
        inertia: { url: '/admin/users' },
        docs: {
            description: {
                component: 'Menu lateral sobre o Sidebar do shadcn. Precisa de SidebarProvider (o WAppLayoutTemplate já monta).',
            },
        },
    },
    decorators: [
        (Story, context) => (
            <SidebarProvider defaultOpen={(context.parameters.sidebarOpen as boolean | undefined) ?? true}>
                <Story />
                <SidebarInset className="p-4">
                    <SidebarTrigger className="size-9" />
                </SidebarInset>
            </SidebarProvider>
        ),
    ],
} satisfies Meta<typeof WAppSidebarOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Administrador: Story = {};
export const Desenvolvedor: Story = {
    parameters: { inertia: { url: '/admin/dashboard', props: asUser(DEVELOPER) } },
};
export const Recolhido: Story = { parameters: { sidebarOpen: false } };
export const Celular: Story = {
    globals: { viewport: { value: 'mobile2', isRotated: false } },
};
