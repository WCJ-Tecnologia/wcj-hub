import { router } from '@inertiajs/react';

import type { NamedOption, ProjectType } from '@/components/projects/project-form-dialog';
import { SearchInput } from '@/components/search-input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';

export interface ProjectFilters {
    search: string;
    client_id: number | null;
    manager_id: number | null;
    type: ProjectType | null;
    finished: boolean;
}

/** Busca e filtros da lista de projetos (F2 §05): abertos por padrão; encerrados pelo filtro (P7). */
export function ProjectFiltersBar({
    filters,
    clients,
    managers,
}: {
    filters: ProjectFilters;
    clients: NamedOption[];
    managers: NamedOption[];
}) {
    const params = {
        client_id: filters.client_id,
        manager_id: filters.manager_id,
        type: filters.type,
        finished: filters.finished ? 1 : undefined,
    };

    const apply = (changes: Record<string, string | number | null | undefined>) =>
        router.get('/projetos', { search: filters.search || undefined, ...params, ...changes }, { preserveState: true, replace: true });

    return (
        <div className="mb-4 space-y-3">
            <div className="grid gap-2 md:grid-cols-[1fr_repeat(3,minmax(0,12rem))]">
                <SearchInput
                    url="/projetos"
                    value={filters.search}
                    params={params}
                    placeholder="Buscar por prefixo, nome ou cliente…"
                    label="Buscar projetos"
                />
                <Select
                    value={filters.client_id ?? ''}
                    onChange={(e) => apply({ client_id: e.target.value || undefined })}
                    aria-label="Cliente"
                >
                    <option value="">Todos os clientes</option>
                    {clients.map((client) => (
                        <option key={client.id} value={client.id}>
                            {client.name}
                        </option>
                    ))}
                </Select>
                <Select
                    value={filters.manager_id ?? ''}
                    onChange={(e) => apply({ manager_id: e.target.value || undefined })}
                    aria-label="Gestor"
                >
                    <option value="">Todos os gestores</option>
                    {managers.map((manager) => (
                        <option key={manager.id} value={manager.id}>
                            {manager.name}
                        </option>
                    ))}
                </Select>
                <Select value={filters.type ?? ''} onChange={(e) => apply({ type: e.target.value || undefined })} aria-label="Tipo">
                    <option value="">Todos os tipos</option>
                    <option value="delivery">Projeto de entrega</option>
                    <option value="continuous">Produto contínuo</option>
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
