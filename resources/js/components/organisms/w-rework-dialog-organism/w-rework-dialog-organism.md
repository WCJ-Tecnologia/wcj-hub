# WReworkDialogOrganism

Organismo · `organisms/w-rework-dialog-organism/w-rework-dialog-organism.tsx` · story `Organisms/Rework Dialog`

## O que faz

Diálogo _Devolver_ do F3 §02 (P39, P40): abre quando o card volta de Code-review, In test ou Done para To do ou Doing. Título "Devolver SGM-17 para To do".

- **Foi engano**: caixa "Arrastei para a coluna errada; não é retrabalho". Marcada, esconde origem e motivo; o movimento é gravado sem eles e não conta retrabalho (P39).
- **Origem**: Select com `rework_origins` (code review, teste cruzado, QA, homologação), obrigatório salvo engano.
- **Motivo**: Select com os `rework_reasons` recebidos (só ativos, G7), obrigatório salvo engano.
- **Texto do motivo**: aparece e é obrigatório (até 500) quando o motivo escolhido tem `slug === 'other'` (P40).
- Fora do engano, e só quando o card tem dev, avisa: "O dev do card será avisado no sistema e por e-mail." (P22).

## Props

| Prop                   | Tipo                                                 | Padrão     | Obrigatória | Para quê                                                                |
| ---------------------- | ---------------------------------------------------- | ---------- | ----------- | ----------------------------------------------------------------------- |
| `open`, `onOpenChange` |                                                      |            | sim         | controle do diálogo                                                     |
| `card`                 | `BoardCard`                                          |            | sim         | chave no título; `developer` decide o aviso                             |
| `to`                   | `BoardStage`                                         |            | sim         | coluna de destino, pelo nome                                            |
| `origins`              | `BoardOptions['rework_origins']`                     |            | sim         | origem (P39)                                                            |
| `reasons`              | `BoardOptions['rework_reasons']`                     |            | sim         | motivos ativos (P40)                                                    |
| `initial`              | `{ mistake?, rework_origin_id?, rework_reason_id? }` | tudo vazio | não         | valores iniciais (as stories usam)                                      |
| `loading`              | `boolean`                                            | `false`    | não         | spinner, campos travados, não fecha                                     |
| `errors`               | `Record<string, string>`                             |            | não         | `rework_origin_id`, `rework_reason_id`, `rework_reason_text`, `mistake` |
| `onConfirm`            | `(payload: ReworkPayload) => void`                   |            | sim         | ver abaixo                                                              |

`ReworkPayload` = `{ mistake: true }` ou `{ mistake: false; rework_origin_id; rework_reason_id; rework_reason_text? }` (o texto só vai com o motivo "Outro"). Encaixa direto no `MovePayload`.

## Exemplo

```tsx
<WReworkDialogOrganism
    open
    onOpenChange={close}
    card={intent.card}
    to={intent.to}
    origins={options.rework_origins}
    reasons={options.rework_reasons}
    loading={busy}
    errors={moveErrors}
    onConfirm={(rework) => send({ stage_id: intent.to.id, ...rework })}
/>
```

## Aviso do Done (P38)

Não há organismo próprio: ao mover para o Done com item do roteiro reprovado ou a testar, a página (pelo `useBoardMove`) usa o `WConfirmDialogMolecule`, não destrutivo, com o texto do `doneWarningText(card.qa_summary)` do `lib/quadro.ts` ("O roteiro tem N itens reprovados e M a testar. Mover mesmo assim?"). Avisa e deixa seguir (premissa).

## Cuidados

- Cancelar só fecha: o card fica onde estava (F3 §04).
- Não decide se é retrabalho: quem abre o diálogo é o `classifyMove`. O servidor confere de novo e é quem conta o ciclo, limpa o teste cruzado (P34) e volta o roteiro para A testar (P37).
- Motivo desativado depois de aberto o diálogo: o servidor recusa e o erro aparece no campo pelo `errors`.
