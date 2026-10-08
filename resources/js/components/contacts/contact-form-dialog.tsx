import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

export interface ContactFormData {
    name: string;
    email: string;
    /** Texto livre, gravado como a pessoa digitou (F1 §02, D3). */
    phone: string;
}

/**
 * Modal de criar e editar contato (F1 §02), o mesmo na lista geral e na ficha do cliente. O
 * cliente é fixo: o de onde se abriu. Desativar e anonimizar são ações da linha.
 */
export function ContactFormDialog({
    open,
    onOpenChange,
    form,
    editing,
    clientName,
    situation,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    form: InertiaFormProps<ContactFormData>;
    editing: boolean;
    clientName: string;
    /** "Ativo" ou "Desativado em …, por …". */
    situation?: string | null;
    onSubmit: (event: FormEvent) => void;
}) {
    // O cliente não é campo do formulário: a recusa (cliente desativado) chega fora dos campos.
    const clientError = (form.errors as Record<string, string | undefined>).client;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar contato' : 'Novo contato'}</DialogTitle>
                    <DialogDescription>Pessoa do cliente com quem a equipe fala. E-mail e telefone são opcionais.</DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="contact-form" onSubmit={onSubmit} className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            <span className="font-medium text-foreground">Cliente:</span> {clientName}
                        </p>
                        {clientError && (
                            <p role="alert" className="text-sm font-medium text-destructive">
                                {clientError}
                            </p>
                        )}

                        <FormField label="Nome" htmlFor="contact-name" required error={form.errors.name}>
                            <Input value={form.data.name} maxLength={120} onChange={(e) => form.setData('name', e.target.value)} />
                        </FormField>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="E-mail" htmlFor="contact-email" error={form.errors.email}>
                                <Input
                                    type="email"
                                    value={form.data.email}
                                    maxLength={160}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                />
                            </FormField>

                            {/* Texto livre, sem máscara do Brasil: o cliente pode ser de fora (N5) e o "+" fica. */}
                            <FormField label="Telefone" htmlFor="contact-phone" error={form.errors.phone}>
                                <Input
                                    type="tel"
                                    inputMode="tel"
                                    value={form.data.phone}
                                    maxLength={20}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                />
                            </FormField>
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
                    <Button type="submit" form="contact-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Criar contato'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
