import { router } from '@inertiajs/react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import type { IndicatorsFilters, IndicatorsOptions } from '@/types/indicadores';

export interface WIndicatorsFiltersOrganismProps {
    projectId: number;
    filters: IndicatorsFilters;
    options: IndicatorsOptions;
    /** `project`: sprint, pessoa, tipo e área (Tempo por etapa, Horas, Por pessoa). `mine`: o prazo (Minhas tarefas e horas). */
    variant: 'project' | 'mine';
}

/** O Radix Select não aceita item com valor vazio. */
const ALL = 'all';

/** Os parâmetros da URL que a aba mantém (F7 §05): só o que vale para ela. */
export function filterParams(filters: IndicatorsFilters, variant: WIndicatorsFiltersOrganismProps['variant']) {
    return variant === 'project'
        ? {
              aba: filters.tab,
              sprint: filters.sprint,
              developer_id: filters.developer_id,
              type_id: filters.type_id,
              area_id: filters.area_id,
          }
        : { aba: filters.tab, due: filters.due, until: filters.until };
}

/**
 * Os filtros das abas com filtro (F7 §05): busca por chave ou título e, nas abas do projeto, a
 * sprint (a em andamento por padrão; cada sprint; Todas; Fora de sprint), a pessoa (dev da tarefa),
 * o tipo e a área; em *Minhas tarefas e horas*, o prazo (atrasadas; vencem até uma data; sem data
 * prevista). Tudo na URL, para dar link; o servidor refaz as contas.
 */
export function WIndicatorsFiltersOrganism({ projectId, filters, options, variant }: WIndicatorsFiltersOrganismProps) {
    const url = `/projetos/${projectId}/indicadores`;
    const params = filterParams(filters, variant);
    const apply = (changes: Record<string, string | number | null | undefined>) =>
        router.get(
            url,
            { search: filters.search || undefined, ...params, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    const fromSelect = (value: string) => (value === ALL ? undefined : value);

    return (
        <div data-slot="w-indicators-filters-organism" className="flex flex-wrap items-end gap-3">
            <WSearchInputMolecule
                url={url}
                value={filters.search}
                params={params}
                placeholder="Chave ou título"
                label="Buscar tarefa"
                className="w-full sm:w-64"
            />

            {variant === 'project' ? (
                <>
                    <Select value={filters.sprint} onValueChange={(value) => apply({ sprint: value })}>
                        <SelectTrigger className="w-full sm:w-56" aria-label="Filtrar por sprint">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {options.sprints.map((sprint) => (
                                <SelectItem key={sprint.id} value={String(sprint.id)}>
                                    {sprint.name}
                                    {sprint.open ? ' (em andamento)' : ''}
                                </SelectItem>
                            ))}
                            <SelectItem value="todas">Todas as sprints</SelectItem>
                            <SelectItem value="fora">Fora de sprint</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.developer_id ? String(filters.developer_id) : ALL}
                        onValueChange={(value) => apply({ developer_id: fromSelect(value) })}
                    >
                        <SelectTrigger className="w-full sm:w-48" aria-label="Filtrar por pessoa">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Todas as pessoas</SelectItem>
                            {options.developers.map((person) => (
                                <SelectItem key={person.id} value={String(person.id)}>
                                    {person.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.type_id ? String(filters.type_id) : ALL}
                        onValueChange={(value) => apply({ type_id: fromSelect(value) })}
                    >
                        <SelectTrigger className="w-full sm:w-44" aria-label="Filtrar por tipo de tarefa">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Todos os tipos</SelectItem>
                            {options.types.map((type) => (
                                <SelectItem key={type.id} value={String(type.id)}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.area_id ? String(filters.area_id) : ALL}
                        onValueChange={(value) => apply({ area_id: fromSelect(value) })}
                    >
                        <SelectTrigger className="w-full sm:w-44" aria-label="Filtrar por área">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Todas as áreas</SelectItem>
                            {options.areas.map((area) => (
                                <SelectItem key={area.id} value={String(area.id)}>
                                    {area.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </>
            ) : (
                <>
                    <Select
                        value={filters.due ?? ALL}
                        onValueChange={(value) => apply({ due: fromSelect(value), until: value === 'until' ? filters.until : undefined })}
                    >
                        <SelectTrigger className="w-full sm:w-48" aria-label="Filtrar por prazo">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Todos os prazos</SelectItem>
                            <SelectItem value="late">Atrasadas</SelectItem>
                            <SelectItem value="until">Vencem até uma data</SelectItem>
                            <SelectItem value="none">Sem data prevista</SelectItem>
                        </SelectContent>
                    </Select>
                    {filters.due === 'until' && (
                        <WDatePickerMolecule
                            value={filters.until}
                            label="Vencem até"
                            placeholder="Até quando"
                            onChange={(value) => apply({ until: value ?? undefined })}
                        />
                    )}
                </>
            )}
        </div>
    );
}
