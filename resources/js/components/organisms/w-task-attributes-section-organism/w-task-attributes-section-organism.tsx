import { type ReactNode, useId } from 'react';

import { WPriorityMarkAtom } from '@/components/atoms/w-priority-mark-atom/w-priority-mark-atom';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { dateTimeBr } from '@/lib/ficha';
import { PRIORITIES, PRIORITY_LABEL } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { AreaRef, PrioritySlug, SupportRequest, TaskPatchPayload, TaskTypeRef } from '@/types/quadro';

/** Valor do Select para "sem área": o Radix não aceita `''` como valor de item. */
const NO_AREA = 'none';

/** "Melhoria · Portal do cidadão · Alta"; sem área, "Melhoria · Alta". */
export function attributesSummary(type: TaskTypeRef, area: AreaRef | null, priority: PrioritySlug): string {
    return [type.label, area?.name, PRIORITY_LABEL[priority]].filter(Boolean).join(' · ');
}

export interface WTaskAttributesSectionOrganismProps {
    type: TaskTypeRef;
    area: AreaRef | null;
    priority: PrioritySlug;
    /** Card de suporte (P52): quem pediu, por onde e quando; null nos demais. Só leitura aqui. */
    support: SupportRequest | null;
    /** Listas gerais ativas (P18): `options.types`, `options.areas`. */
    types: TaskTypeRef[];
    areas: AreaRef[];
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `type_id`, `area_id`, `priority`. */
    errors?: Record<string, string>;
    onChange: (changes: Pick<TaskPatchPayload, 'type_id' | 'area_id' | 'priority'>) => void;
    className?: string;
}

/**
 * Tipo, área e prioridade da ficha (F3 §02, P18, P19) e, no card de suporte, o pedido do cliente
 * (P52, só leitura). Cada troca devolve só o campo trocado por `onChange`; quem grava é a página.
 */
export function WTaskAttributesSectionOrganism({
    type,
    area,
    priority,
    support,
    types,
    areas,
    canEdit,
    busy = false,
    errors,
    onChange,
    className,
}: WTaskAttributesSectionOrganismProps) {
    const baseId = useId();
    const fieldId = (name: string) => `${baseId}-${name}`;
    const describedBy = (name: string) => (errors?.[name] ? `${fieldId(name)}-error` : undefined);

    // Tipo desativado some da escolha e continua no card que já o usa (P18, G7).
    const typeOptions = types.some((item) => item.id === type.id) ? types : [{ ...type, label: `${type.label} (desativado)` }, ...types];
    // A área que saiu da lista do projeto continua no card até alguém trocar.
    const areaOptions = !area || areas.some((item) => item.id === area.id) ? areas : [area, ...areas];
    const noAreas = areaOptions.length === 0;

    return (
        <div data-slot="w-task-attributes-section-organism" aria-busy={busy || undefined} className={cn('grid gap-4', className)}>
            {canEdit ? (
                <div className="grid gap-3 sm:grid-cols-3">
                    <WFormFieldMolecule label="Tipo" htmlFor={fieldId('type_id')} error={errors?.type_id}>
                        <Select
                            value={String(type.id)}
                            disabled={busy}
                            onValueChange={(value) => Number(value) !== type.id && onChange({ type_id: Number(value) })}
                        >
                            {/* O Root do Radix não é elemento: id e aria vão no trigger, que é o botão focável. */}
                            <SelectTrigger
                                id={fieldId('type_id')}
                                aria-invalid={Boolean(errors?.type_id)}
                                aria-describedby={describedBy('type_id')}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {typeOptions.map((item) => (
                                    <SelectItem key={item.id} value={String(item.id)}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </WFormFieldMolecule>

                    <WFormFieldMolecule label="Área" htmlFor={fieldId('area_id')} error={errors?.area_id}>
                        <Select
                            value={noAreas ? undefined : area ? String(area.id) : NO_AREA}
                            disabled={busy || noAreas}
                            onValueChange={(value) => {
                                const next = value === NO_AREA ? null : Number(value);
                                if (next !== (area?.id ?? null)) onChange({ area_id: next });
                            }}
                        >
                            <SelectTrigger
                                id={fieldId('area_id')}
                                aria-invalid={Boolean(errors?.area_id)}
                                aria-describedby={describedBy('area_id')}
                            >
                                <SelectValue placeholder="Nenhuma área no projeto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NO_AREA}>Nenhuma</SelectItem>
                                {areaOptions.map((item) => (
                                    <SelectItem key={item.id} value={String(item.id)}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </WFormFieldMolecule>

                    <WFormFieldMolecule label="Prioridade" htmlFor={fieldId('priority')} error={errors?.priority}>
                        <Select
                            value={priority}
                            disabled={busy}
                            onValueChange={(value) => value !== priority && onChange({ priority: value as PrioritySlug })}
                        >
                            <SelectTrigger
                                id={fieldId('priority')}
                                aria-invalid={Boolean(errors?.priority)}
                                aria-describedby={describedBy('priority')}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {PRIORITIES.map((item) => (
                                    <SelectItem key={item} value={item}>
                                        <WPriorityMarkAtom priority={item} variant="chip" />
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </WFormFieldMolecule>
                </div>
            ) : (
                <dl className="grid gap-3 sm:grid-cols-3">
                    <ReadOnlyItem label="Tipo">{type.label}</ReadOnlyItem>
                    <ReadOnlyItem label="Área">{area?.name ?? 'Nenhuma'}</ReadOnlyItem>
                    <ReadOnlyItem label="Prioridade">
                        <WPriorityMarkAtom priority={priority} variant="chip" />
                    </ReadOnlyItem>
                </dl>
            )}

            {support && (
                <div className="grid gap-2 rounded-md border border-border bg-muted/40 p-3">
                    <h3 className="font-mono text-xs tracking-wider text-muted-foreground uppercase">Pedido do cliente</h3>
                    <dl className="grid gap-3 sm:grid-cols-3">
                        <ReadOnlyItem label="Quem pediu">{support.requester}</ReadOnlyItem>
                        <ReadOnlyItem label="Por onde">{support.channel}</ReadOnlyItem>
                        <ReadOnlyItem label="Quando">
                            <span className="font-mono tabular-nums">{dateTimeBr(support.requested_at)}</span>
                        </ReadOnlyItem>
                    </dl>
                </div>
            )}
        </div>
    );
}

function ReadOnlyItem({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid gap-1">
            <dt className="text-sm font-medium">{label}</dt>
            <dd className="text-sm">{children}</dd>
        </div>
    );
}
