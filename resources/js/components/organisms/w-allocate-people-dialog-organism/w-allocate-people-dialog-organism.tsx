import { Loader2, Search } from 'lucide-react';
import { useId, useMemo, useState } from 'react';

import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import type { PersonOption } from '@/types/configuracao';

export interface WAllocatePeopleDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Quem ainda pode entrar (`options.people`): o servidor já tirou quem está no projeto e o Comercial. */
    people: PersonOption[];
    /** A alocação está indo ao servidor. */
    loading?: boolean;
    /** Erro do servidor para a lista inteira (ex.: "Fulano já está no projeto."). */
    error?: string;
    onConfirm: (userIds: number[]) => void;
}

const normalize = (text: string) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

/**
 * "Alocar pessoas" (F2 §02, aba Pessoas; P5): um campo só, com escolha de uma ou várias. Lista
 * de caixas com busca por nome; cada pessoa entra uma vez só. Abrir de novo zera a escolha.
 */
export function WAllocatePeopleDialogOrganism({
    open,
    onOpenChange,
    people,
    loading = false,
    error,
    onConfirm,
}: WAllocatePeopleDialogOrganismProps) {
    const [search, setSearch] = useState('');
    const [selected, setSelected] = useState<number[]>([]);
    const searchId = useId();
    const errorId = useId();

    // Abrir de novo zera a escolha: ajuste no render, sem efeito (react.dev, "you might not need an effect").
    const [wasOpen, setWasOpen] = useState(open);
    if (open !== wasOpen) {
        setWasOpen(open);
        if (open) {
            setSearch('');
            setSelected([]);
        }
    }

    const visible = useMemo(() => {
        const term = normalize(search.trim());
        return term === '' ? people : people.filter((person) => normalize(person.name).includes(term));
    }, [people, search]);

    const toggle = (id: number, checked: boolean) =>
        setSelected((current) => (checked ? [...current, id] : current.filter((item) => item !== id)));

    const count = selected.length;

    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-allocate-people-dialog-organism">
                <DialogHeader>
                    <DialogTitle>Alocar pessoas</DialogTitle>
                    <DialogDescription>
                        Quem entra passa a ver o projeto na hora, também pela IA, e pode ser dev ou QA de tarefa. Alocado quer dizer que vai
                        executar.
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    {people.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Todo mundo que pode entrar já está no projeto.</p>
                    ) : (
                        <div className="space-y-3">
                            <div className="relative">
                                <Search
                                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden
                                />
                                <Input
                                    id={searchId}
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Buscar pelo nome…"
                                    aria-label="Buscar pessoas"
                                    className="pl-9"
                                />
                            </div>
                            <ul
                                className="max-h-72 divide-y divide-border overflow-y-auto rounded-md border border-border"
                                aria-label="Pessoas que podem entrar"
                                aria-describedby={error ? errorId : undefined}
                            >
                                {visible.length === 0 && (
                                    <li className="px-3 py-3 text-sm text-muted-foreground">Ninguém com esse nome.</li>
                                )}
                                {visible.map((person) => {
                                    const id = `${searchId}-person-${person.id}`;
                                    return (
                                        <li key={person.id} className="flex items-center gap-3 px-3 py-2">
                                            <Checkbox
                                                id={id}
                                                checked={selected.includes(person.id)}
                                                onCheckedChange={(value) => toggle(person.id, value === true)}
                                                disabled={loading}
                                            />
                                            <label
                                                htmlFor={id}
                                                className="flex min-w-0 flex-1 cursor-pointer items-baseline justify-between gap-3 text-sm"
                                            >
                                                <span className="truncate font-medium">{person.name}</span>
                                                <span className="shrink-0 text-xs text-muted-foreground">{person.role_label}</span>
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                            {error && (
                                <p id={errorId} className="text-xs font-medium text-destructive" role="alert">
                                    {error}
                                </p>
                            )}
                        </div>
                    )}
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
                        Cancelar
                    </Button>
                    <Button onClick={() => onConfirm(selected)} disabled={loading || count === 0}>
                        {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                        {count <= 1 ? 'Alocar' : `Alocar ${count} pessoas`}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
