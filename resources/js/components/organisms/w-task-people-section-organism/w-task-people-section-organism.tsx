import { Loader2, Plus, X } from 'lucide-react';
import { useId, useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Avatar, AvatarFallback } from '@/components/shadcn/avatar';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { assignNoticeText } from '@/lib/ficha';
import { cn, initials } from '@/lib/utils';
import type { PersonRef } from '@/types/quadro';

export interface WTaskPeopleSectionOrganismProps {
    taskKey: string;
    developer: PersonRef | null;
    qa: PersonRef | null;
    participants: PersonRef[];
    /** Pessoas alocadas e ativas (`options.members`). */
    members: PersonRef[];
    /** `auth.user.id`: quem se define a si mesmo não recebe aviso (F3 §10.5). */
    currentUserId: number | null;
    /** `abilities.update`. */
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `developer_id`, `qa_id`, `user_id`. */
    errors?: Record<string, string>;
    onChangeDeveloper: (person: PersonRef | null) => void;
    onChangeQa: (person: PersonRef | null) => void;
    onToggleParticipant: (person: PersonRef, add: boolean) => void;
}

/** "Dev Bruno Lima · QA Carla Mendes · 2 participantes" ou "Ninguém no card": a linha do cabeçalho recolhido. */
export function peopleSummary(developer: PersonRef | null, qa: PersonRef | null, participants: PersonRef[]): string {
    const parts: string[] = [];
    if (developer) parts.push(`Dev ${developer.name}`);
    if (qa) parts.push(`QA ${qa.name}`);
    if (participants.length > 0) parts.push(`${participants.length} ${participants.length === 1 ? 'participante' : 'participantes'}`);
    return parts.length > 0 ? parts.join(' · ') : 'Ninguém no card';
}

/** "Ninguém" no Select: o Radix não aceita valor vazio (o mesmo jeito do `NO_AREA` do card). */
const NOBODY = 'none';

type Role = 'developer' | 'qa';

const ROLE_LABEL: Record<Role, string> = { developer: 'dev', qa: 'QA' };

/** Qual controle disparou a gravação em curso: o spinner vai nele. */
type Acting = Role | `participant-${number}`;

/**
 * Seção *Pessoas* da ficha do card (F3 §02, §04; P20, P22, P58, P5): Dev e QA, um de cada,
 * entre as pessoas alocadas e ativas, e os participantes. Definir outra pessoa como dev ou QA
 * pede confirmação com o aviso "Fulano será avisado no sistema e por e-mail" (P22); a si mesmo
 * ou "Ninguém" grava direto (F3 §10.5). Participante entra e sai sem aviso (P58).
 * Só o conteúdo: a página põe o cabeçalho recolhível e o resumo (`peopleSummary`).
 */
export function WTaskPeopleSectionOrganism({
    taskKey,
    developer,
    qa,
    participants,
    members,
    currentUserId,
    canEdit,
    busy = false,
    errors,
    onChangeDeveloper,
    onChangeQa,
    onToggleParticipant,
}: WTaskPeopleSectionOrganismProps) {
    const baseId = useId();
    const [acting, setActing] = useState<Acting | null>(null);
    const [pending, setPending] = useState<{ role: Role; person: PersonRef } | null>(null);

    const spinning = (key: Acting) => busy && acting === key;
    const current: Record<Role, PersonRef | null> = { developer, qa };
    const change: Record<Role, (person: PersonRef | null) => void> = { developer: onChangeDeveloper, qa: onChangeQa };

    const commit = (role: Role, person: PersonRef | null) => {
        setActing(role);
        change[role](person);
    };

    /** As alocadas e, se o atual não estiver entre elas, ele também: o Select precisa da opção para mostrar o nome. */
    const optionsFor = (person: PersonRef | null) =>
        person && !members.some((member) => member.id === person.id) ? [person, ...members] : members;

    /** O Select fica no valor da prop: só muda depois que a página gravar (e, com aviso, depois do *Definir*). */
    const choose = (role: Role, value: string) => {
        if (value === NOBODY) {
            if (current[role] !== null) commit(role, null);
            return;
        }
        const person = optionsFor(current[role]).find((member) => String(member.id) === value);
        if (!person || person.id === current[role]?.id) return;
        if (person.id === currentUserId) commit(role, person);
        else setPending({ role, person });
    };

    const toggle = (person: PersonRef, add: boolean) => {
        setActing(`participant-${person.id}`);
        onToggleParticipant(person, add);
    };

    if (!canEdit) {
        return (
            <dl data-slot="w-task-people-section-organism" className="grid gap-3 text-sm sm:grid-cols-3">
                <ReadOnlyFact label="Dev" value={developer?.name ?? '—'} />
                <ReadOnlyFact label="QA" value={qa?.name ?? '—'} />
                <ReadOnlyFact
                    label="Participantes"
                    value={participants.length > 0 ? participants.map((person) => person.name).join(', ') : 'Nenhum participante'}
                />
            </dl>
        );
    }

    const roleField = (role: Role, label: string, errorKey: string) => {
        const id = `${baseId}-${role}`;
        const error = errors?.[errorKey];
        const person = current[role];
        return (
            <WFormFieldMolecule label={label} htmlFor={id} error={error}>
                <Select value={person ? String(person.id) : NOBODY} onValueChange={(value) => choose(role, value)} disabled={busy}>
                    {/* O Root do Radix não é elemento: id e aria vão no trigger, que é o botão focável. */}
                    <SelectTrigger id={id} aria-invalid={Boolean(error)} aria-describedby={error ? `${id}-error` : undefined}>
                        {spinning(role) && <Loader2 className="animate-spin" aria-hidden />}
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NOBODY}>Ninguém</SelectItem>
                        {members.length === 0 && !person ? (
                            <SelectItem value="no-members" disabled>
                                Nenhuma pessoa alocada no projeto
                            </SelectItem>
                        ) : (
                            optionsFor(person).map((member) => (
                                <SelectItem key={member.id} value={String(member.id)}>
                                    {member.name}
                                </SelectItem>
                            ))
                        )}
                    </SelectContent>
                </Select>
            </WFormFieldMolecule>
        );
    };

    const participantsLabelId = `${baseId}-participants`;
    // Quem acabou de entrar ainda não está na lista: o spinner vai no botão do Popover.
    const addingSpins =
        busy &&
        acting !== null &&
        acting.startsWith('participant-') &&
        !participants.some((person) => acting === `participant-${person.id}`);

    return (
        <div data-slot="w-task-people-section-organism" aria-busy={busy || undefined} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                {roleField('developer', 'Dev', 'developer_id')}
                {roleField('qa', 'QA', 'qa_id')}
            </div>

            <div className="space-y-2">
                <p id={participantsLabelId} className="text-sm font-medium">
                    Participantes
                </p>
                {participants.length === 0 ? (
                    <p className="text-sm text-muted-foreground">Nenhum participante.</p>
                ) : (
                    <ul aria-labelledby={participantsLabelId} className="grid gap-1">
                        {participants.map((person) => (
                            <li key={person.id} className="flex items-center gap-2 rounded-md py-1">
                                <Avatar className="size-7">
                                    <AvatarFallback aria-hidden className="text-xs">
                                        {initials(person.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <span className="min-w-0 flex-1 truncate text-sm">{person.name}</span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    disabled={busy}
                                    aria-label={`Remover ${person.name} dos participantes`}
                                    onClick={() => toggle(person, false)}
                                >
                                    {spinning(`participant-${person.id}`) ? (
                                        <Loader2 className="animate-spin" aria-hidden />
                                    ) : (
                                        <X aria-hidden />
                                    )}
                                    Remover
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}

                <ParticipantPicker
                    baseId={baseId}
                    developer={developer}
                    qa={qa}
                    participants={participants}
                    members={members}
                    busy={busy}
                    spinning={addingSpins}
                    onToggle={toggle}
                />

                {errors?.user_id && (
                    <p role="alert" className="text-xs font-medium text-destructive">
                        {errors.user_id}
                    </p>
                )}
            </div>

            <WConfirmDialogMolecule
                open={pending !== null}
                onOpenChange={(open) => !open && setPending(null)}
                title={pending ? `Definir ${pending.person.name} como ${ROLE_LABEL[pending.role]} de ${taskKey}?` : ''}
                description={pending ? assignNoticeText(pending.person.name) : undefined}
                confirmLabel="Definir"
                loading={busy}
                onConfirm={() => {
                    if (!pending) return;
                    commit(pending.role, pending.person);
                    setPending(null);
                }}
            />
        </div>
    );
}

function ReadOnlyFact({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-0.5">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}

interface ParticipantPickerProps {
    baseId: string;
    developer: PersonRef | null;
    qa: PersonRef | null;
    participants: PersonRef[];
    members: PersonRef[];
    busy: boolean;
    spinning: boolean;
    onToggle: (person: PersonRef, add: boolean) => void;
}

/** *Adicionar participante*: as pessoas alocadas com caixa de marcar; dev e QA já estão no card (F3 §04). */
function ParticipantPicker({ baseId, developer, qa, participants, members, busy, spinning, onToggle }: ParticipantPickerProps) {
    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button variant="outline" size="sm" disabled={busy}>
                    {spinning ? <Loader2 className="animate-spin" aria-hidden /> : <Plus aria-hidden />}
                    Adicionar participante
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-72 p-2">
                <p className="px-2 py-1.5 font-mono text-xs font-medium tracking-wide text-muted-foreground uppercase">Participantes</p>
                {members.length === 0 ? (
                    <p className="px-2 py-1.5 text-sm text-muted-foreground">Nenhuma pessoa alocada no projeto.</p>
                ) : (
                    <ul className="grid">
                        {members.map((member) => {
                            const fixed = developer?.id === member.id ? 'Dev' : qa?.id === member.id ? 'QA' : null;
                            const checked = fixed !== null || participants.some((person) => person.id === member.id);
                            const id = `${baseId}-member-${member.id}`;
                            return (
                                <li key={member.id}>
                                    <label
                                        htmlFor={id}
                                        className={cn(
                                            'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm',
                                            fixed ? 'cursor-default' : 'cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                        )}
                                    >
                                        <Checkbox
                                            id={id}
                                            checked={checked}
                                            disabled={fixed !== null || busy}
                                            onCheckedChange={(value) => onToggle(member, value === true)}
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
    );
}
