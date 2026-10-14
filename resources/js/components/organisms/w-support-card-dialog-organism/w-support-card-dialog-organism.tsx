import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { PRIORITIES, PRIORITY_LABEL } from '@/lib/quadro';
import type { BoardOptions, PrioritySlug } from '@/types/quadro';

export const SUPPORT_TITLE_MAX = 200;

export interface SupportCardPayload {
    title: string;
    requester_contact_id: number;
    contact_channel_id: number;
    /** Quando o pedido chegou, `AAAA-MM-DD HH:MM` no fuso de São Paulo (P52). */
    requested_at: string;
    priority: PrioritySlug;
}

export interface WSupportCardDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Contatos ativos do cliente do projeto (P52); vazio troca o campo por um aviso e trava o botão. */
    contacts: BoardOptions['contacts'];
    /** Por onde o pedido chegou (`contact_channels`). */
    channels: BoardOptions['channels'];
    /** "Agora", proposto em *Quando*: `date` em `AAAA-MM-DD` e `time` em `HH:MM`. A tela passa, para a story ser fixa. */
    now: { date: string; time: string };
    /** Nome da coluna de suporte no texto; padrão `Support` (P10). */
    stageName?: string;
    /** Desabilita, mostra o spinner no botão e impede fechar. */
    loading?: boolean;
    /** Erros do servidor por campo (`title`, `requester_contact_id`, `contact_channel_id`, `requested_at`, `priority`). */
    errors?: Record<string, string>;
    onConfirm: (payload: SupportCardPayload) => void;
}

/**
 * Diálogo *Novo card de suporte* (F3 §02, P52): o gestor registra o pedido que o cliente
 * fez direto à WCJ. O card nasce na coluna de suporte, tipo Suporte, fora da sprint.
 */
export function WSupportCardDialogOrganism({ open, onOpenChange, loading = false, ...props }: WSupportCardDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-support-card-dialog-organism">
                {/* Desmontado ao fechar: a próxima abertura começa limpa e com o "agora" novo. */}
                <SupportCardForm {...props} loading={loading} onCancel={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WSupportCardDialogOrganismProps, 'open' | 'onOpenChange' | 'loading'> & { loading: boolean; onCancel: () => void };

function SupportCardForm({ contacts, channels, now, stageName = 'Support', loading, errors, onConfirm, onCancel }: FormProps) {
    const formId = useId();
    const [title, setTitle] = useState('');
    const [contactId, setContactId] = useState('');
    const [channelId, setChannelId] = useState('');
    const [date, setDate] = useState(now.date);
    const [time, setTime] = useState(now.time);
    const [priority, setPriority] = useState<PrioritySlug>('normal');
    const [tried, setTried] = useState(false);

    const noContacts = contacts.length === 0;
    const missing = {
        title: title.trim() === '',
        contact: contactId === '',
        channel: channelId === '',
        date: date === '',
        time: !/^\d{2}:\d{2}$/.test(time),
    };
    const fieldError = (key: string, local: boolean, message: string) => errors?.[key] ?? (tried && local ? message : undefined);
    const titleError = fieldError('title', missing.title, 'Informe o título.');
    const contactError = fieldError('requester_contact_id', missing.contact, 'Escolha quem pediu.');
    const channelError = fieldError('contact_channel_id', missing.channel, 'Escolha por onde o pedido chegou.');
    const dateError = fieldError('requested_at', missing.date, 'Informe a data.');
    const timeError = tried && missing.time ? 'Informe a hora.' : undefined;
    const priorityError = errors?.priority;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (loading || noContacts || Object.values(missing).some(Boolean)) return;
        onConfirm({
            title: title.trim(),
            requester_contact_id: Number(contactId),
            contact_channel_id: Number(channelId),
            requested_at: `${date} ${time}`,
            priority,
        });
    };

    const ids = {
        title: `${formId}-title`,
        contact: `${formId}-contact`,
        channel: `${formId}-channel`,
        date: `${formId}-date`,
        time: `${formId}-time`,
        priority: `${formId}-priority`,
    };
    const describedBy = (id: string, error?: string) => (error ? `${id}-error` : undefined);

    return (
        <>
            <DialogHeader>
                <DialogTitle>Novo card de suporte</DialogTitle>
                <DialogDescription>Tipo: Suporte. O card nasce na coluna {stageName}, fora da sprint.</DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate className="space-y-4">
                    <WFormFieldMolecule label="Título" htmlFor={ids.title} required error={titleError}>
                        <Input
                            value={title}
                            maxLength={SUPPORT_TITLE_MAX}
                            disabled={loading}
                            autoFocus
                            onChange={(e) => setTitle(e.target.value)}
                        />
                    </WFormFieldMolecule>

                    {noContacts ? (
                        <WFormFieldMolecule label="Quem pediu" required>
                            <p
                                className="rounded-md border border-dashed border-border bg-muted px-3 py-2 text-sm text-muted-foreground"
                                role="status"
                            >
                                Nenhum contato ativo neste cliente. Cadastre na ficha do cliente.
                            </p>
                        </WFormFieldMolecule>
                    ) : (
                        <WFormFieldMolecule label="Quem pediu" htmlFor={ids.contact} required error={contactError}>
                            <Select value={contactId} onValueChange={setContactId} disabled={loading}>
                                <SelectTrigger
                                    id={ids.contact}
                                    aria-invalid={Boolean(contactError)}
                                    aria-describedby={describedBy(ids.contact, contactError)}
                                >
                                    <SelectValue placeholder="Escolha o contato" />
                                </SelectTrigger>
                                <SelectContent>
                                    {contacts.map((contact) => (
                                        <SelectItem key={contact.id} value={String(contact.id)}>
                                            {contact.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </WFormFieldMolecule>
                    )}

                    <WFormFieldMolecule label="Por onde" htmlFor={ids.channel} required error={channelError}>
                        <Select value={channelId} onValueChange={setChannelId} disabled={loading}>
                            <SelectTrigger
                                id={ids.channel}
                                aria-invalid={Boolean(channelError)}
                                aria-describedby={describedBy(ids.channel, channelError)}
                            >
                                <SelectValue placeholder="Escolha o canal" />
                            </SelectTrigger>
                            <SelectContent>
                                {channels.map((channel) => (
                                    <SelectItem key={channel.id} value={String(channel.id)}>
                                        {channel.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </WFormFieldMolecule>

                    <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                        <WFormFieldMolecule label="Quando" htmlFor={ids.date} required error={dateError}>
                            <WDatePickerMolecule
                                value={date || null}
                                clearable={false}
                                disabled={loading}
                                onChange={(value) => setDate(value ?? '')}
                            />
                        </WFormFieldMolecule>
                        <WFormFieldMolecule label="Hora" htmlFor={ids.time} required error={timeError}>
                            <Input
                                type="time"
                                value={time}
                                disabled={loading}
                                className="font-mono"
                                onChange={(e) => setTime(e.target.value)}
                            />
                        </WFormFieldMolecule>
                    </div>

                    <WFormFieldMolecule label="Prioridade" htmlFor={ids.priority} required error={priorityError}>
                        <Select value={priority} onValueChange={(value) => setPriority(value as PrioritySlug)} disabled={loading}>
                            <SelectTrigger
                                id={ids.priority}
                                aria-invalid={Boolean(priorityError)}
                                aria-describedby={describedBy(ids.priority, priorityError)}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {PRIORITIES.map((slug) => (
                                    <SelectItem key={slug} value={slug}>
                                        {PRIORITY_LABEL[slug]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </WFormFieldMolecule>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onCancel} disabled={loading}>
                    Cancelar
                </Button>
                <Button type="submit" form={formId} disabled={loading || noContacts}>
                    {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Criar card
                </Button>
            </DialogFooter>
        </>
    );
}
