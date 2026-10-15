import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useId, type FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Card } from '@/components/shadcn/card';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Input } from '@/components/shadcn/input';
import type { ProjectDefaults } from '@/types/configuracao';

export interface WProjectDefaultsTabOrganismProps {
    projectId: number;
    defaults: ProjectDefaults;
}

interface DefaultsFormData {
    require_pause_reason: boolean;
    /** Texto no formulário; o servidor valida inteiro maior que 0. */
    sprint_days: string;
}

/**
 * Aba Padrões (F2 §02, §04; P8, P15, P46): "Pedir motivo na pausa" e "Duração da sprint" em dias
 * úteis. Salvar vale dali para a frente: não apaga motivos já gravados nem mexe na sprint aberta.
 * Só quem gere vê a aba; escreve em `PUT /projetos/{id}/padroes`.
 */
export function WProjectDefaultsTabOrganism({ projectId, defaults }: WProjectDefaultsTabOrganismProps) {
    const checkboxId = useId();
    const form = useForm<DefaultsFormData>({
        require_pause_reason: defaults.require_pause_reason,
        sprint_days: String(defaults.sprint_days),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/projetos/${projectId}/padroes`, { preserveScroll: true });
    };

    return (
        <Card data-slot="w-project-defaults-tab-organism" className="max-w-xl p-6">
            <form onSubmit={submit} className="space-y-6">
                <div className="flex items-start gap-3">
                    <Checkbox
                        id={checkboxId}
                        checked={form.data.require_pause_reason}
                        onCheckedChange={(value) => form.setData('require_pause_reason', value === true)}
                        aria-describedby={`${checkboxId}-hint`}
                    />
                    <div className="space-y-1">
                        <label htmlFor={checkboxId} className="text-sm font-medium">
                            Pedir motivo na pausa
                        </label>
                        <p id={`${checkboxId}-hint`} className="text-xs text-muted-foreground">
                            Ligado: levar o card para uma etapa de papel Pausa pede o motivo. O tempo parado é contado à parte nos dois
                            casos.
                        </p>
                        {form.errors.require_pause_reason && (
                            <p className="text-xs font-medium text-destructive" role="alert">
                                {form.errors.require_pause_reason}
                            </p>
                        )}
                    </div>
                </div>

                <WFormFieldMolecule
                    label="Duração da sprint (dias úteis)"
                    htmlFor="sprint-days"
                    required
                    error={form.errors.sprint_days}
                    hint="Usada para propor o fim da sprint nova. Mudar não altera a sprint aberta."
                >
                    <Input
                        type="number"
                        inputMode="numeric"
                        min={1}
                        step={1}
                        value={form.data.sprint_days}
                        onChange={(e) => form.setData('sprint_days', e.target.value)}
                        className="w-32 tabular-nums"
                    />
                </WFormFieldMolecule>

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={form.processing || !form.isDirty}>
                        {form.processing && <Loader2 className="size-4 animate-spin" aria-hidden />}
                        Salvar padrões
                    </Button>
                    {form.recentlySuccessful && <span className="text-sm text-muted-foreground">Salvo.</span>}
                </div>
            </form>
        </Card>
    );
}
