# WSprintFormDialogOrganism

Organismo · `organisms/w-sprint-form-dialog-organism/w-sprint-form-dialog-organism.tsx` · story `Organisms/Sprint Form Dialog`

## O que faz

_Nova sprint_ e _Editar sprint_ da tela `/projetos/{id}/sprints` (F5 §02, §04). Os campos são os do `WSprintFieldsMolecule`. Na criação, abre com a proposta do servidor ("Sprint N", início hoje, fim pela duração padrão, P46) e o fim acompanha o início; na edição, com a sprint, e o fim fica onde está. Grava sozinho: `POST /projetos/{id}/sprints` ou `PUT /projetos/{id}/sprints/{sprint}`, e fecha no sucesso.

Não é o _Nova sprint_ do arrastar (o `WNewSprintDialogOrganism` do kit do quadro, que põe o card junto, R5).

## Props

| Prop                   | Tipo                   | Obrigatória | Para quê                            |
| ---------------------- | ---------------------- | ----------- | ----------------------------------- |
| `open`, `onOpenChange` |                        | sim         | controle do diálogo                 |
| `projectId`            | `number`               | sim         | as rotas                            |
| `sprintDays`           | `number`               | sim         | duração padrão do projeto (P46)     |
| `sprint`               | `SprintRow \| null`    | sim         | a sprint na edição; null na criação |
| `proposal`             | `SprintFields \| null` | sim         | a proposta do servidor, na criação  |

## Cuidados

- O botão _Nova sprint_ só aparece sem sprint aberta (P45); com aberta, o servidor recusa ("A Sprint 4 está aberta neste projeto: encerre-a antes de criar outra.").
- Nome repetido no projeto volta como erro do campo.
