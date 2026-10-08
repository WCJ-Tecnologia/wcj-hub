import { Link, usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types';

export type WBrandLogoSize = 'sm' | 'md' | 'lg';

const IMAGE_SIZE: Record<WBrandLogoSize, string> = {
    sm: 'h-7 max-w-[10rem]',
    md: 'h-8 max-w-[11rem]',
    lg: 'h-11 max-w-[14rem]',
};

export interface WBrandLogoAtomProps {
    className?: string;
    /** Mostra o nome da marca ao lado da imagem. */
    showText?: boolean;
    size?: WBrandLogoSize;
    /** Destino do link; `false` renderiza sem link. Padrão: `/`. */
    href?: string | false;
}

/**
 * The client's logo, read from the shared `app.brand` props (APP_BRAND_NAME and
 * APP_BRAND_LOGO in .env). Swap public/brand/logo.svg for the real file.
 */
export function WBrandLogoAtom({ className, showText = true, size = 'md', href }: WBrandLogoAtomProps) {
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
        return (
            <span data-slot="w-brand-logo-atom" className={cn('flex min-w-0 shrink-0 items-center gap-2.5', className)}>
                {inner}
            </span>
        );
    }

    return (
        <Link
            data-slot="w-brand-logo-atom"
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
