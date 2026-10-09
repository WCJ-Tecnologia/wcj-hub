import type { Meta, StoryObj } from '@storybook/react-vite';

import { Button } from './button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from './dialog';

interface DemoProps {
    title: string;
    description: string;
    size: 'default' | 'lg';
    defaultOpen: boolean;
}

/** Centralizado no desktop, bottom-sheet no celular. Sempre com DialogTitle. */
function DialogDemo({ title, description, size, defaultOpen }: DemoProps) {
    return (
        <Dialog defaultOpen={defaultOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">Abrir modal</Button>
            </DialogTrigger>
            <DialogContent size={size}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <p className="text-sm">O corpo rola sozinho quando o conteúdo passa da altura da tela.</p>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline">Cancelar</Button>
                    <Button>Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

const meta = {
    title: 'Shadcn/Dialog',
    component: DialogDemo,
    tags: ['autodocs'],
    argTypes: {
        title: { control: 'text' },
        description: { control: 'text' },
        size: { control: 'inline-radio', options: ['default', 'lg'] },
        defaultOpen: { control: 'boolean' },
    },
    args: { title: 'Novo usuário', description: 'A pessoa recebe uma senha temporária por e-mail.', size: 'default', defaultOpen: false },
} satisfies Meta<typeof DialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Largo: Story = { args: { size: 'lg', title: 'Criado · user #4' } };
