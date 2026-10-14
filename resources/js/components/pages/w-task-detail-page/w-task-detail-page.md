# WTaskDetailPage

Tela · `pages/w-task-detail-page/w-task-detail-page.tsx` · Inertia `projects/task` (`resources/js/pages/projects/task.tsx`) · story `Pages/Task Detail`

## O que faz

A ficha do card em `/projetos/{id}/cards/{chave}` (F3 §02 a §04, F5 §02), como o controller do B3 vai montar (**proposta do kit; o Cauã ajusta**):

- `WAppLayoutTemplate` largo: eyebrow "Projetos · <projeto>", h1 = a chave (`SGM-17`); o título do card fica no `WTaskDetailHeaderOrganism`, editável;
- o cabeçalho sempre visível (`WTaskDetailHeaderOrganism`): chave, tipo, área, card pai, título, marcas, capa, fatos só leitura, _Histórico_ e o menu ⋯;
- as **seções recolhidas** (F3 §01, R4: "tudo além do título é opcional e vem recolhido"), cada uma num `WCollapsibleSectionMolecule` com o resumo da seção no cabeçalho, em duas colunas no desktop:

| Coluna principal                                   | Coluna lateral                                          |
| -------------------------------------------------- | ------------------------------------------------------- |
| Requisição (`w-task-requisition-section-organism`) | Tipo, área e prioridade (`w-task-attributes-section-…`) |
| Critérios de aceite (`w-task-criteria-section-…`)  | Pessoas (`w-task-people-section-…`)                     |
| PRs (`w-task-pull-requests-section-…`)             | Horas previstas (`w-task-hours-section-…`)              |
| Comentários (`w-task-comments-section-…`)          | Card pai e cards filhos (`w-task-parent-section-…`)     |
| Anexos (`w-task-attachments-section-…`)            | Tarefa de origem (`w-task-origin-section-…`)            |

- _Mover para…_ passa pelo `useBoardMove` com os mesmos diálogos do quadro (nova sprint, pausa, retrabalho, aviso do Done); _Arquivar_ abre o `WArchiveDialogOrganism`; _Excluir_ abre o `WConfirmDialogMolecule` destrutivo com o texto do F3 §04.

**Rotas**: no comentário do topo do arquivo (o B3 confirma). O `PATCH` da ficha recebe só os campos tocados (`TaskPatchPayload`).

**Uma escrita por vez.** `pending` guarda o nome da escrita em curso; todas as seções recebem `busy` e desabilitam o que grava (uma visita não-GET cancelaria a outra no Inertia 2, como no `useRowAction`). Não há cópia otimista: a resposta volta com a ficha inteira (`task`) e o Inertia troca as props.

**Erro do servidor abre a seção.** `FIELD_SECTION` diz em que seção mora cada campo do payload; o `onError` abre essa seção e passa `errors` a ela. Erro sem campo conhecido vira toast.

## Props

| Prop      | Tipo                | Para quê                                                  |
| --------- | ------------------- | --------------------------------------------------------- |
| `project` | `BoardProject`      | Nome, prefixo, sprint aberta, regra da pausa (diálogos)   |
| `task`    | `TaskDetail`        | A ficha                                                   |
| `options` | `TaskDetailOptions` | Tipos, áreas, alocados, etapas, motivos, cards do projeto |
| `today`   | `string`            | `AAAA-MM-DD` em São Paulo (início proposto da sprint)     |

## Cuidados

- Anexar manda `files[]` com `forceFormData`; o servidor responde com a ficha (o Inertia aceita multipart no `router.post`).
- Excluir de verdade: o servidor redireciona para o quadro; a tela não tem para onde voltar sozinha.
- O estado "aberta/recolhida" das seções é da sessão da tela (não se guarda): a regra é começar recolhido.
