import type { InertiaFormProps } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';

/** Espelha AnonymizeContactRequest::WORD. */
export const ANONYMIZE_WORD = 'ANONIMIZAR';

export interface WAnonymizeContactDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    contactName: string;
    /** O `useForm({ confirmation })` de quem abre (o `useContactActions`). */
    form: InertiaFormProps<{ confirmation: string }>;
    onSubmit: (event: FormEvent) => void;
}

/**
 * Anonimizar é irreversível (N12): a pessoa digita a palavra para confirmar (F1 §04). O botão só
 * libera com a palavra exata; o servidor confere de novo.
 */
export function WAnonymizeContactDialogOrganism({ open, onOpenChange, contactName, form, onSubmit }: WAnonymizeContactDialogOrganismProps) {
    const ready = form.data.confirmation === ANONYMIZE_WORD;

    return (
        <Dialog open={open} onOpenChange={(next) => !form.processing && onOpenChange(next)}>
            <DialogContent data-slot="w-anonymize-contact-dialog-organism">
                <DialogHeader>
                    <DialogTitle>Anonimizar {contactName}?</DialogTitle>
                    <DialogDescription className="text-sm text-muted-foreground">
                        Isto atende a um pedido do titular (LGPD) e não pode ser desfeito. Digite ANONIMIZAR para confirmar.
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="anonymize-form" onSubmit={onSubmit} className="space-y-3">
                        <WFormFieldMolecule
                            label="Confirmação"
                            htmlFor="anonymize-confirmation"
                            required
                            error={form.errors.confirmation}
                            hint="O nome vira “removido a pedido do titular”; e-mail e telefone somem, inclusive do histórico."
                        >
                            <Input
                                value={form.data.confirmation}
                                autoComplete="off"
                                spellCheck={false}
                                onChange={(e) => form.setData('confirmation', e.target.value)}
                            />
                        </WFormFieldMolecule>
                    </form>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="anonymize-form" variant="destructive" disabled={!ready || form.processing}>
                        {form.processing && <Loader2 className="size-4 animate-spin" aria-hidden />}
                        Anonimizar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
