import { Calendar, MessageSquare, Paperclip } from 'lucide-react';

import { cn, dateBr } from '@/lib/utils';

export interface WCardCountersMoleculeProps {
    /** Data prevista `AAAA-MM-DD` (P28); null não mostra. */
    dueOn: string | null;
    attachmentsCount: number;
    commentsCount: number;
    /** Passou da data prevista (P53): a data fica no vermelho de status. A marca "Atrasado" vem à parte. */
    late?: boolean;
    className?: string;
}

/** Contadores do rodapé do card (F3 §03): data prevista, anexos e comentários, cada um só quando há. */
export function WCardCountersMolecule({ dueOn, attachmentsCount, commentsCount, late = false, className }: WCardCountersMoleculeProps) {
    if (!dueOn && attachmentsCount <= 0 && commentsCount <= 0) return null;

    return (
        <ul
            data-slot="w-card-counters-molecule"
            className={cn('flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground', className)}
        >
            {dueOn && (
                <li className={cn('inline-flex items-center gap-1', late && 'font-medium text-status-rejected')} title="Data prevista">
                    <Calendar className="size-3.5" aria-hidden />
                    <span className="sr-only">Data prevista:</span>
                    <span className="font-mono tabular-nums">{dateBr(dueOn)}</span>
                </li>
            )}
            {attachmentsCount > 0 && (
                <li className="inline-flex items-center gap-1" title="Anexos">
                    <Paperclip className="size-3.5" aria-hidden />
                    <span className="font-mono tabular-nums">{attachmentsCount}</span>
                    <span className="sr-only">{attachmentsCount === 1 ? 'anexo' : 'anexos'}</span>
                </li>
            )}
            {commentsCount > 0 && (
                <li className="inline-flex items-center gap-1" title="Comentários">
                    <MessageSquare className="size-3.5" aria-hidden />
                    <span className="font-mono tabular-nums">{commentsCount}</span>
                    <span className="sr-only">{commentsCount === 1 ? 'comentário' : 'comentários'}</span>
                </li>
            )}
        </ul>
    );
}
