import { Head, Link, router } from '@inertiajs/react';
import { CalendarRange, KanbanSquare } from 'lucide-react';

import { WHoursTabOrganism } from '@/components/organisms/w-hours-tab-organism/w-hours-tab-organism';
import { WIndicatorsFiltersOrganism } from '@/components/organisms/w-indicators-filters-organism/w-indicators-filters-organism';
import { WMyTasksTabOrganism } from '@/components/organisms/w-my-tasks-tab-organism/w-my-tasks-tab-organism';
import { WPeopleTabOrganism } from '@/components/organisms/w-people-tab-organism/w-people-tab-organism';
import { WQualityTabOrganism } from '@/components/organisms/w-quality-tab-organism/w-quality-tab-organism';
import { WSituationTabOrganism } from '@/components/organisms/w-situation-tab-organism/w-situation-tab-organism';
import { WStageTimeTabOrganism } from '@/components/organisms/w-stage-time-tab-organism/w-stage-time-tab-organism';
import { Button } from '@/components/shadcn/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/shadcn/tabs';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { TAB_LABEL } from '@/lib/indicadores';
import type { IndicatorsPageData, IndicatorsTab } from '@/types/indicadores';

export type WProjectIndicatorsPageProps = IndicatorsPageData;

/** As abas com os filtros do projeto (F7 §05). */
const WITH_PROJECT_FILTERS: IndicatorsTab[] = ['tempo', 'horas', 'pessoas'];

/**
 * Indicadores do projeto (Inertia: `projects/indicators`, `/projetos/{id}/indicadores`; F7 §01 a
 * §06): as abas Situação, Tempo por etapa, Horas, Por pessoa, Qualidade e Minhas tarefas e horas.
 * O gestor e o Administrador têm as seis; o dev, só a dele (P54). A aba e os filtros ficam na URL
 * (`?aba=`); o servidor manda só o conteúdo da aba pedida. Nada aqui altera dado.
 */
export function WProjectIndicatorsPage(props: WProjectIndicatorsPageProps) {
    const { project, tabs, filters, options } = props;
    const active = tabs.includes(filters.tab) ? filters.tab : tabs[0];
    const url = `/projetos/${project.id}/indicadores`;

    const changeTab = (value: string) => router.get(url, { aba: value }, { preserveState: true, preserveScroll: true, replace: true });

    const content = (tab: IndicatorsTab) => {
        switch (tab) {
            case 'situacao':
                return props.situation && <WSituationTabOrganism data={props.situation} />;
            case 'tempo':
                return props.stage_time && <WStageTimeTabOrganism data={props.stage_time} />;
            case 'horas':
                return props.hours && <WHoursTabOrganism data={props.hours} />;
            case 'pessoas':
                return props.people && <WPeopleTabOrganism data={props.people} />;
            case 'qualidade':
                return props.quality && <WQualityTabOrganism data={props.quality} />;
            case 'minhas':
                return props.mine && <WMyTasksTabOrganism data={props.mine} />;
        }
    };

    return (
        <WAppLayoutTemplate
            eyebrow={`Projetos · ${project.key_prefix}`}
            title={`Indicadores · ${project.name}`}
            subtitle={project.client}
            width="wide"
            actions={
                <>
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/quadro`}>
                            <KanbanSquare className="size-4" aria-hidden /> Abrir o quadro
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/projetos/${project.id}/sprints`}>
                            <CalendarRange className="size-4" aria-hidden /> Sprints
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title={`Indicadores · ${project.name}`} />

            <Tabs value={active} onValueChange={changeTab} className="gap-4">
                <TabsList className="h-auto w-full flex-wrap justify-start gap-1 overflow-x-auto" aria-label="Abas dos indicadores">
                    {tabs.map((tab) => (
                        <TabsTrigger key={tab} value={tab} className="flex-none px-3">
                            {TAB_LABEL[tab]}
                        </TabsTrigger>
                    ))}
                </TabsList>

                {tabs.map((tab) => (
                    <TabsContent key={tab} value={tab} className="space-y-4">
                        {tab === active && WITH_PROJECT_FILTERS.includes(tab) && (
                            <WIndicatorsFiltersOrganism projectId={project.id} filters={filters} options={options} variant="project" />
                        )}
                        {tab === active && tab === 'minhas' && (
                            <WIndicatorsFiltersOrganism projectId={project.id} filters={filters} options={options} variant="mine" />
                        )}
                        {tab === active && content(tab)}
                    </TabsContent>
                ))}
            </Tabs>
        </WAppLayoutTemplate>
    );
}
