import { Head, Link, router } from '@inertiajs/react';
import { KanbanSquare } from 'lucide-react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WBoardStagesTabOrganism } from '@/components/organisms/w-board-stages-tab-organism/w-board-stages-tab-organism';
import { WProjectAreasTabOrganism } from '@/components/organisms/w-project-areas-tab-organism/w-project-areas-tab-organism';
import { WProjectDefaultsTabOrganism } from '@/components/organisms/w-project-defaults-tab-organism/w-project-defaults-tab-organism';
import { WProjectMembersTabOrganism } from '@/components/organisms/w-project-members-tab-organism/w-project-members-tab-organism';
import { Button } from '@/components/shadcn/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/shadcn/tabs';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import type {
    AreaRow,
    MemberRow,
    ProjectSettingsFilters,
    ProjectSettingsOptions,
    SettingsProject,
    SettingsTab,
    StageRow,
} from '@/types/configuracao';

export interface WProjectSettingsPageProps {
    project: SettingsProject;
    members: MemberRow[];
    stages: StageRow[];
    /** Só para quem gere (F2 §09); null para o dev, que vê pessoas e etapas só leitura. */
    areas: AreaRow[] | null;
    options: ProjectSettingsOptions | null;
    filters: ProjectSettingsFilters;
}

const TABS: { value: SettingsTab; label: string; managersOnly?: boolean }[] = [
    { value: 'pessoas', label: 'Pessoas' },
    { value: 'etapas', label: 'Etapas' },
    { value: 'areas', label: 'Áreas', managersOnly: true },
    { value: 'padroes', label: 'Padrões', managersOnly: true },
];

/**
 * Configuração do projeto (Inertia: `projects/settings`, `/projetos/{id}/configuracao`; F2 §01 a
 * §05): as abas Pessoas, Etapas, Áreas e Padrões. A aba fica na URL (`?aba=`), para voltar a ela
 * depois de salvar e para dar link. O dev alocado vê Pessoas e Etapas só leitura.
 */
export function WProjectSettingsPage({ project, members, stages, areas, options, filters }: WProjectSettingsPageProps) {
    const canManage = project.can.manage;
    const tabs = TABS.filter((tab) => canManage || !tab.managersOnly);
    const active = tabs.some((tab) => tab.value === filters.tab) ? filters.tab : 'pessoas';

    const changeTab = (value: string) =>
        router.get(`/projetos/${project.id}/configuracao`, { aba: value }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <WAppLayoutTemplate
            eyebrow={`Projetos · ${project.key_prefix}`}
            title={`Configuração · ${project.name}`}
            subtitle={`${project.client} · gestor responsável ${project.manager}`}
            width="wide"
            actions={
                <>
                    <WStatusPillAtom
                        kind="project"
                        status={project.finished_at === null}
                        label={project.finished_at ? `Encerrado em ${project.finished_at}` : undefined}
                    />
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/quadro`}>
                            <KanbanSquare className="size-4" aria-hidden /> Abrir o quadro
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title={`Configuração · ${project.name}`} />

            {!canManage && (
                <p className="mb-4 rounded-md border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    Você vê as pessoas e as etapas deste projeto. Quem configura é o gestor do projeto ou o Administrador.
                </p>
            )}

            <Tabs value={active} onValueChange={changeTab} className="gap-4">
                <TabsList
                    variant="line"
                    className="w-full justify-start border-b border-border sm:w-auto"
                    aria-label="Partes da configuração"
                >
                    {tabs.map((tab) => (
                        <TabsTrigger key={tab.value} value={tab.value} className="flex-none px-3">
                            {tab.label}
                        </TabsTrigger>
                    ))}
                </TabsList>

                <TabsContent value="pessoas">
                    <WProjectMembersTabOrganism
                        projectId={project.id}
                        members={members}
                        people={options?.people ?? []}
                        canManage={canManage}
                    />
                </TabsContent>
                <TabsContent value="etapas">
                    <WBoardStagesTabOrganism
                        projectId={project.id}
                        stages={stages}
                        roles={options?.stage_roles ?? []}
                        canManage={canManage}
                    />
                </TabsContent>
                {canManage && areas && (
                    <TabsContent value="areas">
                        <WProjectAreasTabOrganism
                            projectId={project.id}
                            areas={areas}
                            showInactive={filters.show_inactive}
                            canManage={canManage}
                        />
                    </TabsContent>
                )}
                {canManage && (
                    <TabsContent value="padroes">
                        <WProjectDefaultsTabOrganism projectId={project.id} defaults={project.defaults} />
                    </TabsContent>
                )}
            </Tabs>
        </WAppLayoutTemplate>
    );
}
