# WStartSprintDialogOrganism

Organismo · `organisms/w-start-sprint-dialog-organism/w-start-sprint-dialog-organism.tsx` · story `Organisms/Start Sprint Dialog`

## O que faz

A confirmação do _Montar sprint_ do quadro (D-GER-35; F5 §01, §02, §04), aberta pela barra depois de marcar as tarefas no Backlog.

- **Sem sprint aberta**, _Iniciar sprint_: os campos da `WSprintFieldsMolecule` já preenchidos pela `proposedSprint()` de `lib/quadro.ts` ("Sprint N" com N = `sprints_count + 1`, início hoje, fim pela duração padrão em dias úteis contando o início, objetivo opcional; a mesma proposta do diálogo do arrastar, P46, R5). Confirmar cria a sprint com as tarefas, numa ação só.
- **Com sprint aberta**, _Pôr na Sprint N_: só a confirmação; as tarefas saem do Backlog e vão para o To do da sprint aberta (P45).

Nos dois casos lista as marcadas (chave e título) com o resumo "3 selecionadas · 20 h previstas (1 sem horas)". Devolve um `SprintBatchPayload` (`types/quadro.ts`) em `onConfirm`; a página manda para `POST /projetos/{id}/sprint-aberta/tarefas/lote`, rota que já existe no backend.

Erros do servidor (`errors`): `task_ids` em cima, em `role="alert"` (o lote recusado inteiro, com as chaves: "Nada foi gravado: SGM-30 já está na Sprint 3."); `new_sprint.*` nos campos; `name` (nome repetido, o mesmo erro do _Nova sprint_) no campo Nome.

## Props

| Prop                   | Tipo                                    | Obrigatória | Para quê                                                                 |
| ---------------------- | --------------------------------------- | ----------- | ------------------------------------------------------------------------ |
| `open`, `onOpenChange` |                                         | sim         | controle do diálogo (não fecha enquanto `loading`)                       |
| `project`              | `BoardProject`                          | sim         | `open_sprint` decide o caso; `sprints_count` e `sprint_days`, a proposta |
| `cards`                | `BoardCard[]`                           | sim         | as marcadas                                                              |
| `today`                | `string`                                | sim         | `AAAA-MM-DD` em São Paulo: o início proposto                             |
| `loading`              | `boolean`                               | não         | spinner e trava enquanto grava                                           |
| `errors`               | `Record<string, string>`                | não         | erros do servidor                                                        |
| `onConfirm`            | `(payload: SprintBatchPayload) => void` | sim         | a intenção de gravar                                                     |

## Cuidados

- O diálogo não decide regra: a recusa (tarefa que saiu do Backlog, sprint aberta por outra pessoa no meio, permissão) é do servidor.
- Desmonta ao fechar: a próxima abertura volta à proposta.
