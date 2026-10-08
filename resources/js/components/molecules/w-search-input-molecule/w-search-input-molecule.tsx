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

    const navigate = (search: string) => {
        router.get(url, { ...params, search: search || undefined }, { preserveState: true, replace: true });
    };

    const schedule = (next: string) => {
        setText(next);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(() => navigate(next.trim()), 300);
    };

    const clear = () => {
        if (timer.current) clearTimeout(timer.current);
        setText('');
        navigate('');
    };

    useEffect(() => {
        return () => {
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
                        if (timer.current) clearTimeout(timer.current);
                        navigate(text.trim());
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
