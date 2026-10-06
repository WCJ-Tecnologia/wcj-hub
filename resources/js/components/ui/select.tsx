import { ChevronDown } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Native select on purpose: on mobile it opens the OS picker, which is faster
 * and more accessible than any custom listbox.
 */
function Select({ className, children, ...props }: React.ComponentProps<'select'>) {
    return (
        <div className="relative">
            <select
                data-slot="select"
                className={cn(
                    'flex h-10 w-full appearance-none rounded-md border border-input bg-card px-3 pr-9 text-base shadow-sm transition-[color,box-shadow] outline-none md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40',
                    'aria-invalid:border-destructive aria-invalid:ring-destructive/30',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
            <ChevronDown className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground" />
        </div>
    );
}

export { Select };
