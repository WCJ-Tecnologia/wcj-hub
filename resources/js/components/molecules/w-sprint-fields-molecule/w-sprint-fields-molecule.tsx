import { useId, useState } from 'react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Input } from '@/components/shadcn/input';
import { Textarea } from '@/components/shadcn/textarea';
import { addBusinessDays, businessDaysBetween } from '@/lib/dias-uteis';
import type { SprintFields } from '@/types/sprints';

export const SPRINT_NAME_MAX = 80;
export const SPRINT_GOAL_MAX = 500;

/** Os erros que a tela mostra antes de mandar (o servidor confere de novo, F5 §07). */
export function sprintFieldErrors(value: SprintFields): Partial<Record<keyof SprintFields, string>> {
    const errors: Partial<Record<keyof SprintFields, string>> = {};
    if (value.name.trim() === '') errors.name = 'Informe o nome.';
    if (value.starts_on === '') errors.starts_on = 'Informe o início.';
    if (value.ends_on === '') errors.ends_on = 'Informe o fim.';
    else if (value.starts_on !== '' && value.ends_on < value.starts_on) errors.ends_on = 'O fim não pode ser antes do início';
    return errors;
}

export interface WSprintFieldsMoleculeProps {
    value: SprintFields;
    onChange: (value: SprintFields) => void;
    /** Duração padrão do projeto em dias úteis (P46): o fim acompanha o início enquanto a pessoa não mexe nele. */
    sprintDays: number;
    /** Fim acompanha o início. Na edição de uma sprint que já existe, `false`. */
    followStart?: boolean;
    /** Erros do servidor por campo (`name`, `goal`, `starts_on`, `ends_on`). */
    errors?: Partial<Record<keyof SprintFields, string>>;
    /** Já tentou mandar: mostra os obrigatórios vazios. */
    tried?: boolean;
    disabled?: boolean;
}

/**
 * Os campos da sprint (F5 §02): nome, objetivo, início, fim e os dias úteis só leitura, com o
 * mesmo visual do `WNewSprintDialogOrganism` do kit do quadro. Usado em *Nova sprint* e *Editar
 * sprint* da tela de sprints e na próxima sprint do diálogo de encerrar. As contas de dia útil
 * são as do `lib/dias-uteis.ts`.
 */
export function WSprintFieldsMolecule({
    value,
    onChange,
    sprintDays,
    followStart = true,
    errors,
    tried = false,
    disabled = false,
}: WSprintFieldsMoleculeProps) {
    const baseId = useId();
    const [endTouched, setEndTouched] = useState(!followStart);
    const local = sprintFieldErrors(value);
    const endBeforeStart = value.starts_on !== '' && value.ends_on !== '' && value.ends_on < value.starts_on;

    const errorOf = (key: keyof SprintFields) => errors?.[key] ?? (tried || (key === 'ends_on' && endBeforeStart) ? local[key] : undefined);
    const businessDays = value.starts_on && value.ends_on && !endBeforeStart ? businessDaysBetween(value.starts_on, value.ends_on) : null;

    const changeStart = (next: string | null) => {
        const start = next ?? '';
        onChange({ ...value, starts_on: start, ends_on: !endTouched && start ? addBusinessDays(start, sprintDays) : value.ends_on });
    };

    const changeEnd = (next: string | null) => {
        setEndTouched(true);
        onChange({ ...value, ends_on: next ?? '' });
    };

    const ids = { name: `${baseId}-name`, goal: `${baseId}-goal`, start: `${baseId}-start`, end: `${baseId}-end`, days: `${baseId}-days` };

    return (
        <div data-slot="w-sprint-fields-molecule" className="space-y-4">
            <WFormFieldMolecule label="Nome" htmlFor={ids.name} required error={errorOf('name')}>
                <Input
                    value={value.name}
                    maxLength={SPRINT_NAME_MAX}
                    disabled={disabled}
                    onChange={(e) => onChange({ ...value, name: e.target.value })}
                />
            </WFormFieldMolecule>

            <WFormFieldMolecule
                label="Objetivo"
                htmlFor={ids.goal}
                error={errors?.goal}
                hint={`Opcional, até ${SPRINT_GOAL_MAX} caracteres.`}
            >
                <Textarea
                    value={value.goal}
                    maxLength={SPRINT_GOAL_MAX}
                    disabled={disabled}
                    onChange={(e) => onChange({ ...value, goal: e.target.value })}
                />
            </WFormFieldMolecule>

            <div className="grid gap-4 sm:grid-cols-2">
                <WFormFieldMolecule label="Início" htmlFor={ids.start} required error={errorOf('starts_on')}>
                    <WDatePickerMolecule value={value.starts_on || null} clearable={false} disabled={disabled} onChange={changeStart} />
                </WFormFieldMolecule>
                <WFormFieldMolecule
                    label="Fim"
                    htmlFor={ids.end}
                    required
                    error={errorOf('ends_on')}
                    hint={followStart ? `Proposta: ${sprintDays} dias úteis, contando o início.` : undefined}
                >
                    <WDatePickerMolecule value={value.ends_on || null} clearable={false} disabled={disabled} onChange={changeEnd} />
                </WFormFieldMolecule>
            </div>

            <WFormFieldMolecule
                label="Dias úteis"
                htmlFor={ids.days}
                hint="De segunda a sexta, entre o início e o fim, inclusive. Feriado não conta."
            >
                <Input value={businessDays ?? '—'} readOnly className="w-24 bg-muted font-mono tabular-nums" />
            </WFormFieldMolecule>
        </div>
    );
}
