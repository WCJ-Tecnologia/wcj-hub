import { Link, usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types';

type BrandSize = 'sm' | 'md' | 'lg';

const IMAGE_SIZE: Record<BrandSize, string> = {
    sm: 'h-7 max-w-[10rem]',
    md: 'h-8 max-w-[11rem]',
    lg: 'h-11 max-w-[14rem]',
};

/**
 * The client's logo, read from the shared `app.brand` props (APP_BRAND_NAME and
 * APP_BRAND_LOGO in .env). Swap public/brand/logo.svg for the real file.
 */
export function BrandLogo({
    className,
    showText = true,
    size = 'md',
    href,
}: {
    className?: string;
    showText?: boolean;
    size?: BrandSize;
    /** `false` renders without a link. */
    href?: string | false;
}) {
    const { brand } = usePage<SharedProps>().props.app;

    // `className` goes on the outermost element: a hidden inner span would leave
    // a visible, nameless link behind (axe: link-name).
    const inner = (
        <>
            <img src={brand.logo} alt={brand.name} className={cn('w-auto shrink-0 object-contain object-left', IMAGE_SIZE[size])} />
            {showText && <span className="truncate text-sm font-bold tracking-tight">{brand.name}</span>}
        </>
    );

    if (href === false) {
        return <span className={cn('flex min-w-0 shrink-0 items-center gap-2.5', className)}>{inner}</span>;
    }

    return (
        <Link
            href={href ?? '/'}
            className={cn(
                'flex min-w-0 shrink-0 items-center gap-2.5 rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
                className,
            )}
        >
            {inner}
        </Link>
    );
}

/** Product name, used as a discreet footer in the sidebar. */
export function ProductWordmark({ className }: { className?: string }) {
    const { name } = usePage<SharedProps>().props.app;

    return <p className={cn('truncate text-2xs font-medium', className)}>{name}</p>;
}
