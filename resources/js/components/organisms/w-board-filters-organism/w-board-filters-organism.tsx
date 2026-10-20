import { ListFilter, Search, X } from 'lucide-react';
import { useId } from 'react';

import { WPriorityMarkAtom } from '@/components/atoms/w-priority-mark-atom/w-priority-mark-atom';
import { WBoardOrderToggleMolecule } from '@/components/molecules/w-board-order-toggle-molecule/w-board-order-toggle-molecule';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Input } from '@/components/shadcn/input';
import { Label } from '@/components/shadcn/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover';
import { tagDotClasses } from '@/lib/tags';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { Switch } from '@/components/shadcn/switch';
import { BOARD_MARK_LABEL, PRIORITIES, hasActiveFilters } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { BoardFilters, BoardMark, BoardOptions, BoardOrder, PrioritySlug, TagColor } from '@/types/quadro';

/** O Radix Select não aceita item com valor vazio: "todos" usa este valor e vira `null` no filtro. */
const ALL = 'all';
const MARKS = Object.keys(BOARD_MARK_LABEL) as BoardMark[];
/** Urgente primeiro, como na ordem do quadro (F3 §03). */
const PRIORITY_OPTIONS: PrioritySlug[] = [...PRIORITIES].reverse();

/** Os filtros zerados, sem mexer em *Mostrar prontos antigos* nem em *Arquivados*. */
export const clearBoardFilters = (filters: BoardFilters): BoardFilters => ({
    ...filters,
    search: '',
    mine: false,
    developer_id: null,
    qa_id: null,
    type_id: null,
    area_id: null,
    priority: null,
    marks: [],
});

export interface WBoardFiltersOrganismProps {
    /** Valor controlado; a página guarda e manda ao servidor. */
    filters: BoardFilters;
    onChange: (filters: BoardFilters) => void;
    options: Pick<BoardOptions, 'members' | 'types' | 'areas'>;
    order: BoardOrder;
    onOrderChange: (order: BoardOrder) => void;
    className?: string;
}

/**
 * A barra acima do quadro (F3 §05): busca por chave ou título, *Só os meus*, o botão *Filtros* (popover com
 * Tipo, Área, Prioridade, Dev, QA, Marcas, *Mostrar prontos antigos* e *Arquivados*, com o número de filtros
 * ligados; decisão de 14/10) e, à direita, *Ordem*. Só devolve o filtro novo em `onChange`: a página filtra
 * (servidor) e lembra a ordem (navegador).
 */
export function WBoardFiltersOrganism({ filters, onChange, options, order, onOrderChange, className }: WBoardFiltersOrganismProps) {
    const id = useId();
    const set = (changes: Partial<BoardFilters>) => onChange({ ...filters, ...changes });
    const fromSelect = (value: string) => (value === ALL ? null : Number(value));
    const toSelect = (value: number | null) => (value === null ? ALL : String(value));

    const toggleMark = (mark: BoardMark, checked: boolean) =>
        set({
            marks: checked
                ? MARKS.filter((item) => item === mark || filters.marks.includes(item))
                : filters.marks.filter((item) => item !== mark),
        });

    type FilterSelect = {
        key: 'developer_id' | 'qa_id' | 'type_id' | 'area_id';
        label: string;
        all: string;
        items: { id: number; name: string; color?: TagColor | null }[];
    };
    // Ordem dos filtros (decisão de 14/10): Tipo, Área, Prioridade, Dev, QA, Marcas.
    const tagSelects: FilterSelect[] = [
        {
            key: 'type_id',
            label: 'Tipo',
            all: 'Todos os tipos',
            items: options.types.map((type) => ({ id: type.id, name: type.label, color: type.color })),
        },
        { key: 'area_id', label: 'Área', all: 'Todas as áreas', items: options.areas },
    ];
    const peopleSelects: FilterSelect[] = [
        { key: 'developer_id', label: 'Dev', all: 'Todos os devs', items: options.members },
        { key: 'qa_id', label: 'QA', all: 'Todos os QAs', items: options.members },
    ];
    // Cada select vira um campo com rótulo dentro do popover (14/10): o filtro saiu da barra para um botão só.
    const renderSelect = (select: FilterSelect) => (
        <div key={select.key} className="grid gap-1.5">
            <Label htmlFor={`${id}-${select.key}`} className="text-xs text-muted-foreground">
                {select.label}
            </Label>
            <Select value={toSelect(filters[select.key])} onValueChange={(value) => set({ [select.key]: fromSelect(value) })}>
                <SelectTrigger id={`${id}-${select.key}`} size="sm" className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>{select.all}</SelectItem>
                    {select.items.map((item) => (
                        <SelectItem key={item.id} value={String(item.id)}>
                            {item.color !== undefined && (
                                <span aria-hidden className={cn('size-2.5 shrink-0 rounded-full', tagDotClasses(item.color))} />
                            )}
                            {item.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );

    // Quantos filtros estão ligados: o número no botão *Filtros*.
    const activeCount =
        [filters.type_id, filters.area_id, filters.priority, filters.developer_id, filters.qa_id].filter((value) => value !== null).length +
        filters.marks.length +
        (filters.old_done ? 1 : 0) +
        (filters.archived ? 1 : 0);

    return (
        <div data-slot="w-board-filters-organism" className={cn('flex flex-wrap items-center gap-x-4 gap-y-2', className)}>
            <div role="search" className="relative min-w-0 flex-1 basis-64">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
                <Input
                    type="search"
                    value={filters.search}
                    onChange={(event) => set({ search: event.target.value })}
                    placeholder="Buscar por chave ou título…"
                    aria-label="Buscar card por chave ou título"
                    autoComplete="off"
                    className="pr-9 pl-9 [&::-webkit-search-cancel-button]:hidden"
                />
                {filters.search && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => set({ search: '' })}
                        aria-label="Limpar busca"
                        className="absolute top-1/2 right-2 size-7 -translate-y-1/2 text-muted-foreground"
                    >
                        <X aria-hidden />
                    </Button>
                )}
            </div>

            <div className="flex items-center gap-2">
                <Switch id={`${id}-mine`} checked={filters.mine} onCheckedChange={(checked) => set({ mine: checked })} />
                <Label htmlFor={`${id}-mine`} className="cursor-pointer">
                    Só os meus
                </Label>
            </div>

            {/* Todos os filtros num botão só (14/10): a barra fica limpa e o número diz quantos estão ligados. */}
            <Popover>
                <PopoverTrigger asChild>
                    <Button type="button" variant="outline" aria-label={activeCount > 0 ? `Filtros, ${activeCount} ligados` : 'Filtros'}>
                        <ListFilter aria-hidden /> Filtros
                        {activeCount > 0 && (
                            <Badge variant="secondary" className="font-mono tabular-nums" aria-hidden>
                                {activeCount}
                            </Badge>
                        )}
                    </Button>
                </PopoverTrigger>
                {/* Rola dentro da altura que sobra na tela: num notebook de 768 px, Arquivados ficava fora do alcance. */}
                <PopoverContent
                    align="end"
                    className="max-h-(--radix-popover-content-available-height) w-80 overflow-y-auto"
                    aria-label="Filtros do quadro"
                >
                    <div className="grid gap-4">
                        <div className="grid gap-3">
                            {tagSelects.map(renderSelect)}
                            <div className="grid gap-1.5">
                                <Label htmlFor={`${id}-priority`} className="text-xs text-muted-foreground">
                                    Prioridade
                                </Label>
                                <Select
                                    value={filters.priority ?? ALL}
                                    onValueChange={(value) => set({ priority: value === ALL ? null : (value as PrioritySlug) })}
                                >
                                    <SelectTrigger id={`${id}-priority`} size="sm" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>Todas as prioridades</SelectItem>
                                        {PRIORITY_OPTIONS.map((priority) => (
                                            <SelectItem key={priority} value={priority}>
                                                <WPriorityMarkAtom priority={priority} variant="chip" />
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {peopleSelects.map(renderSelect)}
                        </div>

                        <fieldset className="grid gap-2.5">
                            <legend className="mb-2 font-mono text-xs tracking-wider text-muted-foreground uppercase">
                                Cards com a marca
                            </legend>
                            {MARKS.map((mark) => (
                                <div key={mark} className="flex items-center gap-2">
                                    <Checkbox
                                        id={`${id}-mark-${mark}`}
                                        checked={filters.marks.includes(mark)}
                                        onCheckedChange={(checked) => toggleMark(mark, checked === true)}
                                    />
                                    <Label htmlFor={`${id}-mark-${mark}`} className="cursor-pointer font-normal">
                                        {BOARD_MARK_LABEL[mark]}
                                    </Label>
                                </div>
                            ))}
                        </fieldset>

                        <div className="grid gap-2.5 border-t border-border pt-3">
                            <div className="flex items-center gap-2">
                                <Switch
                                    id={`${id}-old`}
                                    checked={filters.old_done}
                                    onCheckedChange={(checked) => set({ old_done: checked })}
                                />
                                <Label htmlFor={`${id}-old`} className="cursor-pointer font-normal">
                                    Mostrar prontos antigos
                                </Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Switch
                                    id={`${id}-archived`}
                                    checked={filters.archived}
                                    onCheckedChange={(checked) => set({ archived: checked })}
                                />
                                <Label htmlFor={`${id}-archived`} className="cursor-pointer font-normal">
                                    Arquivados
                                </Label>
                            </div>
                        </div>

                        {hasActiveFilters(filters) && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="justify-start"
                                onClick={() => onChange(clearBoardFilters(filters))}
                            >
                                <X aria-hidden /> Limpar filtros
                            </Button>
                        )}
                    </div>
                </PopoverContent>
            </Popover>

            <WBoardOrderToggleMolecule value={order} onChange={onOrderChange} className="ml-auto" />
        </div>
    );
}
