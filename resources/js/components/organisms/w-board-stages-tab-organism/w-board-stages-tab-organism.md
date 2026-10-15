# WBoardStagesTabOrganism

Organismo · `organisms/w-board-stages-tab-organism/w-board-stages-tab-organism.tsx` · story `Organisms/Etapas do quadro`

## O que faz

A aba Etapas da configuração do projeto (F2 §02 a §04; P8, P10–P12): lista das etapas ativas na ordem do quadro (Posição, Nome, Papel, Cards na etapa), reordenável por arrastar (dnd-kit: alça com ponteiro ou teclado), com **Nova etapa**, **Editar** e **Remover** para quem gere. Fila, Sprint e Suporte reordenam e renomeiam, mas não têm Remover (P11). Escreve em `POST /projetos/{id}/etapas`, `PUT …/etapas/{etapa}`, `PUT …/etapas/ordem` (`ids[]`) e `DELETE …/etapas/{etapa}` (`destination_id` quando há cards).

## Props

| Prop        | Tipo                | Para quê                                       |
| ----------- | ------------------- | ---------------------------------------------- |
| `projectId` | `number`            | as rotas                                       |
| `stages`    | `StageRow[]`        | as etapas ativas, na ordem                     |
| `roles`     | `StageRoleOption[]` | `options.stage_roles`; `[]` para quem não gere |
| `canManage` | `boolean`           | mostra alça e botões                           |

## Cuidados

Ao soltar, a lista já mostra a ordem nova e manda o quadro inteiro; se o servidor recusar (toast), volta para a ordem que veio nas props. Com o papel congelado (`role_locked`), a edição manda só o nome. Não usa o `WDataTableOrganism` porque a linha precisa do `useSortable`.
