import { usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types';

export interface WProductWordmarkAtomProps {
    className?: string;
}

/** Product name (APP_NAME), used as a discreet footer in the sidebar. */
export function WProductWordmarkAtom({ className }: WProductWordmarkAtomProps) {
    const { name } = usePage<SharedProps>().props.app;

    return (
        <p data-slot="w-product-wordmark-atom" className={cn('truncate text-2xs font-medium', className)}>
            {name}
        </p>
    );
}
