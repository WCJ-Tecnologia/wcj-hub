import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

/** Espelha App\Platform\Enums\RoleEnum (docs/banco.txt, roles). */
export const ROLE = { ADMIN: 1, PROJECT_MANAGER: 2, DEVELOPER: 3, SALES: 4 } as const;

export interface UserFormData {
    role_id: number;
    name: string;
    email: string;
    /** Carga diária em horas decimais (N3); vazio = sem carga. */
    daily_hours: string;
    /** IA4: a IA desta pessoa altera dados (true) ou só consulta. */
    ai_write_allowed: boolean;
}

/**
 * Modal de convidar e editar usuário (F1 §02). O `useForm` vive na página; aqui ficam só os
 * campos. Desativar e reativar são ações da linha (D3): a situação aqui é só leitura.
 */
export function UserFormDialog({
    open,
    onOpenChange,
    form,
    editing,
    situation,
    roles,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    form: InertiaFormProps<UserFormData>;
    editing: boolean;
    /** "Ativo" ou "Desativado em …, por …": quem e quando, gravados pelo servidor. */
    situation?: string | null;
    roles: { id: number; label: string }[];
    onSubmit: (event: FormEvent) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar usuário' : 'Convidar usuário'}</DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'Para mandar uma senha provisória nova, use Reenviar convite na lista.'
                            : 'A pessoa recebe por e-mail o endereço do Hub, o login e uma senha provisória, que troca no primeiro acesso.'}
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="user-form" onSubmit={onSubmit} className="space-y-4">
                        <FormField label="Papel" htmlFor="role_id" required error={form.errors.role_id}>
                            <Select value={form.data.role_id} onChange={(e) => form.setData('role_id', Number(e.target.value))}>
                                {roles.map((role) => (
                                    <option key={role.id} value={role.id}>
                                        {role.label}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField label="Nome" htmlFor="name" required error={form.errors.name}>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        </FormField>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="E-mail" htmlFor="email" required error={form.errors.email}>
                                <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                            </FormField>
                            <FormField
                                label="Carga diária (horas)"
                                htmlFor="daily_hours"
                                required={form.data.role_id === ROLE.DEVELOPER}
                                error={form.errors.daily_hours}
                            >
                                <Input
                                    type="number"
                                    inputMode="decimal"
                                    min="0.25"
                                    max="24"
                                    // Horas com decimais (F1 §02): 7,3 vale; sem passo fixo (D9 do Lucas).
                                    step="any"
                                    value={form.data.daily_hours}
                                    onChange={(e) => form.setData('daily_hours', e.target.value)}
                                />
                            </FormField>
                        </div>

                        <div>
                            <div className="flex items-center gap-3">
                                <Switch
                                    id="ai_write_allowed"
                                    checked={form.data.ai_write_allowed}
                                    onCheckedChange={(checked) => form.setData('ai_write_allowed', checked)}
                                    aria-describedby="ai_write_allowed-hint"
                                    aria-invalid={Boolean(form.errors.ai_write_allowed)}
                                />
                                <Label htmlFor="ai_write_allowed" className="cursor-pointer">
                                    IA pode escrever
                                </Label>
                            </div>
                            <p id="ai_write_allowed-hint" className="mt-1.5 text-xs text-muted-foreground">
                                Desligado, a IA desta pessoa só consulta. Mudar desconecta as conexões de IA dela, que reconecta.
                            </p>
                            {form.errors.ai_write_allowed && (
                                <p className="mt-1 text-xs font-medium text-destructive" role="alert">
                                    {form.errors.ai_write_allowed}
                                </p>
                            )}
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
                    <Button type="submit" form="user-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Enviar convite'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
