import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { Label } from '@/components/shadcn/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';

/** Espelha App\Platform\Enums\RoleEnum (docs/banco.txt, roles). */
export const ROLE = { ADMIN: 1, PROJECT_MANAGER: 2, DEVELOPER: 3, SALES: 4 } as const;

export interface UserFormData {
    role_id: number;
    name: string;
    email: string;
    /** Carga diária em horas decimais (N3); vazio = sem carga. */
    daily_hours: string;
    active: boolean;
}

export interface WUserFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O `useForm<UserFormData>` da página. */
    form: InertiaFormProps<UserFormData>;
    /** Muda título, descrição e botão de salvar. */
    editing: boolean;
    /** "Desativado em …, por …": quem e quando, gravados pelo servidor. */
    deactivatedNote?: string | null;
    roles: { id: number; label: string }[];
    onSubmit: (event: FormEvent) => void;
}

/**
 * Modal de criar/editar usuário. O `useForm` vive na página (um só,
 * reaproveitado para os dois casos); aqui ficam só os campos. Ajuste mínimo do
 * B0 (papel e carga diária); a tela completa é do B1.
 */
export function WUserFormDialogOrganism({
    open,
    onOpenChange,
    form,
    editing,
    deactivatedNote,
    roles,
    onSubmit,
}: WUserFormDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-user-form-dialog-organism">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar usuário' : 'Novo usuário'}</DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'Para redefinir a senha, use o botão da chave na lista.'
                            : 'A pessoa recebe uma senha temporária por e-mail e escolhe a definitiva no primeiro acesso.'}
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="user-form" onSubmit={onSubmit} className="space-y-4">
                        <WFormFieldMolecule label="Papel" htmlFor="role_id" required error={form.errors.role_id}>
                            <Select value={String(form.data.role_id)} onValueChange={(value) => form.setData('role_id', Number(value))}>
                                {/* O Root do Radix não é elemento: id e aria vão no trigger, que é o botão focável. */}
                                <SelectTrigger
                                    id="role_id"
                                    aria-invalid={Boolean(form.errors.role_id)}
                                    aria-describedby={form.errors.role_id ? 'role_id-error' : undefined}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((role) => (
                                        <SelectItem key={role.id} value={String(role.id)}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </WFormFieldMolecule>

                        <WFormFieldMolecule label="Nome" htmlFor="name" required error={form.errors.name}>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        </WFormFieldMolecule>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <WFormFieldMolecule label="E-mail" htmlFor="email" required error={form.errors.email}>
                                <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                            </WFormFieldMolecule>
                            <WFormFieldMolecule label="Carga diária (horas)" htmlFor="daily_hours" error={form.errors.daily_hours}>
                                <Input
                                    type="number"
                                    inputMode="decimal"
                                    min="0.25"
                                    max="24"
                                    step="0.25"
                                    value={form.data.daily_hours}
                                    onChange={(e) => form.setData('daily_hours', e.target.value)}
                                />
                            </WFormFieldMolecule>
                        </div>

                        <div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="active"
                                    checked={form.data.active}
                                    onCheckedChange={(checked) => form.setData('active', checked === true)}
                                    aria-describedby={deactivatedNote ? 'active-note' : undefined}
                                />
                                <Label htmlFor="active" className="cursor-pointer">
                                    Usuário ativo
                                </Label>
                            </div>
                            {deactivatedNote && (
                                <p id="active-note" className="mt-1.5 text-xs text-muted-foreground">
                                    {deactivatedNote}
                                </p>
                            )}
                        </div>
                    </form>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="user-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Cadastrar usuário'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
