import type { Meta, StoryObj } from '@storybook/react-vite';
import { LogOut, UserRound } from 'lucide-react';

import { Button } from './button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from './dropdown-menu';

interface DemoProps {
    label: string;
    withDestructive: boolean;
}

function DropdownDemo({ label, withDestructive }: DemoProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline">Abrir menu</Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-56">
                <DropdownMenuLabel>{label}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem>
                    <UserRound /> Meu perfil
                </DropdownMenuItem>
                {withDestructive && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem variant="destructive">
                            <LogOut /> Sair
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

const meta = {
    title: 'Shadcn/Dropdown Menu',
    component: DropdownDemo,
    tags: ['autodocs'],
    argTypes: { label: { control: 'text' }, withDestructive: { control: 'boolean' } },
    args: { label: 'Ana Souza', withDestructive: true },
} satisfies Meta<typeof DropdownDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
