import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    title,
    description,
    icon,
    action,
}: {
    title: string;
    description: string;
    icon?: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card/50 px-6 py-14 text-center">
            <div className="mb-4 grid size-12 place-items-center rounded-full bg-muted text-muted-foreground">
                {icon ?? <Inbox className="size-5" />}
            </div>
            <h3 className="font-heading text-base font-semibold">{title}</h3>
            <p className="mt-1 max-w-md text-sm text-muted-foreground">{description}</p>
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
