import { router } from '@inertiajs/react';
import { useEffect, useRef, useState, type KeyboardEvent } from 'react';

import { SearchInput } from '@/components/search-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';

export interface AuditFilters {
    search: string;
    /** "AAAA-MM-DD". Nulo: sem limite (o Histórico de uma ficha, ou "De" apagado). */
    from: string | null;
    to: string | null;
    user_id: number | null;
    /** "screen" ou o slug do assistente (claude, codex…). */
    channel: string | null;
    action: string | null;
    type: string | null;
    id: number | null;
    outcome: string | null;
}

interface Choice {
    value: string | number;
    label: string;
}

export interface AuditOptions {
    users: Choice[];
    channels: Choice[];
    actions: Choice[];
    types: Choice[];
    outcomes: Choice[];
}

export const AUDIT_URL = '/admin/audit-logs';

/**
 * Os filtros na query string. "from" vai sempre, vazio quando apagado: sem ele o servidor volta
 * ao padrão de 7 dias (F1 §05).
 */
export const filterParams = (filters: AuditFilters) => ({
    search: filters.search || undefined,
    from: filters.from ?? '',
    to: filters.to ?? undefined,
    user_id: filters.user_id ?? undefined,
    channel: filters.channel ?? undefined,
    action: filters.action ?? undefined,
    type: filters.type ?? undefined,
    id: filters.id ?? undefined,
    outcome: filters.outcome ?? undefined,
});

const SELECTS: { key: keyof AuditOptions; param: keyof AuditFilters; label: string; all: string }[] = [
    { key: 'users', param: 'user_id', label: 'Quem', all: 'Todas as pessoas' },
    { key: 'channels', param: 'channel', label: 'Canal', all: 'Todos os canais' },
    { key: 'actions', param: 'action', label: 'Ação', all: 'Todas as ações' },
    { key: 'types', param: 'type', label: 'Tipo de registro', all: 'Todos os tipos' },
    { key: 'outcomes', param: 'outcome', label: 'Resultado', all: 'Todos os resultados' },
];

/** Período, Quem, Canal, Ação, Tipo de registro, Resultado e a busca pelo registro (F1 §05). */
export function AuditLogFilters({ filters, options }: { filters: AuditFilters; options: AuditOptions }) {
    // "De" e "Até" com estado local (CR-1-07): cada segmento digitado é uma data válida, e uma
    // visita por tecla devolvia o valor antigo até a resposta chegar. A visita sai 300 ms depois
    // da última mudança (molde do SearchInput), ou na hora ao sair do campo ou com Enter.
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Quando o servidor responde com outro período (Limpar filtros, Histórico de uma ficha), o
    // campo acompanha: ajuste durante o render, sem efeito em cascata (react.dev, "You might not
    // need an effect").
    const [server, setServer] = useState({ from: filters.from, to: filters.to });
    if (server.from !== filters.from || server.to !== filters.to) {
        if (server.from !== filters.from) setFrom(filters.from ?? '');
        if (server.to !== filters.to) setTo(filters.to ?? '');
        setServer({ from: filters.from, to: filters.to });
    }

    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );

    // "from" vai sempre, vazio quando apagado (filterParams); "to" vazio sai da query.
    const period = (nextFrom: string, nextTo: string) => ({ from: nextFrom, to: nextTo || undefined });

    // Os selects e a busca levam o período dos campos, não o do servidor (CR-2-10): trocar um
    // select logo depois de aplicar a data cancelaria a visita da data e sairia com o período antigo.
    const params = { ...filterParams(filters), ...period(from, to) };
    const visit = (next: Record<string, string | number | undefined>) =>
        router.get(AUDIT_URL, { ...params, ...next }, { preserveState: true, replace: true });

    const applyPeriod = (nextFrom: string, nextTo: string) => {
        if (timer.current) clearTimeout(timer.current);
        timer.current = null;
        if (nextFrom === (filters.from ?? '') && nextTo === (filters.to ?? '')) return;
        visit(period(nextFrom, nextTo));
    };

    const schedulePeriod = (nextFrom: string, nextTo: string) => {
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(() => applyPeriod(nextFrom, nextTo), 300);
    };

    const applyOnEnter = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        applyPeriod(from, to);
    };

    return (
        <div className="mb-4 space-y-2">
            <div className="grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <SearchInput
                    url={AUDIT_URL}
                    value={filters.search}
                    params={params}
                    placeholder="Buscar pelo registro (ex.: Maria, WCJ)…"
                    label="Buscar pelo registro"
                />
                <div className="flex items-center gap-2">
                    <Label htmlFor="audit-from" className="w-9 shrink-0 sm:w-auto">
                        De
                    </Label>
                    <Input
                        id="audit-from"
                        type="date"
                        value={from}
                        onChange={(event) => {
                            setFrom(event.target.value);
                            schedulePeriod(event.target.value, to);
                        }}
                        onBlur={() => applyPeriod(from, to)}
                        onKeyDown={applyOnEnter}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <Label htmlFor="audit-to" className="w-9 shrink-0 sm:w-auto">
                        Até
                    </Label>
                    <Input
                        id="audit-to"
                        type="date"
                        value={to}
                        onChange={(event) => {
                            setTo(event.target.value);
                            schedulePeriod(from, event.target.value);
                        }}
                        onBlur={() => applyPeriod(from, to)}
                        onKeyDown={applyOnEnter}
                    />
                </div>
            </div>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                {SELECTS.map((select) => (
                    <Select
                        key={select.param}
                        value={String(filters[select.param] ?? '')}
                        // Trocar o tipo sai do Histórico de uma ficha: o id era daquele tipo.
                        // O período dos campos vai junto: a data que esperava a pausa não sai depois.
                        onChange={(event) => {
                            if (timer.current) clearTimeout(timer.current);
                            timer.current = null;
                            visit({ [select.param]: event.target.value || undefined, ...(select.param === 'type' && { id: undefined }) });
                        }}
                        aria-label={select.label}
                    >
                        <option value="">{select.all}</option>
                        {options[select.key].map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                ))}
            </div>
        </div>
    );
}
