import type { LucideIcon } from 'lucide-react';

import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Tone = 'default' | 'pending' | 'approved' | 'rejected' | 'primary';

const TONES: Record<Tone, string> = {
    default: 'bg-muted text-muted-foreground',
    primary: 'bg-primary/12 text-primary',
    pending: 'bg-status-pending-bg text-status-pending',
    approved: 'bg-status-approved-bg text-status-approved',
    rejected: 'bg-status-rejected-bg text-status-rejected',
};

export function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    tone = 'default',
    progress,
    className,
}: {
    label: string;
    value: string | number;
    hint?: string;
    icon?: LucideIcon;
    tone?: Tone;
    progress?: number;
    className?: string;
}) {
    return (
        <Card className={cn('gap-0 p-4', className)}>
            <div className="flex items-start justify-between gap-3">
                <p className="text-xs font-medium text-muted-foreground">{label}</p>
                {Icon && (
                    <span className={cn('grid size-8 shrink-0 place-items-center rounded-lg', TONES[tone])}>
                        <Icon className="size-4" />
                    </span>
                )}
            </div>

            <p className="mt-2 font-heading text-2xl font-bold tracking-tight">{value}</p>

            {progress !== undefined && (
                <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        className={cn(
                            'h-full rounded-full transition-all',
                            progress >= 100 ? 'bg-status-rejected' : progress >= 80 ? 'bg-status-pending' : 'bg-primary',
                        )}
                        style={{ width: `${Math.min(progress, 100)}%` }}
                    />
                </div>
            )}

            {hint && <p className="mt-2 text-xs text-muted-foreground">{hint}</p>}
        </Card>
    );
}
