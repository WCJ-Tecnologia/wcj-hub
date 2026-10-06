import { CheckCircle2, Circle, XCircle } from 'lucide-react';
import type { ComponentType } from 'react';

import { cn } from '@/lib/utils';

type Tone = 'pending' | 'approved' | 'rejected' | 'neutral';

const TONE_CLASSES: Record<Tone, string> = {
    pending: 'bg-status-pending-bg text-status-pending',
    approved: 'bg-status-approved-bg text-status-approved',
    rejected: 'bg-status-rejected-bg text-status-rejected',
    neutral: 'bg-status-neutral-bg text-status-neutral',
};

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

const CLIENT: Record<string, Entry> = {
    active: { tone: 'approved', icon: CheckCircle2, label: 'Ativo' },
    inactive: { tone: 'neutral', icon: XCircle, label: 'Inativo' },
};

const SETS = { user: USER, client: CLIENT };

/**
 * Status is never communicated by colour alone — every pill carries an icon and
 * a written label.
 */
export function StatusPill({
    status,
    kind,
    label,
    className,
    size = 'default',
}: {
    status: string | boolean | null | undefined;
    kind: keyof typeof SETS;
    label?: string;
    className?: string;
    size?: 'default' | 'sm';
}) {
    if (status === null || status === undefined) return null;

    const key = typeof status === 'boolean' ? (status ? 'active' : 'inactive') : status;

    const config = SETS[kind][key] ?? {
        tone: 'neutral' as Tone,
        icon: Circle,
        label: label ?? key,
    };
    const Icon = config.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full font-semibold',
                TONE_CLASSES[config.tone],
                size === 'sm' ? 'px-2 py-0.5 text-2xs' : 'px-2.5 py-1 text-xs',
                className,
            )}
        >
            <Icon className={size === 'sm' ? 'size-3' : 'size-3.5'} aria-hidden />
            {label ?? config.label}
        </span>
    );
}
