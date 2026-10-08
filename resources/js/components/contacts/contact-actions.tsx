import { router, useForm, usePage } from '@inertiajs/react';
import { EyeOff, History, Pencil, Power, RotateCcw, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { AnonymizeContactDialog } from '@/components/contacts/anonymize-contact-dialog';
import { ContactFormDialog, type ContactFormData } from '@/components/contacts/contact-form-dialog';
import { contactSituation, type ContactRow } from '@/components/contacts/contact-table';
import { RowActions } from '@/components/row-actions';
import type { SharedProps } from '@/types';

const EMPTY: ContactFormData = { name: '', email: '', phone: '' };

/**
 * Criar, editar e as ações de linha do contato, as mesmas na lista geral e na ficha do cliente.
 * Os `useForm` ficam aqui para as duas páginas não repetirem o fluxo; a página só abre o
 * formulário, desenha as ações da linha e põe `dialogs` na tela.
 */
export function useContactActions() {
    const abilities = usePage<SharedProps>().props.auth.user?.abilities;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ContactRow | null>(null);
    const [newFor, setNewFor] = useState<{ id: number; name: string } | null>(null);
    const [deleting, setDeleting] = useState<ContactRow | null>(null);
    const [anonymizing, setAnonymizing] = useState<ContactRow | null>(null);
    const [busy, setBusy] = useState(false);
    const form = useForm<ContactFormData>(EMPTY);
    const anonymizeForm = useForm({ confirmation: '' });

    const openCreate = (client: { id: number; name: string }) => {
        form.setData(EMPTY);
        form.clearErrors();
        setEditing(null);
        setNewFor(client);
        setOpen(true);
    };

    const openEdit = (row: ContactRow) => {
        form.setData({ name: row.name, email: row.email ?? '', phone: row.phone ?? '' });
        form.clearErrors();
        setEditing(row);
        setOpen(true);
    };

    const openAnonymize = (row: ContactRow) => {
        anonymizeForm.reset();
        anonymizeForm.clearErrors();
        setAnonymizing(row);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/admin/contacts/${editing.id}`, options);
        else if (newFor) form.post(`/admin/clients/${newFor.id}/contacts`, options);
    };

    const anonymize = (event: FormEvent) => {
        event.preventDefault();
        if (!anonymizing) return;
        anonymizeForm.post(`/admin/contacts/${anonymizing.id}/anonymize`, { preserveScroll: true, onSuccess: () => setAnonymizing(null) });
    };

    const rowRequest = {
        preserveScroll: true,
        onStart: () => setBusy(true),
        onFinish: () => {
            setBusy(false);
            setDeleting(null);
        },
    };

    // Desativar e reativar não pedem confirmação (F1 §04); o segundo clique é ignorado enquanto a visita roda.
    const rowPost = (url: string) => !busy && router.post(url, {}, rowRequest);

    const rowActions = (row: ContactRow, labeled = false) => (
        <RowActions
            labeled={labeled}
            // Até cinco ações: no desktop, três por linha, para a tabela caber sem rolagem lateral.
            className={labeled ? undefined : 'ml-auto w-30 flex-wrap'}
            actions={[
                { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row), hidden: !row.can.update },
                {
                    label: 'Desativar',
                    ariaLabel: `Desativar ${row.name}`,
                    icon: Power,
                    onClick: () => rowPost(`/admin/contacts/${row.id}/deactivate`),
                    hidden: !row.can.deactivate,
                },
                {
                    label: 'Reativar',
                    ariaLabel: `Reativar ${row.name}`,
                    icon: RotateCcw,
                    onClick: () => rowPost(`/admin/contacts/${row.id}/reactivate`),
                    hidden: !row.can.reactivate,
                },
                {
                    label: 'Anonimizar',
                    ariaLabel: `Anonimizar ${row.name}`,
                    icon: EyeOff,
                    onClick: () => openAnonymize(row),
                    destructive: true,
                    hidden: !row.can.anonymize,
                },
                {
                    label: 'Excluir',
                    ariaLabel: `Excluir ${row.name}`,
                    icon: Trash2,
                    onClick: () => setDeleting(row),
                    destructive: true,
                    hidden: !row.can.delete,
                },
                {
                    label: 'Histórico',
                    ariaLabel: `Histórico de ${row.name}`,
                    icon: History,
                    onClick: () => router.visit(`/admin/audit-logs?type=contact&id=${row.id}`),
                    hidden: !abilities?.view_audit_logs,
                },
            ]}
        />
    );

    const dialogs = (
        <>
            <ContactFormDialog
                open={open}
                onOpenChange={setOpen}
                form={form}
                editing={editing !== null}
                clientName={(editing ? editing.client_name : newFor?.name) ?? ''}
                situation={editing ? contactSituation(editing) : null}
                onSubmit={submit}
            />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(next) => !next && setDeleting(null)}
                title={deleting ? `Excluir ${deleting.name}?` : ''}
                description="Excluir de verdade. Não dá para desfazer."
                confirmLabel="Excluir"
                destructive
                loading={busy}
                onConfirm={() => deleting && router.delete(`/admin/contacts/${deleting.id}`, rowRequest)}
            />

            <AnonymizeContactDialog
                open={anonymizing !== null}
                onOpenChange={(next) => !next && setAnonymizing(null)}
                contactName={anonymizing?.name ?? ''}
                form={anonymizeForm}
                onSubmit={anonymize}
            />
        </>
    );

    return { openCreate, rowActions, dialogs };
}
