import * as SwitchPrimitive from '@radix-ui/react-switch';
import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Radix Switch para liga/desliga com efeito imediato. Para opção dentro de um
 * formulário que ainda vai ser salvo, prefira Checkbox.
 */
function Switch({ className, ...props }: React.ComponentProps<typeof SwitchPrimitive.Root>) {
    return (
        <SwitchPrimitive.Root
            data-slot="switch"
            className={cn(
                'peer inline-flex h-5 w-9 shrink-0 items-center rounded-full border border-transparent shadow-sm transition-colors outline-none',
                'focus-visible:ring-[3px] focus-visible:ring-ring/40',
                'data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
                'disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        >
            <SwitchPrimitive.Thumb
                data-slot="switch-thumb"
                className="pointer-events-none block size-4 rounded-full bg-card shadow-sm ring-0 transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0.5"
            />
        </SwitchPrimitive.Root>
    );
}

export { Switch };
