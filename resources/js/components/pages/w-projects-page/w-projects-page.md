# WProjectsPage

Tela · `pages/w-projects-page/w-projects-page.tsx` · story `Pages/Projetos`

## O que faz

Projetos (F2 §03 a §05): a lista dos projetos que a pessoa vê (P5), com busca e filtros, criar e editar por modal (decisão do Lucas Scholz, 09/10/2026), encerrar e reabrir (P7) e excluir só o nunca usado (G2). Encerrados ficam escondidos e, com o filtro, em cinza. Os botões da linha vêm da `ProjectPolicy` (`row.can`); "Novo projeto" só para quem tem `manage_projects` e recebeu `formOptions`. Trocar o responsável pede confirmação (F2 §04). Estados de lista pelo `WListBodyMolecule`; clique duplo pelo `useRowAction`.

O Laravel chama esta tela por `Inertia::render('projects/index')`; `resources/js/pages/projects/index.tsx` só reexporta.

## Props

`projects` (`Paginated<ProjectRow>`), `filters` (`ProjectFilters`), `filterOptions` (`{ clients, managers }`), `formOptions` (`{ clients, managers } | null`), `today` (`AAAA-MM-DD`)

## Exemplo

As props de exemplo ficam em `.storybook/fixtures.ts` (`PROJECTS`, `PROJECT_FILTERS`).

## Cuidados

O estado vazio fala com o dev quando ele não cria projeto (F2 §03). A ficha e o quadro (clicar no projeto) são do B3.
