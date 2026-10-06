import { Link, usePage } from '@inertiajs/react';
import { Building2, History, LayoutDashboard, UserRound, Users, type LucideIcon } from 'lucide-react';

import { BrandLogo, ProductWordmark } from '@/components/brand-logo';
import { cn } from '@/lib/utils';
import type { Abilities, SharedProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    visible?: (abilities: Abilities) => boolean;
}

interface NavGroup {
    title: string;
    items: NavItem[];
}

/**
 * Declarative navigation. Adding a screen is adding an item here; a group
 * whose items are all hidden disappears by itself.
 */
const NAV: NavGroup[] = [
    {
        title: 'Visão geral',
        items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }],
    },
    {
        title: 'Cadastros',
        items: [
            { label: 'Empresas', href: '/admin/enterprises', icon: Building2 },
            { label: 'Usuários', href: '/admin/users', icon: Users, visible: (a) => a.view_users },
        ],
    },
    {
        title: 'Conta',
        items: [
            { label: 'Logs de auditoria', href: '/admin/audit-logs', icon: History, visible: (a) => a.view_audit_logs },
            { label: 'Perfil', href: '/admin/profile', icon: UserRound },
        ],
    },
];

export function AppSidebar({ onNavigate }: { onNavigate?: () => void }) {
    const { url, props } = usePage<SharedProps>();
    const user = props.auth.user;

    if (!user) return null;

    const isActive = (href: string) => url === href || url.startsWith(href + '/') || url.startsWith(href + '?');

    return (
        <aside className="flex h-full w-[17rem] flex-col bg-sidebar text-sidebar-foreground" aria-label="Menu principal">
            <div className="flex h-[4.25rem] items-center border-b border-sidebar-border px-4 text-white">
                <BrandLogo showText={false} href="/admin/dashboard" />
            </div>

            <nav className="scrollbar-slim flex-1 space-y-6 overflow-y-auto px-3 py-5">
                {NAV.map((group) => {
                    const items = group.items.filter((item) => !item.visible || item.visible(user.abilities));

                    if (items.length === 0) return null;

                    return (
                        <div key={group.title}>
                            <p className="px-3 pb-2 text-2xs font-semibold tracking-[0.16em] text-sidebar-foreground/75 uppercase">
                                {group.title}
                            </p>
                            <ul className="space-y-1">
                                {items.map((item) => {
                                    const active = isActive(item.href);
                                    const Icon = item.icon;

                                    return (
                                        <li key={item.href}>
                                            <Link
                                                href={item.href}
                                                onClick={onNavigate}
                                                aria-current={active ? 'page' : undefined}
                                                className={cn(
                                                    'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all outline-none focus-visible:ring-[3px] focus-visible:ring-sidebar-ring/50',
                                                    active
                                                        ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm shadow-black/20'
                                                        : 'text-sidebar-foreground/85 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                                )}
                                            >
                                                <Icon className="size-[18px] shrink-0" aria-hidden />
                                                <span className="flex-1 truncate">{item.label}</span>
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    );
                })}
            </nav>

            <div className="border-t border-sidebar-border p-3">
                <div className="rounded-lg bg-sidebar-accent/60 px-3 py-2.5">
                    <p className="truncate text-xs font-semibold">{user.name}</p>
                    <p className="truncate text-2xs text-sidebar-foreground/75">{user.type_label}</p>
                </div>
                <ProductWordmark className="mt-2 px-1 text-sidebar-foreground/70" />
            </div>
        </aside>
    );
}
