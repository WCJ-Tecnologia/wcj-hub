import type { Meta, StoryObj } from '@storybook/react-vite';

import { SidebarProvider } from '@/components/shadcn/sidebar';

import { asUser, DEVELOPER, NOTIFICATIONS } from '../../../../../.storybook/fixtures';
import { WAppTopbarOrganism } from './w-app-topbar-organism';

const meta = {
    title: 'Organisms/App Topbar',
    component: WAppTopbarOrganism,
    tags: ['autodocs'],
    parameters: {
        layout: 'fullscreen',
        docs: {
            description: {
                component: 'Topbar do shell: botão do menu, troca de tema, sino de avisos e menu do usuário. Precisa de SidebarProvider.',
            },
        },
    },
    decorators: [
        (Story) => (
            <SidebarProvider className="min-h-0 flex-col">
                <Story />
            </SidebarProvider>
        ),
    ],
} satisfies Meta<typeof WAppTopbarOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const OutroUsuario: Story = { parameters: { inertia: { props: asUser(DEVELOPER) } } };
export const ComAvisos: Story = { parameters: { inertia: { props: { notifications: NOTIFICATIONS } } } };
