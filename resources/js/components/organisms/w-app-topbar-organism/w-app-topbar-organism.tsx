import { router, usePage } from '@inertiajs/react';
import { LogOut, Moon, Sun, UserRound } from 'lucide-react';

import { WBrandLogoAtom } from '@/components/atoms/w-brand-logo-atom/w-brand-logo-atom';
import { Avatar, AvatarFallback } from '@/components/shadcn/avatar';
import { Button } from '@/components/shadcn/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/shadcn/dropdown-menu';
import { SidebarTrigger } from '@/components/shadcn/sidebar';
import { useTheme } from '@/hooks/use-theme';
import { cn, initials } from '@/lib/utils';
import type { SharedProps } from '@/types';

/**
 * Barra do shell: botão do menu lateral, logo (celular), troca de tema e menu
 * do usuário. O título e as ações da página ficam no WPageHeaderMolecule.
 * Precisa estar dentro de um SidebarProvider (o WAppLayoutTemplate monta).
 */
export function WAppTopbarOrganism() {
    const { theme, toggle } = useTheme();
    const { user } = usePage<SharedProps>().props.auth;
    const { brand } = usePage<SharedProps>().props.app;

    const logout = () => router.post('/admin/logout');

    return (
        <header
            data-slot="w-app-topbar-organism"
            className="sticky top-0 z-30 flex h-14 items-center gap-2 border-b border-border bg-background/85 px-4 backdrop-blur-md lg:px-6"
        >
            <SidebarTrigger className="size-9" aria-label="Abrir ou fechar o menu" />

            <WBrandLogoAtom showText={false} size="sm" className="lg:hidden" href="/admin/dashboard" />
            <p className="hidden min-w-0 flex-1 truncate text-sm font-medium text-muted-foreground lg:block">{brand.name}</p>
            <span className="flex-1 lg:hidden" />

            <Button variant="outline" size="icon" onClick={toggle} aria-label={theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'}>
                <Sun className={cn('size-4', theme === 'dark' && 'hidden')} aria-hidden />
                <Moon className={cn('size-4', theme !== 'dark' && 'hidden')} aria-hidden />
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-auto gap-2 rounded-full py-0.5 pr-2 pl-0.5" aria-label="Menu do usuário">
                        <Avatar>
                            <AvatarFallback>{initials(user?.name ?? 'AD')}</AvatarFallback>
                        </Avatar>
                        <span className="hidden text-left leading-tight sm:block">
                            <span className="block max-w-[9rem] truncate text-xs font-semibold">{user?.name}</span>
                            <span className="block max-w-[9rem] truncate text-2xs font-normal text-muted-foreground">
                                {user?.role_label}
                            </span>
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuLabel>
                        <p className="text-sm font-semibold text-foreground">{user?.name}</p>
                        <p className="truncate text-xs font-normal text-muted-foreground">{user?.email}</p>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onClick={() => router.visit('/admin/profile')}>
                        <UserRound /> Meu perfil
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem variant="destructive" onClick={logout}>
                        <LogOut /> Sair
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>
    );
}
