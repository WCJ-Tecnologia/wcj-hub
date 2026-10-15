# WSprintTasksOrganism

Organismo · `organisms/w-sprint-tasks-organism/w-sprint-tasks-organism.tsx` · aparece na story `Pages/Sprints do projeto`

## O que faz

Tarefas da sprint (F5 §03, §05): Chave (link para a ficha), Título, Etapa, Dev, QA, Previstas, Lançadas e Data prevista, pela ordem das etapas do quadro e da chave; arquivadas não vêm (P26). Busca por chave ou título; filtros Dev, Etapa e os rápidos _Sem dev_ e _Sem horas previstas_, todos na URL.

Na sprint aberta, para quem gere (`canManage`, a ability `manageTasks`):

- horas previstas na linha (lápis → campo; Enter salva, Esc cancela; vazio limpa), `PUT /projetos/{id}/tarefas/{task}/horas-previstas`; a data prevista é refeita no servidor (P28);
- a marca "Reestimativa proposta: 24 h" com _Aprovar_ e _Recusar_ (P27);
- _Tirar da sprint_, com confirmação: o card volta ao Backlog, sem retrabalho (P45).

Vazio: "Nenhuma tarefa nesta sprint." com _Abrir o quadro_.

## Props

| Prop        | Tipo             | Para quê                                   |
| ----------- | ---------------- | ------------------------------------------ |
| `projectId` | `number`         | as rotas                                   |
| `sprint`    | `SelectedSprint` | as tarefas e as opções dos filtros         |
| `filters`   | `SprintFilters`  | busca e filtros                            |
| `canManage` | `boolean`        | as escritas da linha (só na sprint aberta) |
