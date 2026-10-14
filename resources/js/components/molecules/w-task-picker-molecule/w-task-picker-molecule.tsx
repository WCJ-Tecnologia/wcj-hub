import { Link } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { type KeyboardEvent, useId, useState } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { pickCards, type TaskPickMode } from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { TaskPickRef, TaskRef } from '@/types/quadro';

export interface WTaskPickerMoleculeProps {
    /** `options.cards`: os cards do projeto, sem os arquivados e sem este card (F3 §05). */
    cards: TaskPickRef[];
    /** `parent` (P57: sem pai e sem filhos) ou `origin` (P25: qualquer card do projeto). */
    mode: TaskPickMode;
    /** O card escolhido, ou null. */
    value: TaskRef | null;
    onChange: (card: TaskRef | null) => void;
    /** `aria-label` do campo de busca. */
    label: string;
    /** Id do campo, para o `WFormFieldMolecule` ou um `<label>` da tela. */
    id?: string;
    placeholder?: string;
    /** Sem permissão ou gravando: só mostra. */
    disabled?: boolean;
    /** Erro do servidor (ex.: `errors.parent_task_id`). */
    error?: string;
    className?: string;
}

/**
 * Busca de card da ficha (F3 §05): chave ou trecho do título, resultado numa lista por teclado
 * (setas, Enter, Esc). Para o card pai, os cards que a regra exclui ficam na lista **desabilitados,
 * com o motivo** ("SGM-10 já é pai de outros cards; um nível só." / "SGM-12 já é filho de SGM-10.",
 * F3 §04), para a pessoa entender em vez de não achar. Com valor, mostra a chave com link e o título,
 * e os botões *Trocar* e *Limpar*.
 */
export function WTaskPickerMolecule({
    cards,
    mode,
    value,
    onChange,
    label,
    id,
    placeholder = 'Chave ou título',
    disabled = false,
    error,
    className,
}: WTaskPickerMoleculeProps) {
    const baseId = useId();
    const inputId = id ?? `${baseId}-input`;
    const listId = `${baseId}-list`;
    const errorId = `${baseId}-error`;
    const [term, setTerm] = useState('');
    const [active, setActive] = useState(0);
    const [searching, setSearching] = useState(false);

    const results = pickCards(cards, term, mode);
    const showList = searching && term.trim() !== '';

    const choose = (card: TaskPickRef) => {
        onChange({ id: card.id, key: card.key, title: card.title, url: card.url });
        setTerm('');
        setSearching(false);
        setActive(0);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (!showList) return;
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((index) => Math.min(index + 1, Math.max(results.length - 1, 0)));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const hit = results[active];
            if (hit && !hit.exclusion) choose(hit.card);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setTerm('');
            setSearching(false);
        }
    };

    if (value && !searching) {
        return (
            <div data-slot="w-task-picker-molecule" className={cn('flex flex-wrap items-center gap-2', className)}>
                <WTaskKeyAtom taskKey={value.key} href={value.url} title={value.title} />
                <Link href={value.url} className="min-w-0 flex-1 truncate text-sm hover:underline">
                    {value.title}
                </Link>
                {!disabled && (
                    <span className="flex shrink-0 gap-1">
                        <Button type="button" variant="ghost" size="sm" onClick={() => setSearching(true)}>
                            Trocar
                        </Button>
                        <Button type="button" variant="ghost" size="sm" onClick={() => onChange(null)} aria-label={`Limpar ${value.key}`}>
                            <X aria-hidden /> Limpar
                        </Button>
                    </span>
                )}
                {error && (
                    <p id={errorId} role="alert" className="basis-full text-xs font-medium text-destructive">
                        {error}
                    </p>
                )}
            </div>
        );
    }

    return (
        <div data-slot="w-task-picker-molecule" className={cn('grid gap-1', className)}>
            <div className="relative">
                <Search
                    className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden
                />
                <Input
                    id={inputId}
                    role="combobox"
                    aria-label={label}
                    aria-expanded={showList}
                    aria-controls={listId}
                    aria-autocomplete="list"
                    aria-activedescendant={showList && results[active] ? `${listId}-${results[active].card.id}` : undefined}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? errorId : undefined}
                    value={term}
                    disabled={disabled}
                    placeholder={placeholder}
                    autoComplete="off"
                    className="pl-8"
                    onChange={(event) => {
                        setTerm(event.target.value);
                        setSearching(true);
                        setActive(0);
                    }}
                    onFocus={() => setSearching(true)}
                    onBlur={() => {
                        // O clique num resultado dispara antes do blur fechar a lista (mousedown abaixo evita o blur).
                        if (term.trim() === '' && value) setSearching(false);
                    }}
                    onKeyDown={onKeyDown}
                />
                {value && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute top-1/2 right-1 h-7 -translate-y-1/2"
                        onClick={() => {
                            setTerm('');
                            setSearching(false);
                        }}
                    >
                        Cancelar
                    </Button>
                )}
            </div>
            <ul
                id={listId}
                role="listbox"
                aria-label={label}
                hidden={!showList}
                className="max-h-64 overflow-y-auto rounded-md border border-border bg-popover p-1 text-sm shadow-md"
            >
                {results.length === 0 && (
                    <li role="option" aria-selected={false} aria-disabled className="px-2 py-1.5 text-muted-foreground">
                        Nenhum card com esse texto
                    </li>
                )}
                {results.map(({ card, exclusion }, index) => (
                    <li
                        key={card.id}
                        id={`${listId}-${card.id}`}
                        role="option"
                        aria-selected={index === active}
                        aria-disabled={exclusion ? true : undefined}
                        onMouseDown={(event) => event.preventDefault()}
                        onMouseEnter={() => setActive(index)}
                        onClick={() => !exclusion && choose(card)}
                        className={cn(
                            'grid cursor-pointer gap-0.5 rounded-sm px-2 py-1.5',
                            index === active && 'bg-accent text-accent-foreground',
                            exclusion && 'cursor-not-allowed opacity-70',
                        )}
                    >
                        <span className="flex items-center gap-2">
                            <WTaskKeyAtom taskKey={card.key} size="sm" />
                            <span className="min-w-0 flex-1 truncate">{card.title}</span>
                        </span>
                        {exclusion && <span className="text-xs text-muted-foreground">{exclusion}</span>}
                    </li>
                ))}
            </ul>
            {error && (
                <p id={errorId} role="alert" className="text-xs font-medium text-destructive">
                    {error}
                </p>
            )}
        </div>
    );
}
