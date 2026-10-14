import { Plus } from 'lucide-react';
import { useId } from 'react';

import { Avatar, AvatarFallback } from '@/components/shadcn/avatar';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { cn, initials } from '@/lib/utils';
import type { PersonRef } from '@/types/quadro';

type CardRole = 'Dev' | 'QA';

interface Entry {
    person: PersonRef;
    roles: CardRole[];
}

/** Dev, QA e participantes numa lista só, sem repetir pessoa (a mesma pessoa pode ser dev e QA). */
function entriesOf(developer: PersonRef | null, qa: PersonRef | null, participants: PersonRef[]): Entry[] {
    const entries: Entry[] = [];
    const add = (person: PersonRef, role?: CardRole) => {
        const found = entries.find((entry) => entry.person.id === person.id);
        if (found) {
            if (role && !found.roles.includes(role)) found.roles.push(role);
            return;
        }
        entries.push({ person, roles: role ? [role] : [] });
    };
    if (developer) add(developer, 'Dev');
    if (qa) add(qa, 'QA');
    participants.forEach((person) => add(person));
    return entries;
}

function labelOf(entry: Entry): string {
    return entry.roles.length > 0 ? `${entry.person.name} · ${entry.roles.join(' e ')}` : entry.person.name;
}

const SIZES = { sm: 'size-6', default: 'size-8' } as const;

export interface WPeopleAvatarsMoleculeProps {
    developer: PersonRef | null;
    qa: PersonRef | null;
    /** Além do dev e do QA (P58). */
    participants: PersonRef[];
    /** Pessoas alocadas e ativas do projeto: as opções do botão *+* (P20, P58). */
    members?: PersonRef[];
    /** Liga o botão *+*. Sem ele, só os avatares. */
    onToggleParticipant?: (person: PersonRef, checked: boolean) => void;
    /** Quantos avatares antes do "+N". */
    max?: number;
    size?: 'sm' | 'default';
    className?: string;
}

/**
 * Pessoas do card (F3 §03, rodapé): avatares em linha, dev e QA com anel azul, o nome e o papel
 * ao passar o mouse; acima de `max`, "+N". O *+* abre a lista das pessoas alocadas para pôr ou tirar
 * participante (P58).
 */
export function WPeopleAvatarsMolecule({
    developer,
    qa,
    participants,
    members = [],
    onToggleParticipant,
    max = 4,
    size = 'default',
    className,
}: WPeopleAvatarsMoleculeProps) {
    const baseId = useId();
    const entries = entriesOf(developer, qa, participants);
    const visible = entries.slice(0, max);
    const hidden = entries.slice(max);

    if (entries.length === 0 && !onToggleParticipant) return null;

    return (
        <div data-slot="w-people-avatars-molecule" className={cn('flex items-center gap-1.5', className)}>
            {entries.length > 0 && (
                <ul aria-label="Pessoas do card" className="flex items-center gap-1.5">
                    {visible.map((entry) => (
                        <li key={entry.person.id} className="flex">
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Avatar
                                        role="img"
                                        aria-label={labelOf(entry)}
                                        className={cn(SIZES[size], entry.roles.length > 0 && 'ring-2 ring-primary')}
                                    >
                                        <AvatarFallback aria-hidden className={cn(size === 'sm' && 'text-xs')}>
                                            {initials(entry.person.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </TooltipTrigger>
                                <TooltipContent>{labelOf(entry)}</TooltipContent>
                            </Tooltip>
                        </li>
                    ))}
                    {hidden.length > 0 && (
                        <li className="flex">
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span
                                        role="img"
                                        aria-label={`Mais ${hidden.length}: ${hidden.map(labelOf).join(', ')}`}
                                        className={cn(
                                            SIZES[size],
                                            'grid place-items-center rounded-full bg-muted font-mono text-xs font-medium text-muted-foreground',
                                        )}
                                    >
                                        <span aria-hidden>+{hidden.length}</span>
                                    </span>
                                </TooltipTrigger>
                                <TooltipContent>{hidden.map(labelOf).join(', ')}</TooltipContent>
                            </Tooltip>
                        </li>
                    )}
                </ul>
            )}

            {onToggleParticipant && (
                <Popover>
                    <PopoverTrigger asChild>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Adicionar ou remover participante"
                            className={cn(SIZES[size], 'rounded-full border-dashed text-muted-foreground shadow-none')}
                        >
                            <Plus className="size-3.5" aria-hidden />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent align="start" className="w-72 p-2">
                        <p className="px-2 py-1.5 font-mono text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Participantes
                        </p>
                        {members.length === 0 ? (
                            <p className="px-2 py-1.5 text-sm text-muted-foreground">Nenhuma pessoa alocada no projeto.</p>
                        ) : (
                            <ul className="grid">
                                {members.map((member) => {
                                    const fixed: CardRole | null = developer?.id === member.id ? 'Dev' : qa?.id === member.id ? 'QA' : null;
                                    const checked = fixed !== null || participants.some((person) => person.id === member.id);
                                    const id = `${baseId}-${member.id}`;
                                    return (
                                        <li key={member.id}>
                                            <label
                                                htmlFor={id}
                                                className={cn(
                                                    'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm',
                                                    fixed
                                                        ? 'cursor-default'
                                                        : 'cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                                )}
                                            >
                                                <Checkbox
                                                    id={id}
                                                    checked={checked}
                                                    disabled={fixed !== null}
                                                    onCheckedChange={(value) => onToggleParticipant(member, value === true)}
                                                />
                                                <span className="min-w-0 flex-1 truncate">{member.name}</span>
                                                {fixed && <span className="text-xs text-muted-foreground">{fixed} · já está no card</span>}
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </PopoverContent>
                </Popover>
            )}
        </div>
    );
}
