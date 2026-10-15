# WSprintListOrganism

Organismo · `organisms/w-sprint-list-organism/w-sprint-list-organism.tsx` · aparece na story `Pages/Sprints do projeto`

## O que faz

Lista de sprints do projeto (F5 §03, §05): a aberta num card em destaque no topo (nome, objetivo, período, dias úteis, prontas de total) e as encerradas numa tabela, da mais recente para a mais antiga, com Nome, Período, Dias úteis, Objetivo, Situação ("Encerrada em DD/MM/AAAA") e Prontas. Busca por nome e filtro de situação (Todas, Aberta, Encerradas). Ações por linha, pelo `can` do servidor: _Ver tarefas_, _Editar_, _Encerrar_, _Reabrir_ e _Excluir_. Vazio: "Nenhuma sprint ainda." com o _Nova sprint_.

## Props

| Prop                                        | Tipo                       | Para quê                                   |
| ------------------------------------------- | -------------------------- | ------------------------------------------ |
| `projectId`, `sprints`, `selectedId`        |                            | a lista e qual está mostrada embaixo       |
| `filters`                                   | `SprintFilters`            | busca e situação                           |
| `isBusy`, `isLocked`                        | do `useRowAction`          | a trava de clique duplo das ações de linha |
| `onEdit`, `onClose`, `onReopen`, `onDelete` | `(row: SprintRow) => void` | a página abre o diálogo ou grava           |
| `newButton`                                 | `ReactNode`                | o _Nova sprint_, quando pode criar         |
