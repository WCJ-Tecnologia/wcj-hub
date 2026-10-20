import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, History, KanbanSquare } from 'lucide-react';

import { WTaskHistorySummaryOrganism } from '@/components/organisms/w-task-history-summary-organism/w-task-history-summary-organism';
import { WTaskTimelineOrganism } from '@/components/organisms/w-task-timeline-organism/w-task-timeline-organism';
import { Button } from '@/components/shadcn/button';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import type { TaskHistoryPageData } from '@/types/indicadores';

export type WTaskHistoryPageProps = TaskHistoryPageData;

/**
 * A linha do tempo do card (Inertia: `projects/task-history`, `/projetos/{id}/cards/{chave}/historico`;
 * F7 §01 a §04): o resumo no topo e cada movimento, do mais antigo para o mais recente. Abre pelo
 * botão *Histórico* da ficha; o Administrador tem *Ver na auditoria*, com o que a linha do tempo
 * não mostra (edições de campos, reestimativas, trocas de dev e QA). Só leitura.
 */
export function WTaskHistoryPage({ project, task, summary, movements, audit_url }: WTaskHistoryPageProps) {
    return (
        <WAppLayoutTemplate
            eyebrow={`Projetos · ${project.key_prefix}`}
            title={`${task.key} · ${task.title}`}
            subtitle={`Histórico · ${project.name} · ${project.client}`}
            width="wide"
            actions={
                <>
                    <Button variant="outline" asChild>
                        <Link href={task.url}>
                            <ArrowLeft className="size-4" aria-hidden /> Voltar à ficha
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/quadro`}>
                            <KanbanSquare className="size-4" aria-hidden /> Quadro
                        </Link>
                    </Button>
                    {audit_url && (
                        <Button variant="outline" asChild>
                            <Link href={audit_url}>
                                <History className="size-4" aria-hidden /> Ver na auditoria
                            </Link>
                        </Button>
                    )}
                </>
            }
        >
            <Head title={`Histórico · ${task.key}`} />

            <div className="space-y-8">
                <WTaskHistorySummaryOrganism summary={summary} />
                <WTaskTimelineOrganism movements={movements} />
            </div>
        </WAppLayoutTemplate>
    );
}
