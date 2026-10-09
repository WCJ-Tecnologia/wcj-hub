import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { AlphanumericCnpjInput } from '@/components/shadcn/masked-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';

/** Espelha App\Support\Paises::BRASIL. */
export const BRASIL = 'BR';

export interface Country {
    code: string;
    name: string;
}

export interface ClientFormData {
    name: string;
    /** ISO 3166-1 alfa-2. */
    country: string;
    /** CNPJ sem máscara no Brasil; identificador fiscal livre fora (N5). */
    document: string;
}

/** O rótulo muda com o país (F1 §02). */
export const documentLabel = (country: string) => (country === BRASIL ? 'CNPJ' : 'Identificador fiscal');

export interface WClientFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O `useForm<ClientFormData>` da página. */
    form: InertiaFormProps<ClientFormData>;
    editing: boolean;
    /** "Ativo" ou "Desativado em …, por …". */
    situation?: string | null;
    countries: Country[];
    onSubmit: (event: FormEvent) => void;
}

/**
 * Modal de criar e editar cliente (F1 §02). O `useForm` vive na página; aqui ficam só os
 * campos. Desativar e reativar são ações da linha: a situação aqui é só leitura.
 */
export function WClientFormDialogOrganism({ open, onOpenChange, form, editing, situation, countries, onSubmit }: WClientFormDialogOrganismProps) {
    const brazil = form.data.country === BRASIL;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-client-form-dialog-organism">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar cliente' : 'Novo cliente'}</DialogTitle>
                    <DialogDescription>
                        Empresa atendida pela WCJ. No Brasil, o CNPJ é obrigatório e aceita letras (CNPJ alfanumérico).
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="client-form" onSubmit={onSubmit} className="space-y-4">
                        <WFormFieldMolecule label="Nome" htmlFor="name" required error={form.errors.name}>
                            <Input value={form.data.name} maxLength={160} onChange={(e) => form.setData('name', e.target.value)} />
                        </WFormFieldMolecule>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <WFormFieldMolecule label="País" htmlFor="country" required error={form.errors.country}>
                                <Select value={form.data.country} onValueChange={(value) => form.setData('country', value)}>
                                    {/* O Root do Radix não é elemento: id e aria vão no trigger, que é o botão focável. */}
                                    <SelectTrigger
                                        id="country"
                                        aria-invalid={Boolean(form.errors.country)}
                                        aria-describedby={form.errors.country ? 'country-error' : undefined}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countries.map((country) => (
                                            <SelectItem key={country.code} value={country.code}>
                                                {country.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </WFormFieldMolecule>

                            {/* Trocar o país mantém o documento: fora do Brasil ele vira identificador fiscal (N5). */}
                            <WFormFieldMolecule
                                label={documentLabel(form.data.country)}
                                htmlFor="document"
                                required={brazil}
                                error={form.errors.document}
                            >
                                {brazil ? (
                                    <AlphanumericCnpjInput value={form.data.document} onChange={(value) => form.setData('document', value)} />
                                ) : (
                                    <Input value={form.data.document} maxLength={30} onChange={(e) => form.setData('document', e.target.value)} />
                                )}
                            </WFormFieldMolecule>
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
                    <Button type="submit" form="client-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Criar cliente'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
