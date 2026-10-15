import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    Clock,
    ContactRound,
    FolderKanban,
    History,
    LayoutDashboard,
    ListChecks,
    PanelLeftClose,
    UserRound,
    Users,
    type LucideIcon,
} from 'lucide-react';

import { WBrandLogoAtom } from '@/components/atoms/w-brand-logo-atom/w-brand-logo-atom';
import { WProductWordmarkAtom } from '@/components/atoms/w-product-wordmark-atom/w-product-wordmark-atom';
import { Avatar, AvatarFallback } from '@/components/shadcn/avatar';
import { Button } from '@/components/shadcn/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/shadcn/sidebar';
import { initials } from '@/lib/utils';
import type { Abilities, SharedProps } from '@/types';

export interface WNavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Sem `visible`, todo usuário logado vê. */
    visible?: (abilities: Abilities) => boolean;
}

export interface WNavGroup {
    title: string;
    items: WNavItem[];
}

/**
 * Declarative navigation. Adding a screen is adding an item here; a group
 * whose items are all hidden disappears by itself.
 */
export const NAV: WNavGroup[] = [
    {
        title: 'Visão geral',
        items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }],
    },
    {
        // F2 §09: todos menos o Comercial (C14); cada um vê só os projetos que pode ver (P5).
        title: 'Projetos',
        // Minhas horas (F5 §01, P30): a mesma regra da TimeEntryPolicy::viewAny, todos menos o Comercial.
        items: [
            { label: 'Projetos', href: '/projetos', icon: FolderKanban, visible: (a) => a.view_projects },
            { label: 'Minhas horas', href: '/minhas-horas', icon: Clock, visible: (a) => a.view_projects },
        ],
    },
    {
        // F1 §09: o Administrador vê todos; o gestor, Clientes e Contatos; o dev, nenhum.
        title: 'Administração',
        items: [
            { label: 'Usuários', href: '/admin/users', icon: Users, visible: (a) => a.manage_users },
            { label: 'Clientes', href: '/admin/clients', icon: Building2, visible: (a) => a.view_clients },
            { label: 'Contatos', href: '/admin/contacts', icon: ContactRound, visible: (a) => a.view_contacts },
            { label: 'Auditoria', href: '/admin/audit-logs', icon: History, visible: (a) => a.view_audit_logs },
            // F2 §09, G7: as listas gerais de Projetos (motivos de retrabalho), só o Administrador.
            { label: 'Listas', href: '/admin/listas', icon: ListChecks, visible: (a) => a.manage_lists },
        ],
    },
    {
        title: 'Conta',
        items: [{ label: 'Perfil', href: '/admin/profile', icon: UserRound }],
    },
];

export interface WAppSidebarOrganismProps {
    /** Navegação a mostrar. Padrão: `NAV`. */
    nav?: WNavGroup[];
}

/**
 * Menu lateral do backoffice sobre o Sidebar do shadcn. Desktop (lg): fixo,
 * recolhe para só os ícones (collapsible="icon", com tooltip em cada item).
 * Celular: gaveta (Sheet, com focus trap e ESC). Precisa estar dentro de um
 * SidebarProvider, que o WAppLayoutTemplate já monta.
 */
export function WAppSidebarOrganism({ nav = NAV }: WAppSidebarOrganismProps) {
    const { url, props } = usePage<SharedProps>();
    const { isMobile, setOpenMobile } = useSidebar();
    const user = props.auth.user;

    if (!user) return null;

    const isActive = (href: string) => url === href || url.startsWith(href + '/') || url.startsWith(href + '?');

    return (
        <Sidebar data-slot="w-app-sidebar-organism" collapsible="icon" className="border-sidebar-border">
            <SidebarHeader className="h-[4.25rem] flex-row items-center border-b border-sidebar-border px-4 text-white group-data-[collapsible=icon]:px-2">
                {/* Aberto: logo horizontal (desenho de 16 a 40 px). Recolhido (3rem): o símbolo inteiro, regra da marca para pouco espaço. */}
                <WBrandLogoAtom
                    variant="horizontal"
                    surface="dark"
                    size="md"
                    href="/admin/dashboard"
                    className="group-data-[collapsible=icon]:hidden"
                />
                <WBrandLogoAtom
                    variant="symbol"
                    surface="dark"
                    size="xs"
                    href="/admin/dashboard"
                    className="mx-auto hidden group-data-[collapsible=icon]:flex"
                />
                {/* Gaveta do celular: o Sidebar do shadcn esconde o X padrão do Sheet, então o fechar fica aqui. */}
                {isMobile && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => setOpenMobile(false)}
                        aria-label="Fechar menu"
                        className="ml-auto size-9 text-sidebar-accent-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    >
                        <PanelLeftClose aria-hidden />
                    </Button>
                )}
            </SidebarHeader>

            <SidebarContent className="scrollbar-slim">
                <nav
                    aria-label="Menu principal"
                    className="space-y-4 px-1 py-3 group-data-[collapsible=icon]:space-y-0 group-data-[collapsible=icon]:px-0"
                >
                    {nav.map((group) => {
                        const items = group.items.filter((item) => !item.visible || item.visible(user.abilities));

                        if (items.length === 0) return null;

                        return (
                            <SidebarGroup key={group.title}>
                                <SidebarGroupLabel className="font-mono text-2xs tracking-[0.16em] text-sidebar-foreground/75 uppercase">
                                    {group.title}
                                </SidebarGroupLabel>
                                <SidebarGroupContent>
                                    <SidebarMenu className="gap-1">
                                        {items.map((item) => {
                                            const active = isActive(item.href);
                                            const Icon = item.icon;

                                            return (
                                                <SidebarMenuItem key={item.href}>
                                                    <SidebarMenuButton
                                                        asChild
                                                        isActive={active}
                                                        tooltip={item.label}
                                                        className="h-9 gap-3 rounded-lg px-3 font-medium text-sidebar-foreground/85 focus-visible:ring-[3px] focus-visible:ring-sidebar-ring/50 data-[active=true]:bg-sidebar-primary data-[active=true]:text-sidebar-primary-foreground data-[active=true]:shadow-sm data-[active=true]:shadow-black/20 [&>svg]:size-[18px]"
                                                    >
                                                        <Link
                                                            href={item.href}
                                                            onClick={() => setOpenMobile(false)}
                                                            aria-current={active ? 'page' : undefined}
                                                        >
                                                            <Icon aria-hidden />
                                                            <span>{item.label}</span>
                                                        </Link>
                                                    </SidebarMenuButton>
                                                </SidebarMenuItem>
                                            );
                                        })}
                                    </SidebarMenu>
                                </SidebarGroupContent>
                            </SidebarGroup>
                        );
                    })}
                </nav>
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border p-3 group-data-[collapsible=icon]:p-2">
                <div className="rounded-lg bg-sidebar-accent/60 px-3 py-2.5 group-data-[collapsible=icon]:hidden">
                    <p className="truncate text-xs font-semibold">{user.name}</p>
                    <p className="truncate text-2xs text-sidebar-foreground/75">{user.role_label}</p>
                </div>
                {/* Recolhido: só as iniciais; nome e papel no title. */}
                <Avatar className="mx-auto hidden size-8 group-data-[collapsible=icon]:flex" title={`${user.name} · ${user.role_label}`}>
                    <AvatarFallback className="bg-sidebar-accent text-xs text-sidebar-accent-foreground">
                        {initials(user.name)}
                    </AvatarFallback>
                </Avatar>
                <WProductWordmarkAtom className="px-1 text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden" />
            </SidebarFooter>
        </Sidebar>
    );
}
