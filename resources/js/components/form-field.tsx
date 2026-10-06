import { cloneElement, isValidElement, type ReactElement, type ReactNode } from 'react';

import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type ControlProps = { id?: string; 'aria-invalid'?: boolean; 'aria-describedby'?: string };

/**
 * Liga label, controle, erro e dica. Quando `htmlFor` é informado e o filho é
 * um único elemento, injeta nele `id`, `aria-invalid` e `aria-describedby`
 * (apontando para o erro ou para a dica), então a página não repete isso.
 */
export function FormField({
    label,
    htmlFor,
    error,
    hint,
    required,
    className,
    children,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    className?: string;
    children: ReactNode;
}) {
    const messageId = htmlFor ? `${htmlFor}-${error ? 'error' : 'hint'}` : undefined;

    const control =
        htmlFor && isValidElement<ControlProps>(children)
            ? cloneElement(children as ReactElement<ControlProps>, {
                  id: children.props.id ?? htmlFor,
                  'aria-invalid': children.props['aria-invalid'] ?? Boolean(error),
                  'aria-describedby': children.props['aria-describedby'] ?? (error || hint ? messageId : undefined),
              })
            : children;

    return (
        <div className={cn('space-y-1.5', className)}>
            <Label htmlFor={htmlFor}>
                {label}
                {required && (
                    <span className="text-destructive" aria-hidden>
                        *
                    </span>
                )}
            </Label>
            {control}
            {error ? (
                <p id={messageId} className="text-xs font-medium text-destructive" role="alert">
                    {error}
                </p>
            ) : hint ? (
                <p id={messageId} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            ) : null}
        </div>
    );
}
