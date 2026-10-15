import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import type { StageRoleOption, StageRow } from '@/types/configuracao';

export interface StageFormData {
    name: string;
    /** `''` na edição de etapa de papel único: o campo nem vai ao servidor. */
    stage_role_id: number | '';
}

export interface WBoardStageFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O `useForm<StageFormData>` da aba. */
    form: InertiaFormProps<StageFormData>;
    /** A etapa em edição; null = nova. */
    editing: StageRow | null;
    /** Os papéis que podem se repetir (`options.stage_roles`). */
    roles: StageRoleOption[];
    onSubmit: (event: FormEvent) => void;
}

/**
 * Modal de criar e editar etapa (F2 §02, aba Etapas; P11). Na etapa nova, só os papéis que
 * podem se repetir; Fila, Sprint e Suporte mostram o papel só leitura. Nas demais, o papel
 * congela depois que algum card passou pela etapa (P8): o Select fica desabilitado com a dica.
 */
export function WBoardStageFormDialogOrganism({ open, onOpenChange, form, editing, roles, onSubmit }: WBoardStageFormDialogOrganismProps) {
    const singleRole = editing !== null && !editing.removable;
    const roleLocked = editing?.role_locked ?? false;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-board-stage-form-dialog-organism">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar etapa' : 'Nova etapa'}</DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'Os indicadores leem o papel, não o nome: renomear não muda nada no que já foi medido.'
                            : 'A etapa nova entra no fim do quadro; arraste para mudar a posição.'}
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="board-stage-form" onSubmit={onSubmit} className="space-y-4">
                        <WFormFieldMolecule label="Nome" htmlFor="stage-name" required error={form.errors.name}>
                            <Input value={form.data.name} maxLength={60} onChange={(e) => form.setData('name', e.target.value)} />
                        </WFormFieldMolecule>

                        <WFormFieldMolecule
                            label="Papel"
                            htmlFor="stage-role"
                            required={!editing}
                            error={form.errors.stage_role_id}
                            hint={
                                singleRole
                                    ? 'Fila, Sprint e Suporte existem uma vez só por projeto e não trocam de papel.'
                                    : roleLocked
                                      ? 'O papel não muda depois que um card passou pela etapa.'
                                      : 'O que a etapa significa para o sistema e para os indicadores.'
                            }
                        >
                            {singleRole ? (
                                <Input value={editing.role_label} readOnly disabled />
                            ) : (
                                <Select
                                    value={form.data.stage_role_id === '' ? '' : String(form.data.stage_role_id)}
                                    onValueChange={(value) => form.setData('stage_role_id', value === '' ? '' : Number(value))}
                                    disabled={roleLocked}
                                >
                                    <SelectTrigger
                                        id="stage-role"
                                        aria-invalid={Boolean(form.errors.stage_role_id)}
                                        aria-describedby={form.errors.stage_role_id ? 'stage-role-error' : 'stage-role-hint'}
                                    >
                                        <SelectValue placeholder="Escolha o papel" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={String(role.id)}>
                                                {role.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        </WFormFieldMolecule>
                    </form>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="board-stage-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Criar etapa'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
