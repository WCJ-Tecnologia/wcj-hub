import { CheckCircle2, Circle, EyeOff, ShieldX, XCircle } from 'lucide-react';
import type { ComponentType } from 'react';

import { Badge } from '@/components/shadcn/badge';
import { cn } from '@/lib/utils';

type Tone = 'pending' | 'approved' | 'rejected' | 'neutral';

const TONE_VARIANT = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    neutral: 'info',
} as const satisfies Record<Tone, string>;

type Entry = { tone: Tone; icon: ComponentType<{ className?: string }>; label: string };

/**
 * One dictionary per entity. The technical value never reaches the screen:
 * this is the display layer that translates it. Add a set when a new entity
 * gains a status.
 */
const USER: Record<string, Entry> = {
    active: { tone: 'approved', icon: CheckCircle2, label: 'Ativo' },
    inactive: { tone: 'neutral', icon: XCircle, label: 'Desativado' },
};

// F1 §02: a situação do cliente é Ativo ou Desativado (N11).
const CLIENT: Record<string, Entry> = {
    active: { tone: 'approved', icon: CheckCircle2, label: 'Ativo' },
    inactive: { tone: 'neutral', icon: XCircle, label: 'Desativado' },
};

// F1 §02: Ativo, Desativado ou Anonimizado (N11, N12).
const CONTACT: Record<string, Entry> = {
    active: { tone: 'approved', icon: CheckCircle2, label: 'Ativo' },
    inactive: { tone: 'neutral', icon: XCircle, label: 'Desativado' },
    anonymized: { tone: 'neutral', icon: EyeOff, label: 'Anonimizado' },
};

// F1 §03: o resultado da linha de auditoria é ok ou negado (G3).
const AUDIT: Record<string, Entry> = {
    success: { tone: 'approved', icon: CheckCircle2, label: 'Ok' },
    denied: { tone: 'rejected', icon: ShieldX, label: 'Negado' },
};

// F2 §02: a situação do projeto é Aberto ou Encerrado (P7).
const PROJECT: Record<string, Entry> = {
    active: { tone: 'approved', icon: CheckCircle2, label: 'Aberto' },
    inactive: { tone: 'neutral', icon: XCircle, label: 'Encerrado' },
};

// F3 §02: o resultado do critério de aceite, marcado pelo QA em F4 (P36).
const CRITERION: Record<string, Entry> = {
    pending: { tone: 'pending', icon: Circle, label: 'A testar' },
    approved: { tone: 'approved', icon: CheckCircle2, label: 'Aprovado' },
    failed: { tone: 'rejected', icon: XCircle, label: 'Reprovado' },
};

const SETS = { user: USER, client: CLIENT, contact: CONTACT, audit: AUDIT, project: PROJECT, criterion: CRITERION };

export type WStatusPillKind = keyof typeof SETS;

export interface WStatusPillAtomProps {
    /** Valor técnico do status; booleano vira `active`/`inactive`. Nulo não renderiza nada. */
    status: string | boolean | null | undefined;
    /** Dicionário da entidade que traduz o status. */
    kind: WStatusPillKind;
    /** Rótulo que substitui o do dicionário. */
    label?: string;
    className?: string;
    size?: 'default' | 'sm';
}

/**
 * Status is never communicated by colour alone — every pill carries an icon and
 * a written label. Built on the shadcn Badge (status variants).
 */
export function WStatusPillAtom({ status, kind, label, className, size = 'default' }: WStatusPillAtomProps) {
    if (status === null || status === undefined) return null;

    const key = typeof status === 'boolean' ? (status ? 'active' : 'inactive') : status;

    const config = SETS[kind][key] ?? {
        tone: 'neutral' as Tone,
        icon: Circle,
        label: label ?? key,
    };
    const Icon = config.icon;

    return (
        <Badge
            data-slot="w-status-pill-atom"
            variant={TONE_VARIANT[config.tone]}
            className={cn(
                'gap-1.5 rounded-full font-semibold',
                size === 'sm' ? 'px-2 py-0.5 text-2xs [&>svg]:size-3' : 'px-2.5 py-1 text-xs [&>svg]:size-3.5',
                className,
            )}
        >
            <Icon aria-hidden />
            {label ?? config.label}
        </Badge>
    );
}
