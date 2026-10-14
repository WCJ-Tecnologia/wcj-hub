import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';

export interface WTaskKeyAtomProps {
    /** A chave do card, `SGM-17` (P16). `key` é reservada no React, por isso `taskKey`. */
    taskKey: string;
    /** Com `href`, a chave vira link para a ficha (azul de texto); sem, é texto discreto. */
    href?: string;
    /** Título do card de destino, no `title` do link (ex.: o card pai). */
    title?: string;
    size?: 'sm' | 'default';
    className?: string;
}

const SIZES = { sm: 'text-xs', default: 'text-sm' } as const;

/** A chave do card (`SGM-17`) em Space Mono: link para a ficha ou só texto (P16). */
export function WTaskKeyAtom({ taskKey, href, title, size = 'default', className }: WTaskKeyAtomProps) {
    const classes = cn('font-mono font-medium tracking-tight whitespace-nowrap tabular-nums', SIZES[size], className);

    if (href) {
        return (
            <Link
                data-slot="w-task-key-atom"
                href={href}
                title={title}
                className={cn(
                    classes,
                    'rounded-sm text-primary-text underline-offset-2 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50',
                )}
            >
                {taskKey}
            </Link>
        );
    }

    return (
        <span data-slot="w-task-key-atom" title={title} className={cn(classes, 'text-muted-foreground')}>
            {taskKey}
        </span>
    );
}
