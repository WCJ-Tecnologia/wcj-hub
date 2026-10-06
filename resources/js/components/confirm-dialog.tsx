import { Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

/**
 * Confirmação de ação destrutiva ou irreversível. Nunca use o confirm nativo do navegador.
 * Coloque o nome do registro em `description` para a pessoa saber o que está
 * confirmando.
 */
export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    destructive = false,
    loading = false,
    onConfirm,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
    loading?: boolean;
    onConfirm: () => void;
    /** Conteúdo extra abaixo da descrição (ex.: aviso, lista do que será afetado). */
    children?: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            {/* Sem descrição, tira o aria-describedby para o Radix não apontar para um id inexistente. */}
            <DialogContent {...(description ? {} : { 'aria-describedby': undefined })}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && <DialogDescription className="text-sm text-muted-foreground">{description}</DialogDescription>}
                </DialogHeader>
                {children && <DialogBody>{children}</DialogBody>}
                <DialogFooter className={children ? undefined : 'border-t-0 pt-0'}>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
                        {cancelLabel}
                    </Button>
                    <Button variant={destructive ? 'destructive' : 'default'} onClick={onConfirm} disabled={loading}>
                        {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
