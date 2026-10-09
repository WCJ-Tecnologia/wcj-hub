# WProjectFiltersOrganism

Organismo · `organisms/w-project-filters-organism/w-project-filters-organism.tsx` · sem story (aparece em `Pages/Projetos`)

## O que faz

Busca (prefixo, nome, cliente) e filtros da lista de projetos (F2 §05): Cliente, Gestor e Tipo (Selects do shadcn, sentinela `all` = sem filtro) e a caixa **Mostrar encerrados** (abertos por padrão, P7). Exporta `ProjectFilters`.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `filters` | `ProjectFilters` | como o controller devolve |
| `clients`, `managers` | `NamedOption[]` | só os dos projetos que a pessoa vê (`filterOptions`) |

## Exemplo

Ver `pages/w-projects-page/w-projects-page.tsx`.

## Cuidados

A busca é o `WSearchInputMolecule` (espera a escrita em voo terminar, CR-1-10 do B1).
