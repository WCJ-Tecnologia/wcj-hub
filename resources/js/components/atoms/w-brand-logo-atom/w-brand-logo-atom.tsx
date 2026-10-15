import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';

/** Versão do desenho. Regra da marca pela altura do desenho: completo ≥ 40 px, horizontal de 16 a 40 px, símbolo abaixo de 16 px. */
export type WBrandLogoVariant = 'full' | 'horizontal' | 'symbol';

/** Fundo onde o logo fica. `auto` acompanha o tema (classe .dark no <html>). */
export type WBrandLogoSurface = 'light' | 'dark' | 'auto';

export type WBrandLogoSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

/** Altura da imagem. Os SVG já trazem a área de proteção (o raio de um nó em volta), então o desenho é menor que a caixa. */
const HEIGHT: Record<WBrandLogoSize, string> = {
    xs: 'h-5',
    sm: 'h-6',
    md: 'h-7',
    lg: 'h-10',
    xl: 'h-14',
};

/**
 * Arquivos oficiais, copiados sem alteração do mestre da marca (a marca proíbe redesenhar ou recolorir).
 * O logo do produto (símbolo + "Hub", docs/brand/logo/produtos/wcj-hub/) tem uma versão só, que serve
 * ao completo e ao horizontal; o símbolo é o da WCJ (ADR-006).
 */
const HUB_LOGO = { light: '/brand/wcj-hub-logo-fundo-claro.svg', dark: '/brand/wcj-hub-logo-fundo-escuro.svg' };

const FILES: Record<WBrandLogoVariant, { light: string; dark: string }> = {
    full: HUB_LOGO,
    horizontal: HUB_LOGO,
    symbol: { light: '/brand/wcj-simbolo-azul.svg', dark: '/brand/wcj-simbolo-azul-claro.svg' },
};

const ALT = 'WCJ Hub';

export interface WBrandLogoAtomProps {
    variant?: WBrandLogoVariant;
    surface?: WBrandLogoSurface;
    size?: WBrandLogoSize;
    /** Destino do link; `false` renderiza sem link. Padrão: `/`. */
    href?: string | false;
    className?: string;
}

/**
 * Logo do WCJ Hub nos arquivos oficiais de public/brand/. Escolha a versão pela
 * altura e o fundo pela superfície: `dark` na sidebar e no painel do login,
 * `auto` sobre a página (troca com o tema).
 */
export function WBrandLogoAtom({ variant = 'horizontal', surface = 'auto', size = 'md', href, className }: WBrandLogoAtomProps) {
    const files = FILES[variant];
    const image = cn('w-auto max-w-none shrink-0', HEIGHT[size]);

    // Em `auto`, as duas versões vão para o DOM e o tema esconde uma (display: none sai da árvore de acessibilidade).
    const inner =
        surface === 'auto' ? (
            <>
                <img src={files.light} alt={ALT} className={cn(image, 'dark:hidden')} />
                <img src={files.dark} alt={ALT} className={cn(image, 'hidden dark:block')} />
            </>
        ) : (
            <img src={files[surface]} alt={ALT} className={image} />
        );

    // `className` goes on the outermost element: a hidden inner image would leave
    // a visible, nameless link behind (axe: link-name).
    if (href === false) {
        return (
            <span data-slot="w-brand-logo-atom" className={cn('flex shrink-0 items-center', className)}>
                {inner}
            </span>
        );
    }

    // O nome do link é "Início", não o da marca: a WCJ também é cliente (N7), e um link "WCJ Tecnologia"
    // no menu confundiria com o da ficha do cliente (leitor de tela e e2e).
    return (
        <Link
            data-slot="w-brand-logo-atom"
            href={href ?? '/'}
            aria-label="Início"
            className={cn(
                'flex shrink-0 items-center rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
                className,
            )}
        >
            {inner}
        </Link>
    );
}
