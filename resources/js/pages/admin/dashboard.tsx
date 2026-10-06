import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Building2, History, UserCheck, Users } from 'lucide-react';

import { EmptyState } from '@/components/empty-state';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface Summary {
    users_active: number;
    users_total: number;
    enterprises_active: number;
    enterprises_total: number;
}

interface ActivityRow {
    id: number;
    user: string;
    action_label: string;
    entity: string;
    entity_id: number;
    created_at: string | null;
}

interface Props {
    summary: Summary;
    /** Only administrators receive the audit trail; others get an empty list. */
    activity: ActivityRow[];
}

export default function Dashboard({ summary, activity }: Props) {
    return (
        <AppLayout title="Dashboard" subtitle="Visão geral do sistema">
            <Head title="Dashboard" />

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Usuários ativos" value={summary.users_active} icon={UserCheck} tone="approved" />
                <StatCard
                    label="Usuários cadastrados"
                    value={summary.users_total}
                    icon={Users}
                    tone="primary"
                    hint={`${summary.users_total - summary.users_active} inativos`}
                />
                <StatCard label="Empresas ativas" value={summary.enterprises_active} icon={Building2} tone="approved" />
                <StatCard
                    label="Empresas cadastradas"
                    value={summary.enterprises_total}
                    icon={Building2}
                    tone="primary"
                    hint={`${summary.enterprises_total - summary.enterprises_active} inativas`}
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
                                            {row.action_label} · {row.entity} #{row.entity_id}
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
                    <EmptyState
                        icon={<History className="size-5" />}
                        title="Nenhuma atividade ainda"
                        description="As ações administrativas (criar, editar, excluir, login) aparecem aqui conforme acontecem."
                    />
                </div>
            )}
        </AppLayout>
    );
}
