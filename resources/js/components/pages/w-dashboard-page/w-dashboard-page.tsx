import { Head, Link } from '@inertiajs/react';
import { ArrowRight, History, UserCheck, Users } from 'lucide-react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WStatCardMolecule } from '@/components/molecules/w-stat-card-molecule/w-stat-card-molecule';
import { Button } from '@/components/shadcn/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shadcn/card';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';

interface Summary {
    users_active: number;
    users_total: number;
}

interface ActivityRow {
    id: number;
    user: string;
    action_label: string;
    entity: string;
    entity_id: number | null;
    created_at: string | null;
}

export interface WDashboardPageProps {
    summary: Summary;
    /** Only administrators receive the audit trail; others get an empty list. */
    activity: ActivityRow[];
}

/** Tela Dashboard (Inertia: `admin/dashboard`). */
export function WDashboardPage({ summary, activity }: WDashboardPageProps) {
    return (
        <WAppLayoutTemplate title="Dashboard" subtitle="Visão geral do sistema">
            <Head title="Dashboard" />

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <WStatCardMolecule label="Usuários ativos" value={summary.users_active} icon={UserCheck} tone="approved" />
                <WStatCardMolecule
                    label="Usuários cadastrados"
                    value={summary.users_total}
                    icon={Users}
                    tone="primary"
                    hint={`${summary.users_total - summary.users_active} inativos`}
                />
            </div>

            {activity.length > 0 ? (
                <Card className="mt-5 gap-0">
                    <CardHeader className="flex-row items-center justify-between pb-3">
                        <CardTitle className="flex items-center gap-2 text-sm">
                            <History className="size-4" aria-hidden /> Atividade recente
                        </CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/admin/audit-logs">
                                Auditoria <ArrowRight className="size-3.5" aria-hidden />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <ul className="divide-y divide-border">
                            {activity.map((row) => (
                                <li key={row.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {row.action_label} · {row.entity}
                                            {row.entity_id !== null ? ` #${row.entity_id}` : ''}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">{row.user}</p>
                                    </div>
                                    <span className="shrink-0 text-xs text-muted-foreground tabular-nums">{row.created_at}</span>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            ) : (
                <div className="mt-5">
                    <WEmptyStateMolecule
                        icon={<History className="size-5" />}
                        title="Nenhuma atividade ainda"
                        description="As ações administrativas (criar, editar, excluir, login) aparecem aqui conforme acontecem."
                    />
                </div>
            )}
        </WAppLayoutTemplate>
    );
}
