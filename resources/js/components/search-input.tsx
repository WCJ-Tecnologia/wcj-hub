import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/**
 * Busca de listagem: debounce de 300 ms e navegação Inertia com preserveState +
 * replace (não polui o histórico). `params` leva os outros filtros da tela.
 */
export function SearchInput({
    url,
    value,
    params = {},
    placeholder = 'Buscar…',
    label = 'Buscar',
    className,
}: {
    url: string;
    /** Valor vindo do servidor (`filters.search`). */
    value: string | null | undefined;
    params?: Record<string, string | number | null | undefined>;
    placeholder?: string;
    /** aria-label do campo. */
    label?: string;
    className?: string;
}) {
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
        <div role="search" className={cn('relative', className)}>
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
                <button
                    type="button"
                    onClick={clear}
                    aria-label="Limpar busca"
                    className="absolute top-1/2 right-2 inline-flex size-7 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <X className="size-4" aria-hidden />
                </button>
            )}
        </div>
    );
}
