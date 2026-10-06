import type { LucideIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface RowAction {
    /** Texto visível no celular e, por padrão, aria-label no desktop. */
    label: string;
    /** aria-label específico com o nome do registro ("Editar Loja 3"). */
    ariaLabel?: string;
    icon: LucideIcon;
    onClick: () => void;
    destructive?: boolean;
    /** Ação que a pessoa não pode executar simplesmente não aparece. */
    hidden?: boolean;
}

/**
 * Ações de uma linha de listagem. `labeled=false` (desktop): botões de ícone
 * com aria-label. `labeled=true` (card mobile): botões outline com texto.
 */
export function RowActions({ actions, labeled = false, className }: { actions: RowAction[]; labeled?: boolean; className?: string }) {
    const visible = actions.filter((action) => !action.hidden);
    if (visible.length === 0) return null;

    return (
        <div className={cn(labeled ? 'mt-3 flex flex-wrap gap-2' : 'flex justify-end gap-1', className)}>
            {visible.map((action) => {
                const Icon = action.icon;
                return (
                    <Button
                        key={action.label}
                        variant={labeled ? 'outline' : 'ghost'}
                        size={labeled ? 'sm' : 'icon'}
                        onClick={action.onClick}
                        aria-label={action.ariaLabel ?? action.label}
                    >
                        <Icon className={cn('size-4', action.destructive && 'text-destructive')} aria-hidden />
                        {labeled && action.label}
                    </Button>
                );
            })}
        </div>
    );
}
