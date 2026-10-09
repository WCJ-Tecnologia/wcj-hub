import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { cn } from '@/lib/utils';

export interface WSearchInputMoleculeProps {
    /** Rota da listagem que recebe `?search=`. */
    url: string;
    /** Valor vindo do servidor (`filters.search`). */
    value: string | null | undefined;
    /** Outros filtros da tela, mantidos na navegação. */
    params?: Record<string, string | number | null | undefined>;
    placeholder?: string;
    /** aria-label do campo. */
    label?: string;
    className?: string;
}

/**
 * Busca de listagem: debounce de 300 ms e navegação Inertia com preserveState +
 * replace (não polui o histórico). `params` leva os outros filtros da tela.
 *
 * Visita síncrona interrompe visita síncrona no Inertia 2: a busca disparada no meio de uma
 * escrita (Reativar, Excluir…) cancelaria a escrita em silêncio (CR-1-10). Por isso, quando uma
 * visita que não é GET começa, a busca pendente espera ela terminar e só então navega.
 */
export function WSearchInputMolecule({
    url,
    value,
    params = {},
    placeholder = 'Buscar…',
    label = 'Buscar',
    className,
}: WSearchInputMoleculeProps) {
    const [text, setText] = useState(value ?? '');
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    /** O termo que o timer vai buscar. */
    const scheduled = useRef('');
    /** Termo guardado enquanto uma escrita roda; vai para a busca quando ela termina. */
    const pending = useRef<string | null>(null);
    const writing = useRef(false);

    const navigate = (search: string) => {
        router.get(url, { ...params, search: search || undefined }, { preserveState: true, replace: true });
    };
    // Os ouvintes do router são registrados uma vez; leem a navegação com url e params atuais.
    const navigateRef = useRef(navigate);
    useEffect(() => {
        navigateRef.current = navigate;
    });

    const go = (search: string) => {
        if (writing.current) pending.current = search;
        else navigate(search);
    };

    const schedule = (next: string) => {
        setText(next);
        if (timer.current) clearTimeout(timer.current);
        scheduled.current = next.trim();
        timer.current = setTimeout(() => {
            timer.current = null;
            go(scheduled.current);
        }, 300);
    };

    const cancelTimer = () => {
        if (timer.current) clearTimeout(timer.current);
        timer.current = null;
    };

    const clear = () => {
        cancelTimer();
        setText('');
        go('');
    };

    useEffect(() => {
        const offs = [
            router.on('before', (event) => {
                if (event.detail.visit.method === 'get' || !timer.current) return;
                cancelTimer();
                pending.current = scheduled.current;
            }),
            router.on('start', (event) => {
                if (event.detail.visit.method !== 'get') writing.current = true;
            }),
            router.on('finish', (event) => {
                if (event.detail.visit.method === 'get') return;
                writing.current = false;
                const search = pending.current;
                pending.current = null;
                if (search !== null) navigateRef.current(search);
            }),
        ];

        return () => {
            offs.forEach((off) => off());
            if (timer.current) clearTimeout(timer.current);
        };
    }, []);

    return (
        <div data-slot="w-search-input-molecule" role="search" className={cn('relative', className)}>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
            <Input
                type="search"
                value={text}
                onChange={(event) => schedule(event.target.value)}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        cancelTimer();
                        go(text.trim());
                    }
                }}
                placeholder={placeholder}
                aria-label={label}
                autoComplete="off"
                className="pr-9 pl-9 [&::-webkit-search-cancel-button]:hidden"
            />
            {text && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={clear}
                    aria-label="Limpar busca"
                    className="absolute top-1/2 right-2 size-7 -translate-y-1/2 text-muted-foreground"
                >
                    <X aria-hidden />
                </Button>
            )}
        </div>
    );
}
