import type { Meta, StoryObj } from '@storybook/react-vite';

import { Button } from './button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from './card';

interface DemoProps {
    title: string;
    description: string;
    content: string;
    withFooter: boolean;
}

function CardDemo({ title, description, content, withFooter }: DemoProps) {
    return (
        <Card className="w-96">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="text-sm">{content}</CardContent>
            {withFooter && (
                <CardFooter>
                    <Button size="sm">Salvar</Button>
                </CardFooter>
            )}
        </Card>
    );
}

const meta = {
    title: 'Shadcn/Card',
    component: CardDemo,
    tags: ['autodocs'],
    argTypes: {
        title: { control: 'text' },
        description: { control: 'text' },
        content: { control: 'text' },
        withFooter: { control: 'boolean' },
    },
    args: {
        title: 'Quem é você',
        description: 'Administrador',
        content: 'Conteúdo do cartão: formulário, lista ou indicador.',
        withFooter: true,
    },
} satisfies Meta<typeof CardDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SemRodape: Story = { args: { withFooter: false } };
