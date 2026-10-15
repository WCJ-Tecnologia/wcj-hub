# WRemoveBoardStageDialogOrganism

Organismo · `organisms/w-remove-board-stage-dialog-organism/w-remove-board-stage-dialog-organism.tsx` · story `Organisms/Remover etapa`

## O que faz

A confirmação de remover etapa (F2 §04, P12) sobre o `WConfirmDialogMolecule`. Sem cards: "Remover a etapa X?". Com cards: "N cards estão em X. Para onde vão?", com o Select **Mover as tarefas para** obrigatório (as outras etapas ativas do projeto, na ordem do quadro). Não deixa confirmar sem destino.

## Props

| Prop                   | Tipo                                      | Para quê                                           |
| ---------------------- | ----------------------------------------- | -------------------------------------------------- |
| `open`, `onOpenChange` |                                           | controle                                           |
| `stage`                | `StageRow \| null`                        | a etapa que sai                                    |
| `destinations`         | `StageRow[]`                              | as outras etapas ativas                            |
| `loading`              | `boolean`                                 | a remoção está no servidor                         |
| `error`                | `string`                                  | `errors.destination_id` do servidor                |
| `onConfirm`            | `(destinationId: number \| null) => void` | a aba faz o `DELETE /projetos/{id}/etapas/{etapa}` |

## Cuidados

Fila, Sprint e Suporte nem têm o botão Remover (P11); a última etapa de Desenvolvimento o servidor recusa com toast (P43). O diálogo não sabe disso: só pede o destino.
