# WProjectIndicatorsPage

Página · `pages/w-project-indicators-page/w-project-indicators-page.tsx` · story `Pages/Indicadores do projeto` · Inertia `projects/indicators` · `/projetos/{id}/indicadores`

## O que faz

Indicadores do projeto (F7 §01 a §06): as abas Situação, Tempo por etapa, Horas, Por pessoa, Qualidade e Minhas tarefas e horas. O gestor do projeto e o Administrador têm as seis (`ProjectPolicy::viewIndicators`); o dev, só _Minhas tarefas e horas_ (P54, F7 §10.2). A aba e os filtros ficam na URL (`?aba=`); o servidor (`IndicatorsScreen`) manda só o conteúdo da aba pedida e as outras vêm nulas. Nada aqui altera dado.

## Props

As de `IndicatorsPageData` (`types/indicadores.ts`): `project`, `tabs` (as que a pessoa pode ver), `filters`, `options`, `today` e uma de `situation`, `stage_time`, `hours`, `people`, `quality`, `mine`.

## Cuidados

- Os filtros do projeto aparecem só em Tempo por etapa, Horas e Por pessoa; Situação e Qualidade são sempre o projeto inteiro (F7 §05).
- Pedir uma aba que a pessoa não tem cai na primeira dela, sem o conteúdo: o servidor decide.
