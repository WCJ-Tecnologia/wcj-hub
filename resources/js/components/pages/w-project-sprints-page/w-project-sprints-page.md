# WProjectSprintsPage

Página · `pages/w-project-sprints-page/w-project-sprints-page.tsx` · Inertia `projects/sprints` · rota `/projetos/{id}/sprints` · story `Pages/Sprints do projeto`

## O que faz

A tela de sprints do projeto (F5 §01 a §05; escopo B5): a lista (`WSprintListOrganism`, a aberta em destaque e as encerradas abaixo), as tarefas da sprint escolhida (`WSprintTasksOrganism`) e, para quem gere, o painel _Capacidade_ (`WCapacityPanelOrganism`). A sprint mostrada é a do `?sprint=`, senão a aberta, senão a mais recente; _Ver tarefas_ troca. _Nova sprint_ só aparece sem sprint aberta (P45). _Encerrar_ abre o `WCloseSprintDialogOrganism`; _Editar_, o `WSprintFormDialogOrganism`; _Reabrir_ grava direto (o servidor recusa com "Encerre a Sprint 5 antes de reabrir a Sprint 4."); _Excluir_ pede confirmação e só aparece na aberta sem tarefa.

Porta de entrada: _Sprints_ no cabeçalho da configuração do projeto e nas ações da linha da lista de projetos. O quadro (B3) também linka para cá.

## Props

Do `SprintScreen`: `project`, `sprints`, `selected`, `closing`, `proposal`, `can.manage_tasks`, `filters`, `today` (ver `types/sprints.ts`).

## Cuidados

- O dev vê a lista e as tarefas, sem capacidade e sem ação de escrita (F5 §09): o servidor manda `capacity = null` e o `can` falso.
- Nada aqui trava por capacidade ou horas (P49): a diferença negativa é só aviso.
