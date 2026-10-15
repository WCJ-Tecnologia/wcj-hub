# WCloseSprintDialogOrganism

Organismo · `organisms/w-close-sprint-dialog-organism/w-close-sprint-dialog-organism.tsx` · story `Organisms/Close Sprint Dialog`

## O que faz

Diálogo _Encerrar sprint_ do F5 §02 (P50, P51). Lista as tarefas que não chegaram a `Done` (chave, título, etapa e dev; arquivadas não vêm, P26) e, em cada uma, o destino: _Próxima sprint_ (padrão) ou _Backlog_. Embaixo, _Criar a próxima sprint_ com os campos do `WSprintFieldsMolecule`, já preenchidos pelo servidor (nome "Sprint N+1", início no 1º dia útil depois do fim, fim pela duração padrão). Com alguma tarefa indo para a próxima, criar é obrigatório e a caixa fica marcada e travada; sem nenhuma, dá para desmarcar (F5 §10.4).

O resumo antes do botão é a confirmação do F5 §04: "Encerrar a Sprint 4? 2 tarefas prontas ficam nela; 3 vão para a Sprint 5; 1 volta ao Backlog."

Grava em `POST /projetos/{id}/sprints/{sprint}/encerrar` com `{ destinations: { [taskId]: 'next' | 'backlog' }, create_next, next? }`; os erros da próxima vêm como `next.name` etc. e aparecem nos campos.

## Props

| Prop                   | Tipo                                              | Obrigatória | Para quê                                 |
| ---------------------- | ------------------------------------------------- | ----------- | ---------------------------------------- |
| `open`, `onOpenChange` |                                                   | sim         | controle do diálogo                      |
| `projectId`            | `number`                                          | sim         | a rota                                   |
| `sprint`               | `Pick<SprintRow, 'id' \| 'name' \| 'tasks_done'>` | sim         | a sprint aberta                          |
| `closing`              | `SprintClosing`                                   | sim         | tarefas não prontas e a próxima proposta |
| `sprintDays`           | `number`                                          | sim         | duração padrão do projeto (P46)          |

## Cuidados

- Card de suporte que volta ao Backlog só sai da sprint e fica na coluna dele (P52): é o servidor que decide.
- Voltar ao Backlog não conta como retrabalho.
