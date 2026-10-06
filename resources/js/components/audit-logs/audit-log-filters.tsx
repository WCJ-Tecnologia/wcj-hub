import { router } from '@inertiajs/react';

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
    const params = filterParams(filters);
    const visit = (next: Record<string, string | number | undefined>) =>
        router.get(AUDIT_URL, { ...params, ...next }, { preserveState: true, replace: true });

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
                        value={filters.from ?? ''}
                        onChange={(event) => visit({ from: event.target.value })}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <Label htmlFor="audit-to" className="w-9 shrink-0 sm:w-auto">
                        Até
                    </Label>
                    <Input
                        id="audit-to"
                        type="date"
                        value={filters.to ?? ''}
                        onChange={(event) => visit({ to: event.target.value || undefined })}
                    />
                </div>
            </div>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                {SELECTS.map((select) => (
                    <Select
                        key={select.param}
                        value={String(filters[select.param] ?? '')}
                        // Trocar o tipo sai do Histórico de uma ficha: o id era daquele tipo.
                        onChange={(event) =>
                            visit({ [select.param]: event.target.value || undefined, ...(select.param === 'type' && { id: undefined }) })
                        }
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
