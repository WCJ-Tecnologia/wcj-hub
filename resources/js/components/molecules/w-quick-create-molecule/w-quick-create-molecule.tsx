import { Loader2, Plus } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';

import { Input } from '@/components/shadcn/input';
import { cn } from '@/lib/utils';

/** Limite do título do card (P17), o mesmo do diálogo de suporte. */
export const QUICK_CREATE_MAX = 200;

export interface WQuickCreateMoleculeProps {
    /** Título já sem espaços nas pontas. Quem grava é a página (`POST /projetos/{id}/cards`). */
    onCreate: (title: string) => void;
    /** O card anterior ainda está sendo criado: spinner, e o Enter espera. */
    busy?: boolean;
    /** Erro do servidor (ex.: `errors.title`), abaixo do campo. */
    error?: string;
    className?: string;
}

/**
 * O "título + Enter" do topo do Backlog (F3 §01, P17, R4): Enter com texto chama `onCreate`, limpa o
 * campo e deixa o foco nele para o próximo título, como no Trello; Esc limpa.
 */
export function WQuickCreateMolecule({ onCreate, busy = false, error, className }: WQuickCreateMoleculeProps) {
    const [title, setTitle] = useState('');
    const input = useRef<HTMLInputElement>(null);
    const errorId = useId();

    // Se algo tirou o foco enquanto o card era criado, ele volta ao campo quando termina.
    const wasBusy = useRef(busy);
    useEffect(() => {
        if (wasBusy.current && !busy && document.activeElement === document.body) input.current?.focus();
        wasBusy.current = busy;
    }, [busy]);

    return (
        <div data-slot="w-quick-create-molecule" className={cn('grid gap-1', className)}>
            <div className="relative">
                {busy ? (
                    <Loader2
                        className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
                        aria-hidden
                    />
                ) : (
                    <Plus
                        className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden
                    />
                )}
                <Input
                    ref={input}
                    value={title}
                    maxLength={QUICK_CREATE_MAX}
                    // readOnly, não disabled: o campo desabilitado perde o foco, e o próximo título não teria onde ir.
                    readOnly={busy}
                    aria-busy={busy || undefined}
                    aria-label="Título do novo card"
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? errorId : undefined}
                    placeholder="Novo card: título e Enter"
                    autoComplete="off"
                    enterKeyHint="send"
                    className="h-9 bg-card pl-8"
                    onChange={(event) => setTitle(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Escape') {
                            setTitle('');
                            return;
                        }
                        if (event.key !== 'Enter' || event.nativeEvent.isComposing) return;
                        event.preventDefault();
                        const clean = title.trim();
                        if (!clean || busy) return;
                        onCreate(clean);
                        setTitle('');
                    }}
                />
            </div>
            {error && (
                <p id={errorId} role="alert" className="text-xs text-destructive">
                    {error}
                </p>
            )}
        </div>
    );
}
