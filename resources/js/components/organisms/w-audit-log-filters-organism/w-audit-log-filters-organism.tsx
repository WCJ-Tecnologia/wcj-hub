import { router } from '@inertiajs/react';
import { useState } from 'react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { Label } from '@/components/shadcn/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';

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

/** O Radix Select não aceita item com valor vazio: "todos" usa este valor e vira filtro ausente no request. */
const ALL = 'all';

const SELECTS: { key: keyof AuditOptions; param: keyof AuditFilters; label: string; all: string }[] = [
    { key: 'users', param: 'user_id', label: 'Quem', all: 'Todas as pessoas' },
    { key: 'channels', param: 'channel', label: 'Canal', all: 'Todos os canais' },
    { key: 'actions', param: 'action', label: 'Ação', all: 'Todas as ações' },
    { key: 'types', param: 'type', label: 'Tipo de registro', all: 'Todos os tipos' },
    { key: 'outcomes', param: 'outcome', label: 'Resultado', all: 'Todos os resultados' },
];

export interface WAuditLogFiltersOrganismProps {
    filters: AuditFilters;
    options: AuditOptions;
}

/** Período, Quem, Canal, Ação, Tipo de registro, Resultado e a busca pelo registro (F1 §05). */
export function WAuditLogFiltersOrganism({ filters, options }: WAuditLogFiltersOrganismProps) {
    // "De" e "Até" com estado local (CR-1-07 e CR-2-10 do B1): a data escolhida vale na hora, e os
    // selects e a busca levam o período dos campos, não o do servidor. Trocar um select logo depois
    // de escolher a data, com a visita da data ainda em voo, sai com o período novo.
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    // Quando o servidor responde com outro período (Limpar filtros, Histórico de uma ficha), o
    // campo acompanha: ajuste durante o render, sem efeito em cascata (react.dev, "You might not
    // need an effect").
    const [server, setServer] = useState({ from: filters.from, to: filters.to });
    if (server.from !== filters.from || server.to !== filters.to) {
        if (server.from !== filters.from) setFrom(filters.from ?? '');
        if (server.to !== filters.to) setTo(filters.to ?? '');
        setServer({ from: filters.from, to: filters.to });
    }

    // "from" vai sempre, vazio quando apagado (filterParams); "to" vazio sai da query.
    const period = (nextFrom: string, nextTo: string) => ({ from: nextFrom, to: nextTo || undefined });

    const params = { ...filterParams(filters), ...period(from, to) };
    const visit = (next: Record<string, string | number | undefined>) =>
        router.get(AUDIT_URL, { ...params, ...next }, { preserveState: true, replace: true });

    const changeFrom = (value: string | null) => {
        const next = value ?? '';
        setFrom(next);
        visit(period(next, to));
    };

    const changeTo = (value: string | null) => {
        const next = value ?? '';
        setTo(next);
        visit(period(from, next));
    };

    const selected = (param: keyof AuditFilters) => {
        const value = filters[param];
        return value === null || value === '' ? ALL : String(value);
    };

    return (
        <div data-slot="w-audit-log-filters-organism" className="mb-4 space-y-2">
            <div className="grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <WSearchInputMolecule
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
                    <WDatePickerMolecule id="audit-from" value={from || null} onChange={changeFrom} className="flex-1 sm:w-44" />
                </div>
                <div className="flex items-center gap-2">
                    <Label htmlFor="audit-to" className="w-9 shrink-0 sm:w-auto">
                        Até
                    </Label>
                    <WDatePickerMolecule id="audit-to" value={to || null} onChange={changeTo} className="flex-1 sm:w-44" />
                </div>
            </div>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                {SELECTS.map((select) => (
                    <Select
                        key={select.param}
                        value={selected(select.param)}
                        // Trocar o tipo sai do Histórico de uma ficha: o id era daquele tipo.
                        onValueChange={(value) =>
                            visit({ [select.param]: value === ALL ? undefined : value, ...(select.param === 'type' && { id: undefined }) })
                        }
                    >
                        <SelectTrigger aria-label={select.label}>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>{select.all}</SelectItem>
                            {options[select.key].map((option) => (
                                <SelectItem key={option.value} value={String(option.value)}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ))}
            </div>
        </div>
    );
}
