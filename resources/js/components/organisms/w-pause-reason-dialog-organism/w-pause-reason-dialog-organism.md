# WPauseReasonDialogOrganism

Organismo · `organisms/w-pause-reason-dialog-organism/w-pause-reason-dialog-organism.tsx` · story `Organisms/Pause Reason Dialog`

## O que faz

Diálogo _Motivo da pausa_ do F3 §02 (P15): abre quando o card vai para a coluna de pausa e o projeto exige o motivo (`project.pause_requires_reason`). Título "Pausar SGM-17", texto com o nome da coluna de pausa, um campo de texto obrigatório (até 500). Devolve `{ pause_reason }` por `onConfirm`. Quem decide abrir é o `classifyMove` (`lib/quadro.ts`), pelo `useBoardMove`.

## Props

| Prop                   | Tipo                                    | Padrão  | Obrigatória | Para quê                                                            |
| ---------------------- | --------------------------------------- | ------- | ----------- | ------------------------------------------------------------------- |
| `open`, `onOpenChange` | `boolean`, `(open) => void`             |         | sim         | controle do diálogo                                                 |
| `card`                 | `BoardCard`                             |         | sim         | a chave no título                                                   |
| `stage`                | `BoardStage`                            |         | sim         | a coluna de pausa (papel `paused`), pelo nome                       |
| `loading`              | `boolean`                               | `false` | não         | spinner no botão, campos travados, não fecha                        |
| `errors`               | `Record<string, string>`                |         | não         | erro do servidor em `pause_reason` (tem prioridade sobre o da tela) |
| `onConfirm`            | `(payload: PauseReasonPayload) => void` |         | sim         | `{ pause_reason: string }`, sem espaços nas pontas                  |

## Exemplo

```tsx
<WPauseReasonDialogOrganism
    open={pausing !== null}
    onOpenChange={(next) => !next && setPausing(null)}
    card={pausing.card}
    stage={pausing.to}
    loading={busy}
    errors={moveErrors}
    onConfirm={({ pause_reason }) => send({ stage_id: pausing.to.id, pause_reason })}
/>
```

No quadro, use o `useBoardMove` (`hooks/use-board-move.tsx`), que já monta este diálogo na ordem certa.

## Cuidados

- Validação na tela é conveniência: só obrigatório e tamanho (`maxLength`). O servidor decide (inegociável 9).
- Cancelar só fecha: o card fica onde estava porque a página só o move depois do `onConfirm` (F3 §04).
- O formulário vive dentro do `DialogContent`: o Radix desmonta ao fechar e o campo volta vazio na próxima abertura.
- Com a exigência desligada em F2, o card vai para a pausa sem diálogo (P15): isso é do `classifyMove`, não daqui.
