import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { Check } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Radix Checkbox. Pareie com <Label htmlFor> ou passe aria-label.
 * `onCheckedChange` recebe boolean | 'indeterminate'.
 */
function Checkbox({ className, ...props }: React.ComponentProps<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            data-slot="checkbox"
            className={cn(
                'peer flex size-4 shrink-0 items-center justify-center rounded-sm border border-input bg-card shadow-sm transition-[color,box-shadow] outline-none',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40',
                'aria-invalid:border-destructive aria-invalid:ring-destructive/30',
                'data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
                'disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator data-slot="checkbox-indicator" className="flex items-center justify-center text-current">
                <Check className="size-3.5" aria-hidden strokeWidth={3} />
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}

export { Checkbox };
