# WIndicatorsFiltersOrganism

Organismo · `organisms/w-indicators-filters-organism/w-indicators-filters-organism.tsx` · story `Organisms/Indicadores/Filtros`

## O que faz

Os filtros das abas com filtro (F7 §05), tudo na URL. `variant="project"` (Tempo por etapa, Horas, Por pessoa): busca por chave ou título, Sprint (a em andamento por padrão; cada sprint; Todas; Fora de sprint), Pessoa (dev da tarefa), Tipo de tarefa e Área. `variant="mine"` (Minhas tarefas e horas): busca e Prazo (Atrasadas; Vencem até uma data, com o `WDatePickerMolecule`; Sem data prevista). O servidor refaz as contas a cada mudança.

## Props

| Prop        | Tipo                  | Obrigatória | Para quê                            |
| ----------- | --------------------- | ----------- | ----------------------------------- |
| `projectId` | `number`              | sim         | a rota `/projetos/{id}/indicadores` |
| `filters`   | `IndicatorsFilters`   | sim         | o que vale agora (do servidor)      |
| `options`   | `IndicatorsOptions`   | sim         | sprints, pessoas, tipos e áreas     |
| `variant`   | `'project' \| 'mine'` | sim         | qual conjunto de filtros mostrar    |

## Cuidados

- `filterParams()` devolve só os parâmetros da aba: trocar de aba não arrasta filtro de outra.
- A tarefa conta na sprint em que está agora (P50, P51).
