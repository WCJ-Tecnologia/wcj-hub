import { Check } from 'lucide-react';
import { useId } from 'react';

import { TAG_COLOR_LABEL, TAG_COLORS, tagDotClasses } from '@/lib/tags';
import { cn } from '@/lib/utils';
import type { TagColor } from '@/types/quadro';

export interface WTagColorPickerMoleculeProps {
    /** A cor escolhida; null = sem cor. */
    value: TagColor | null;
    onChange: (color: TagColor | null) => void;
    /** Rótulo do grupo: "Cor da etiqueta". */
    label?: string;
    /** Id do grupo, para o `WFormFieldMolecule` ou um `<label>`. */
    id?: string;
    disabled?: boolean;
    className?: string;
}

/**
 * Seletor de cor da etiqueta (tipo na lista geral, F1; área do projeto, F2): a paleta de 10 cores de
 * `lib/tags.ts` mais "Sem cor", como grupo de rádio (setas trocam, Espaço escolhe). Cada bolinha tem o
 * nome da cor para o leitor de tela e no `title`.
 */
export function WTagColorPickerMolecule({
    value,
    onChange,
    label = 'Cor da etiqueta',
    id,
    disabled = false,
    className,
}: WTagColorPickerMoleculeProps) {
    const baseId = useId();
    const groupId = id ?? `${baseId}-group`;
    const options: (TagColor | null)[] = [null, ...TAG_COLORS];
    const selectedIndex = Math.max(0, options.indexOf(value));

    const move = (delta: number) => {
        const next = options[(selectedIndex + delta + options.length) % options.length];
        onChange(next ?? null);
    };

    return (
        <div
            data-slot="w-tag-color-picker-molecule"
            id={groupId}
            role="radiogroup"
            aria-label={label}
            aria-disabled={disabled || undefined}
            className={cn('flex flex-wrap gap-1.5', className)}
            onKeyDown={(event) => {
                if (disabled) return;
                if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                    event.preventDefault();
                    move(1);
                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    move(-1);
                }
            }}
        >
            {options.map((color, index) => {
                const selected = index === selectedIndex;
                const name = color ? TAG_COLOR_LABEL[color] : 'Sem cor';
                return (
                    <button
                        key={color ?? 'none'}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        aria-label={name}
                        title={name}
                        tabIndex={selected ? 0 : -1}
                        disabled={disabled}
                        onClick={() => onChange(color)}
                        className={cn(
                            'grid size-7 place-items-center rounded-md border transition-[box-shadow,transform] outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50',
                            color ? cn('border-transparent', tagDotClasses(color)) : 'border-dashed border-border bg-card',
                            selected && 'ring-2 ring-ring ring-offset-2 ring-offset-background',
                        )}
                    >
                        {selected && <Check className={cn('size-4', color ? 'text-primary-foreground' : 'text-foreground')} aria-hidden />}
                    </button>
                );
            })}
        </div>
    );
}
