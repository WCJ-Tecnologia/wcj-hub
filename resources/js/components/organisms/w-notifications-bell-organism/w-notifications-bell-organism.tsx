/**
 * Sino de avisos do shell (F3 §07; P22, R3): a lista de avisos da pessoa, com link para o card.
 *
 * Rotas: PROPOSTA do kit, o Cauã confirma no B3.
 * - `POST /avisos/{id}/lida`: marca um aviso como lido;
 * - `POST /avisos/lidas`: marca todos como lidos.
 * As duas com `{ preserveScroll: true, only: ['notifications'] }`. Nada é otimista: o shell não tem
 * estado próprio, e o servidor manda `notifications` de novo na resposta.
 */
import { router, usePage } from '@inertiajs/react';
import { Bell, Loader2 } from 'lucide-react';
import { useId, useState, type MouseEvent } from 'react';

import { Button } from '@/components/shadcn/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover';
import { dateTimeBr } from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { HubNotification, NotificationsPayload, SharedProps } from '@/types';

export interface WNotificationsBellOrganismProps {
    /** Sobrepõe o `usePage().props.notifications` (stories e testes). */
    notifications?: NotificationsPayload;
    /** Abre a lista ao montar (stories). */
    defaultOpen?: boolean;
    className?: string;
}

const EMPTY: NotificationsPayload = { unread_count: 0, items: [] };
const VISIT_OPTIONS = { preserveScroll: true, only: ['notifications'] };

/** "Avisos" ou "Avisos, 3 não lidos" (o nome do botão do sino). */
export function bellLabel(unread: number): string {
    if (unread <= 0) return 'Avisos';
    return `Avisos, ${unread} ${unread === 1 ? 'não lido' : 'não lidos'}`;
}

/** O número do selo: "9+" acima de 9. */
export function badgeText(unread: number): string {
    return unread > 9 ? '9+' : String(unread);
}

/** O começo do texto do aviso, para o nome do botão "Marcar como lida". */
function excerpt(text: string): string {
    return text.length > 48 ? `${text.slice(0, 48).trimEnd()}…` : text;
}

export function WNotificationsBellOrganism({ notifications, defaultOpen = false, className }: WNotificationsBellOrganismProps) {
    const shared = usePage<SharedProps>().props.notifications;
    const { unread_count: unread, items } = notifications ?? shared ?? EMPTY;
    const [open, setOpen] = useState(defaultOpen);
    /** O id do aviso sendo marcado, ou 'all'; trava os outros botões até o servidor responder. */
    const [pending, setPending] = useState<string | null>(null);
    const headingId = useId();

    const markRead = (item: HubNotification) => {
        if (pending) return;
        router.post(
            `/avisos/${item.id}/lida`,
            {},
            { ...VISIT_OPTIONS, onStart: () => setPending(item.id), onFinish: () => setPending(null) },
        );
    };

    const markAll = () => {
        if (pending) return;
        router.post('/avisos/lidas', {}, { ...VISIT_OPTIONS, onStart: () => setPending('all'), onFinish: () => setPending(null) });
    };

    const openCard = (event: MouseEvent<HTMLAnchorElement>, item: HubNotification) => {
        // Ctrl/Cmd/Shift/meio: deixa o navegador abrir em outra aba, como um link comum.
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        if (!item.url) return;
        const url = item.url;
        setOpen(false);
        // Marca antes e visita depois: uma visita nova cancelaria o POST em voo (Inertia 2).
        if (item.read_at === null) {
            router.post(`/avisos/${item.id}/lida`, {}, { ...VISIT_OPTIONS, onFinish: () => router.visit(url) });
        } else {
            router.visit(url);
        }
    };

    return (
        <Popover open={open} onOpenChange={setOpen} modal={false}>
            <PopoverTrigger asChild>
                <Button
                    data-slot="w-notifications-bell-organism"
                    variant="outline"
                    size="icon"
                    className={cn('relative', className)}
                    aria-label={bellLabel(unread)}
                >
                    <Bell className="size-4" aria-hidden />
                    {unread > 0 && (
                        <span
                            aria-hidden
                            className="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 font-mono text-2xs leading-none text-primary-foreground"
                        >
                            {badgeText(unread)}
                        </span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-80 p-0" aria-labelledby={headingId}>
                <div className="flex items-center justify-between gap-2 border-b border-border px-3 py-2">
                    <p id={headingId} className="text-sm font-semibold">
                        Avisos
                    </p>
                    {unread > 0 && (
                        <Button variant="link" size="sm" className="h-auto px-0 py-0 text-xs" onClick={markAll} disabled={pending !== null}>
                            {pending === 'all' && <Loader2 className="size-3.5 animate-spin" aria-hidden />}
                            Marcar todas como lidas
                        </Button>
                    )}
                </div>

                {items.length === 0 ? (
                    <p className="px-3 py-6 text-center text-sm text-muted-foreground">Nenhum aviso.</p>
                ) : (
                    <ul
                        aria-labelledby={headingId}
                        aria-busy={pending !== null}
                        className="max-h-96 divide-y divide-border overflow-y-auto"
                    >
                        {items.map((item) => {
                            const isUnread = item.read_at === null;
                            return (
                                <li key={item.id} className="flex gap-2 px-3 py-2.5">
                                    <span
                                        aria-hidden
                                        className={cn('mt-1.5 size-2 shrink-0 rounded-full', isUnread ? 'bg-primary' : 'bg-transparent')}
                                    />
                                    <div className="min-w-0 flex-1">
                                        {/* Fora do <p>: o link fica sozinho no bloco de texto (axe link-in-text-block). */}
                                        {isUnread && <span className="sr-only">Não lido</span>}
                                        <p className={cn('text-sm leading-snug text-foreground', isUnread && 'font-medium')}>
                                            {item.url ? (
                                                <a
                                                    href={item.url}
                                                    onClick={(event) => openCard(event, item)}
                                                    className="rounded-sm underline-offset-2 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                >
                                                    {item.text}
                                                </a>
                                            ) : (
                                                item.text
                                            )}
                                        </p>
                                        <div className="mt-1 flex items-center justify-between gap-2">
                                            <span className="font-mono text-2xs text-muted-foreground">{dateTimeBr(item.created_at)}</span>
                                            {isUnread && (
                                                <Button
                                                    variant="link"
                                                    size="sm"
                                                    className="h-auto px-0 py-0 text-xs"
                                                    onClick={() => markRead(item)}
                                                    disabled={pending !== null}
                                                    aria-label={`Marcar como lida: ${excerpt(item.text)}`}
                                                >
                                                    {pending === item.id && <Loader2 className="size-3.5 animate-spin" aria-hidden />}
                                                    Marcar como lida
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </PopoverContent>
        </Popover>
    );
}
