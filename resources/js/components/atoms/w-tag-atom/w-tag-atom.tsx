import { tagClasses } from '@/lib/tags';
import { cn } from '@/lib/utils';
import type { TagColor } from '@/types/quadro';

export interface WTagAtomProps {
    /** O texto da etiqueta: o tipo ("Melhoria") ou a área ("Financeiro"). */
    label: string;
    /** Cor da paleta; null = cinza neutro. */
    color: TagColor | null;
    /** O que a etiqueta é, só para o leitor de tela: "Tipo: Melhoria". */
    kind?: 'type' | 'area';
    size?: 'sm' | 'default';
    className?: string;
}

const KIND_PREFIX = { type: 'Tipo: ', area: 'Área: ' } as const;

/**
 * A etiqueta colorida do card (F3 §03): tipo e área, com a cor escolhida no cadastro (decisão de
 * 14/10, como no Trello). A cor nunca diz nada sozinha: o texto está sempre na etiqueta.
 */
export function WTagAtom({ label, color, kind, size = 'default', className }: WTagAtomProps) {
    return (
        <span
            data-slot="w-tag-atom"
            data-color={color ?? 'none'}
            className={cn(
                'inline-flex max-w-full items-center rounded-md border border-transparent font-medium whitespace-nowrap',
                size === 'sm' ? 'px-1.5 py-px text-2xs' : 'px-2 py-0.5 text-xs',
                tagClasses(color),
                className,
            )}
        >
            {kind && <span className="sr-only">{KIND_PREFIX[kind]}</span>}
            <span className="truncate">{label}</span>
        </span>
    );
}
