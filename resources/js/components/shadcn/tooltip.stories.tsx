import type { Meta, StoryObj } from '@storybook/react-vite';
import { Info } from 'lucide-react';

import { Button } from './button';
import { Tooltip, TooltipContent, TooltipTrigger } from './tooltip';

interface DemoProps {
    text: string;
    side: 'top' | 'right' | 'bottom' | 'left';
}

function TooltipDemo({ text, side }: DemoProps) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button variant="outline" size="icon" aria-label="Ajuda">
                    <Info aria-hidden />
                </Button>
            </TooltipTrigger>
            <TooltipContent side={side}>{text}</TooltipContent>
        </Tooltip>
    );
}

const meta = {
    title: 'Shadcn/Tooltip',
    component: TooltipDemo,
    tags: ['autodocs'],
    argTypes: {
        text: { control: 'text' },
        side: { control: 'inline-radio', options: ['top', 'right', 'bottom', 'left'] },
    },
    args: { text: 'Carga diária em horas (N3)', side: 'top' },
} satisfies Meta<typeof TooltipDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
