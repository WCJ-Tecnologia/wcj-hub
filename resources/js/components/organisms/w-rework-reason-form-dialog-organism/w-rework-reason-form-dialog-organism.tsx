import type { InertiaFormProps } from '@inertiajs/react';
import { useId, type FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';

export interface ReworkReasonFormData {
    label: string;
    requires_text: boolean;
}

export interface WReworkReasonFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    form: InertiaFormProps<ReworkReasonFormData>;
    editing: boolean;
    /** O "Outro": não deixa de pedir texto (P40). */
    fixed?: boolean;
    situation?: string | null;
    onSubmit: (event: FormEvent) => void;
}

/**
 * Modal de criar e editar motivo de retrabalho (F2 §02; P40): Nome (até 80, único) e "Pede
 * texto". No "Outro" a caixa fica marcada e desabilitada. Exporta `ReworkReasonFormData`.
 */
export function WReworkReasonFormDialogOrganism({
    open,
    onOpenChange,
    form,
    editing,
    fixed = false,
    situation,
    onSubmit,
}: WReworkReasonFormDialogOrganismProps) {
    const checkboxId = useId();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-rework-reason-form-dialog-organism">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar motivo' : 'Novo motivo'}</DialogTitle>
                    <DialogDescription>
                        Usado em toda volta para desenvolvimento, na reprovação da homologação e pela IA. Movimentos antigos passam a
                        mostrar o nome novo.
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="rework-reason-form" onSubmit={onSubmit} className="space-y-4">
                        <WFormFieldMolecule label="Nome" htmlFor="reason-label" required error={form.errors.label}>
                            <Input value={form.data.label} maxLength={80} onChange={(e) => form.setData('label', e.target.value)} />
                        </WFormFieldMolecule>

                        <div className="flex items-start gap-3">
                            <Checkbox
                                id={checkboxId}
                                checked={form.data.requires_text}
                                disabled={fixed}
                                onCheckedChange={(value) => form.setData('requires_text', value === true)}
                                aria-describedby={`${checkboxId}-hint`}
                            />
                            <div className="space-y-1">
                                <label htmlFor={checkboxId} className="text-sm font-medium">
                                    Pede texto
                                </label>
                                <p id={`${checkboxId}-hint`} className="text-xs text-muted-foreground">
                                    {fixed
                                        ? 'O "Outro" sempre pede texto.'
                                        : 'Ligado: quem escolhe este motivo escreve o que aconteceu, como no "Outro".'}
                                </p>
                                {form.errors.requires_text && (
                                    <p className="text-xs font-medium text-destructive" role="alert">
                                        {form.errors.requires_text}
                                    </p>
                                )}
                            </div>
                        </div>

                        {situation && (
                            <p className="text-sm text-muted-foreground">
                                <span className="font-medium text-foreground">Situação:</span> {situation}
                            </p>
                        )}
                    </form>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="rework-reason-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Criar motivo'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
