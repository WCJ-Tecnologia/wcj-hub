import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';

export interface AreaFormData {
    name: string;
}

export interface WProjectAreaFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    form: InertiaFormProps<AreaFormData>;
    editing: boolean;
    /** "Ativa" ou "Desativada em …, por …", na edição. */
    situation?: string | null;
    onSubmit: (event: FormEvent) => void;
}

/**
 * Modal de criar e editar área do projeto (F2 §02, aba Áreas; P18): só o nome, único no projeto
 * sem diferença de acento e caixa (o servidor recusa com "Já existe a área X neste projeto").
 * A cor da etiqueta (decisão de 14/10) entra com a coluna `project_areas.color`, no B3.
 */
export function WProjectAreaFormDialogOrganism({
    open,
    onOpenChange,
    form,
    editing,
    situation,
    onSubmit,
}: WProjectAreaFormDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-project-area-form-dialog-organism">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar área' : 'Nova área'}</DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'O nome novo aparece em todos os cards da área.'
                            : 'Área do produto, para etiquetar os cards deste projeto. Ex.: Admin, Âncora, Fornecedores.'}
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="project-area-form" onSubmit={onSubmit} className="space-y-4">
                        <WFormFieldMolecule label="Nome" htmlFor="area-name" required error={form.errors.name}>
                            <Input value={form.data.name} maxLength={60} onChange={(e) => form.setData('name', e.target.value)} />
                        </WFormFieldMolecule>
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
                    <Button type="submit" form="project-area-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Criar área'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
