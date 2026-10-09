import { router } from '@inertiajs/react';

import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import type { NamedOption, ProjectType } from '@/components/organisms/w-project-form-dialog-organism/w-project-form-dialog-organism';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Label } from '@/components/shadcn/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';

export interface ProjectFilters {
    search: string;
    client_id: number | null;
    manager_id: number | null;
    type: ProjectType | null;
    finished: boolean;
}

export interface WProjectFiltersOrganismProps {
    filters: ProjectFilters;
    /** Clientes e gestores dos projetos que a pessoa vê. */
    clients: NamedOption[];
    managers: NamedOption[];
}

/** O Radix Select não aceita item com valor vazio: "todos" usa este valor e vira filtro ausente no request. */
const ALL = 'all';

/** Busca e filtros da lista de projetos (F2 §05): abertos por padrão; encerrados pelo filtro (P7). */
export function WProjectFiltersOrganism({ filters, clients, managers }: WProjectFiltersOrganismProps) {
    const params = {
        client_id: filters.client_id,
        manager_id: filters.manager_id,
        type: filters.type,
        finished: filters.finished ? 1 : undefined,
    };

    const apply = (changes: Record<string, string | number | null | undefined>) =>
        router.get('/projetos', { search: filters.search || undefined, ...params, ...changes }, { preserveState: true, replace: true });

    const fromSelect = (value: string) => (value === ALL ? undefined : value);

    return (
        <div data-slot="w-project-filters-organism" className="mb-4 space-y-3">
            <div className="grid gap-2 md:grid-cols-[1fr_repeat(3,minmax(0,12rem))]">
                <WSearchInputMolecule
                    url="/projetos"
                    value={filters.search}
                    params={params}
                    placeholder="Buscar por prefixo, nome ou cliente…"
                    label="Buscar projetos"
                />
                <Select value={filters.client_id ? String(filters.client_id) : ALL} onValueChange={(value) => apply({ client_id: fromSelect(value) })}>
                    <SelectTrigger aria-label="Cliente">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos os clientes</SelectItem>
                        {clients.map((client) => (
                            <SelectItem key={client.id} value={String(client.id)}>
                                {client.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select value={filters.manager_id ? String(filters.manager_id) : ALL} onValueChange={(value) => apply({ manager_id: fromSelect(value) })}>
                    <SelectTrigger aria-label="Gestor">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos os gestores</SelectItem>
                        {managers.map((manager) => (
                            <SelectItem key={manager.id} value={String(manager.id)}>
                                {manager.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select value={filters.type ?? ALL} onValueChange={(value) => apply({ type: fromSelect(value) })}>
                    <SelectTrigger aria-label="Tipo">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos os tipos</SelectItem>
                        <SelectItem value="delivery">Projeto de entrega</SelectItem>
                        <SelectItem value="continuous">Produto contínuo</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="flex items-center gap-2">
                <Checkbox
                    id="show-finished"
                    checked={filters.finished}
                    onCheckedChange={(checked) => apply({ finished: checked === true ? 1 : undefined })}
                />
                <Label htmlFor="show-finished" className="cursor-pointer">
                    Mostrar encerrados
                </Label>
            </div>
        </div>
    );
}
