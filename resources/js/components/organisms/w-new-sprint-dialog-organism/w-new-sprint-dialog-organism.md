# WNewSprintDialogOrganism

Organismo · `organisms/w-new-sprint-dialog-organism/w-new-sprint-dialog-organism.tsx` · story `Organisms/New Sprint Dialog`

## O que faz

Diálogo _Nova sprint_ do F5 §02 (R5, P45, P46): abre quando o gestor arrasta um card do Backlog e o projeto não tem sprint aberta. Texto: "Não há sprint aberta neste projeto. SGM-21 entra nesta sprint." Confirmar cria a sprint e põe o card nela, numa ação só; cancelar deixa o card no Backlog.

| Campo      | Padrão                                                                             | Regra na tela                                                                                                                         |
| ---------- | ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| Nome       | "Sprint N", N = `project.sprints_count + 1`                                        | obrigatório, até 80 (único no projeto: o servidor diz)                                                                                |
| Objetivo   | vazio                                                                              | opcional, até 500                                                                                                                     |
| Início     | `today`                                                                            | obrigatório                                                                                                                           |
| Fim        | `addBusinessDays(início, project.sprint_days)`, contando o início como 1º dia útil | obrigatório; recalcula quando o início muda, até a pessoa mexer no fim; antes do início: "O fim não pode ser antes do início" na hora |
| Dias úteis | `businessDaysBetween(início, fim)`                                                 | só leitura, seg–sex inclusive, sem feriado (P29, P48)                                                                                 |

As contas de dia útil estão em `lib/dias-uteis.ts`, sobre `AAAA-MM-DD` em UTC (nunca o fuso do navegador). Ex. do F5: terça 06/10/2026 com 10 dias → segunda 19/10/2026.

## Props

| Prop                   | Tipo                                  | Padrão  | Obrigatória | Para quê                               |
| ---------------------- | ------------------------------------- | ------- | ----------- | -------------------------------------- |
| `open`, `onOpenChange` |                                       |         | sim         | controle do diálogo                    |
| `card`                 | `BoardCard`                           |         | sim         | o card que entra na sprint             |
| `project`              | `BoardProject`                        |         | sim         | `sprints_count` e `sprint_days`        |
| `today`                | `string` (`AAAA-MM-DD`)               |         | sim         | hoje no fuso de São Paulo, da página   |
| `loading`              | `boolean`                             | `false` | não         | spinner, campos travados, não fecha    |
| `errors`               | `Record<string, string>`              |         | não         | `name`, `goal`, `starts_on`, `ends_on` |
| `onConfirm`            | `(payload: NewSprintPayload) => void` |         | sim         | `{ name, goal, starts_on, ends_on }`   |

## Exemplo

Pelo `useBoardMove`, que põe o payload em `MovePayload.new_sprint` e tira o prefixo `new_sprint.` dos erros do Laravel. Sozinho:

```tsx
<WNewSprintDialogOrganism
    open
    onOpenChange={close}
    card={intent.card}
    project={project}
    today={today}
    onConfirm={(sprint) => send({ stage_id: intent.to.id, new_sprint: sprint })}
/>
```

## Cuidados

- Só o gestor do projeto e o Administrador tiram card do Backlog (P45): o `classifyMove` recusa o dev antes de abrir.
- A capacidade (P48, P49) é referência e não aparece aqui; nada neste diálogo trava por número.
