import { router, usePage } from '@inertiajs/react';
import { LogOut, Menu, Moon, Sun, UserRound } from 'lucide-react';

import { BrandLogo } from '@/components/brand-logo';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTheme } from '@/hooks/use-theme';
import { cn, initials } from '@/lib/utils';
import type { SharedProps } from '@/types';

/**
 * Barra do shell: menu (celular), logo (celular), troca de tema e menu do
 * usuário. O título e as ações da página ficam no PageHeader, dentro do main.
 */
export function AppTopbar({ onMenu }: { onMenu?: () => void }) {
    const { theme, toggle } = useTheme();
    const { user } = usePage<SharedProps>().props.auth;
    const { brand } = usePage<SharedProps>().props.app;

    const logout = () => router.post('/admin/logout');

    return (
        <header className="sticky top-0 z-30 flex h-14 items-center gap-2 border-b border-border bg-background/85 px-4 backdrop-blur-md lg:px-6">
            <Button variant="ghost" size="icon" className="lg:hidden" onClick={onMenu} aria-label="Abrir menu">
                <Menu className="size-5" aria-hidden />
            </Button>

            <BrandLogo showText={false} size="sm" className="lg:hidden" href="/admin/dashboard" />
            <p className="hidden min-w-0 flex-1 truncate text-sm font-medium text-muted-foreground lg:block">{brand.name}</p>
            <span className="flex-1 lg:hidden" />

            <Button variant="outline" size="icon" onClick={toggle} aria-label={theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'}>
                <Sun className={cn('size-4', theme === 'dark' && 'hidden')} aria-hidden />
                <Moon className={cn('size-4', theme !== 'dark' && 'hidden')} aria-hidden />
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        className="flex items-center gap-2 rounded-full py-0.5 pr-2 pl-0.5 transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/30"
                        aria-label="Menu do usuário"
                    >
                        <Avatar>
                            <AvatarFallback>{initials(user?.name ?? 'AD')}</AvatarFallback>
                        </Avatar>
                        <span className="hidden text-left leading-tight sm:block">
                            <span className="block max-w-[9rem] truncate text-xs font-semibold">{user?.name}</span>
                            <span className="block max-w-[9rem] truncate text-2xs text-muted-foreground">{user?.type_label}</span>
                        </span>
                    </button>
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
