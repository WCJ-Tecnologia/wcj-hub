import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Radix Dialog: focus trap, ESC, retorno de foco e aria garantidos. Centralizado
 * no desktop, bottom-sheet no celular. Sempre inclua DialogTitle; use
 * DialogDescription ou passe aria-describedby={undefined} no Content.
 */
const Dialog = DialogPrimitive.Root;
const DialogTrigger = DialogPrimitive.Trigger;
const DialogPortal = DialogPrimitive.Portal;
const DialogClose = DialogPrimitive.Close;

function DialogOverlay({ className, ...props }: React.ComponentProps<typeof DialogPrimitive.Overlay>) {
    return (
        <DialogPrimitive.Overlay
            data-slot="dialog-overlay"
            className={cn(
                'fixed inset-0 z-50 bg-black/50 backdrop-blur-sm',
                'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0',
                className,
            )}
            {...props}
        />
    );
}

function DialogContent({
    className,
    children,
    size = 'default',
    ...props
}: React.ComponentProps<typeof DialogPrimitive.Content> & { size?: 'default' | 'lg' }) {
    return (
        <DialogPortal>
            <DialogOverlay />
            <DialogPrimitive.Content
                data-slot="dialog-content"
                className={cn(
                    'fixed z-50 flex max-h-[92vh] w-full flex-col border border-border bg-card text-card-foreground shadow-2xl outline-none',
                    // Celular: bottom-sheet.
                    'inset-x-0 bottom-0 rounded-t-2xl',
                    'max-sm:data-[state=closed]:animate-out max-sm:data-[state=closed]:slide-out-to-bottom-8 max-sm:data-[state=open]:animate-in max-sm:data-[state=open]:slide-in-from-bottom-8',
                    // Desktop: centralizado.
                    'sm:inset-x-auto sm:top-1/2 sm:bottom-auto sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-2xl',
                    'sm:data-[state=closed]:animate-out sm:data-[state=closed]:fade-out-0 sm:data-[state=closed]:zoom-out-95 sm:data-[state=open]:animate-in sm:data-[state=open]:fade-in-0 sm:data-[state=open]:zoom-in-95',
                    size === 'lg' ? 'sm:max-w-3xl' : 'sm:max-w-lg',
                    className,
                )}
                {...props}
            >
                {children}
                <DialogPrimitive.Close
                    className="absolute top-3.5 right-3.5 inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    aria-label="Fechar"
                >
                    <X className="size-4" aria-hidden />
                </DialogPrimitive.Close>
            </DialogPrimitive.Content>
        </DialogPortal>
    );
}

function DialogHeader({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="dialog-header"
            className={cn('flex flex-col gap-0.5 border-b border-border px-5 py-4 pr-14', className)}
            {...props}
        />
    );
}

function DialogBody({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="dialog-body" className={cn('scrollbar-slim flex-1 overflow-y-auto px-5 py-4', className)} {...props} />;
}

function DialogFooter({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="dialog-footer"
            className={cn('flex flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-end', className)}
            {...props}
        />
    );
}

function DialogTitle({ className, ...props }: React.ComponentProps<typeof DialogPrimitive.Title>) {
    return <DialogPrimitive.Title data-slot="dialog-title" className={cn('font-heading text-base font-bold', className)} {...props} />;
}

function DialogDescription({ className, ...props }: React.ComponentProps<typeof DialogPrimitive.Description>) {
    return (
        <DialogPrimitive.Description data-slot="dialog-description" className={cn('text-xs text-muted-foreground', className)} {...props} />
    );
}

export {
    Dialog,
    DialogTrigger,
    DialogPortal,
    DialogClose,
    DialogOverlay,
    DialogContent,
    DialogHeader,
    DialogBody,
    DialogFooter,
    DialogTitle,
    DialogDescription,
};
