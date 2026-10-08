import * as SheetPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Painel lateral sobre Radix Dialog (focus trap, ESC, retorno de foco). Use para
 * detalhe ou revisão que não deve esconder a lista atrás. Tela cheia no celular.
 */
const Sheet = SheetPrimitive.Root;
const SheetTrigger = SheetPrimitive.Trigger;
const SheetClose = SheetPrimitive.Close;
const SheetPortal = SheetPrimitive.Portal;

function SheetOverlay({ className, ...props }: React.ComponentProps<typeof SheetPrimitive.Overlay>) {
    return (
        <SheetPrimitive.Overlay
            data-slot="sheet-overlay"
            className={cn(
                'fixed inset-0 z-50 bg-black/50 backdrop-blur-sm',
                'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0',
                className,
            )}
            {...props}
        />
    );
}

const SIDES = {
    right: 'inset-y-0 right-0 h-full w-full border-l sm:max-w-xl data-[state=open]:slide-in-from-right data-[state=closed]:slide-out-to-right',
    left: 'inset-y-0 left-0 h-full w-full border-r sm:max-w-xl data-[state=open]:slide-in-from-left data-[state=closed]:slide-out-to-left',
} as const;

function SheetContent({
    className,
    children,
    side = 'right',
    ...props
}: React.ComponentProps<typeof SheetPrimitive.Content> & { side?: keyof typeof SIDES }) {
    return (
        <SheetPortal>
            <SheetOverlay />
            <SheetPrimitive.Content
                data-slot="sheet-content"
                className={cn(
                    'fixed z-50 flex flex-col border-border bg-card text-card-foreground shadow-2xl outline-none',
                    'data-[state=closed]:animate-out data-[state=closed]:duration-200 data-[state=open]:animate-in data-[state=open]:duration-300',
                    SIDES[side],
                    className,
                )}
                {...props}
            >
                {children}
                <SheetPrimitive.Close
                    className="absolute top-3.5 right-3.5 inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    aria-label="Fechar"
                >
                    <X className="size-4" aria-hidden />
                </SheetPrimitive.Close>
            </SheetPrimitive.Content>
        </SheetPortal>
    );
}

function SheetHeader({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="sheet-header"
            className={cn('flex flex-col gap-0.5 border-b border-border px-5 py-4 pr-14', className)}
            {...props}
        />
    );
}

function SheetBody({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="sheet-body" className={cn('scrollbar-slim flex-1 overflow-y-auto px-5 py-4', className)} {...props} />;
}

function SheetFooter({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="sheet-footer"
            className={cn('flex flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-end', className)}
            {...props}
        />
    );
}

function SheetTitle({ className, ...props }: React.ComponentProps<typeof SheetPrimitive.Title>) {
    return <SheetPrimitive.Title data-slot="sheet-title" className={cn('font-heading text-base font-bold', className)} {...props} />;
}

function SheetDescription({ className, ...props }: React.ComponentProps<typeof SheetPrimitive.Description>) {
    return (
        <SheetPrimitive.Description data-slot="sheet-description" className={cn('text-xs text-muted-foreground', className)} {...props} />
    );
}

export {
    Sheet,
    SheetTrigger,
    SheetClose,
    SheetPortal,
    SheetOverlay,
    SheetContent,
    SheetHeader,
    SheetBody,
    SheetFooter,
    SheetTitle,
    SheetDescription,
};
