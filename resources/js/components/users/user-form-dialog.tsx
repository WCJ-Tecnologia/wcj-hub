import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DocumentInput, PhoneInput } from '@/components/ui/masked-input';
import { Select } from '@/components/ui/select';

/** Espelha App\Enums\UserTypeEnum. */
export const USER_TYPE = { ADMIN: 1, FINANCIAL: 3 } as const;

export interface UserFormData {
    user_type_id: number;
    name: string;
    email: string;
    document: string;
    phone: string;
    active: boolean;
    enterprise_ids: number[];
}

/**
 * Modal de criar/editar usuário. O `useForm` vive na página (um só,
 * reaproveitado para os dois casos); aqui ficam só os campos.
 */
export function UserFormDialog({
    open,
    onOpenChange,
    form,
    editing,
    deactivatedNote,
    userTypes,
    enterpriseOptions,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    form: InertiaFormProps<UserFormData>;
    editing: boolean;
    /** "Desativado em …, por …": quem e quando, gravados pelo servidor. */
    deactivatedNote?: string | null;
    userTypes: { id: number; label: string }[];
    enterpriseOptions: { id: number; nickname: string }[];
    onSubmit: (event: FormEvent) => void;
}) {
    const isFinancial = form.data.user_type_id === USER_TYPE.FINANCIAL;

    const toggleEnterprise = (id: number) => {
        const current = form.data.enterprise_ids;
        form.setData('enterprise_ids', current.includes(id) ? current.filter((value) => value !== id) : [...current, id]);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
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
                        <FormField label="Perfil" htmlFor="user_type_id" required error={form.errors.user_type_id}>
                            <Select value={form.data.user_type_id} onChange={(e) => form.setData('user_type_id', Number(e.target.value))}>
                                {userTypes.map((type) => (
                                    <option key={type.id} value={type.id}>
                                        {type.label}
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
                            <FormField label="Telefone" htmlFor="phone" error={form.errors.phone}>
                                <PhoneInput value={form.data.phone} onChange={(value) => form.setData('phone', value)} />
                            </FormField>
                        </div>

                        <FormField label="Documento" htmlFor="document" error={form.errors.document} hint="CPF ou CNPJ, opcional.">
                            <DocumentInput value={form.data.document} onChange={(value) => form.setData('document', value)} />
                        </FormField>

                        {isFinancial && (
                            <FormField
                                label="Empresas autorizadas"
                                required
                                error={form.errors.enterprise_ids}
                                hint="O Financeiro só enxerga dados das empresas marcadas."
                            >
                                <div className="scrollbar-slim max-h-44 space-y-1 overflow-y-auto rounded-md border border-border p-2">
                                    {enterpriseOptions.map((enterprise) => (
                                        <div key={enterprise.id} className="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-accent/50">
                                            <Checkbox
                                                id={`enterprise-${enterprise.id}`}
                                                checked={form.data.enterprise_ids.includes(enterprise.id)}
                                                onCheckedChange={() => toggleEnterprise(enterprise.id)}
                                            />
                                            <Label htmlFor={`enterprise-${enterprise.id}`} className="cursor-pointer font-normal">
                                                {enterprise.nickname}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </FormField>
                        )}

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
