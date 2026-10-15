# WTaskHoursSectionOrganism

Organismo · `organisms/w-task-hours-section-organism/w-task-hours-section-organism.tsx` · story `Organisms/Task Hours Section`

## O que faz

O conteúdo da seção _Horas_ da ficha do card (F5 §02 "Horas e data prevista"; F3 §02 "Horas previstas", §04; P27, P28, P30). A página o põe dentro do `WCollapsibleSectionMolecule`, com o resumo de `hoursSummary()` no cabeçalho recolhido.

- Quatro números (`dl`, quatro colunas a partir de `sm:`, em Space Mono): **Horas previstas** (`hours()` ou "—"), **Data prevista** (`dateBr` ou "—"), **Horas lançadas** (soma de todos, P30) e **Faltam** (`remainingHours`, "—" sem previstas). Embaixo, a dica "calculada na 1ª entrada em Doing; vazia sem dev ou sem horas" (P28).
- **Proposta pendente** (`proposed_hours`): bloco com `Badge warning` "Reestimativa proposta", "Proposta: 24 h, aguardando o gestor" (`proposalText`) e "por Bruno Lima". Com `decide_hours`: _Aprovar_ e _Recusar_ (P27).
- **Gestor e Administrador** (`set_hours`): _Definir_ (sem horas) ou _Reestimar_ abre o campo "Horas previstas" (dica "Total da tarefa, dev + QA"), com _Salvar_ e _Cancelar_.
    - Valor novo que muda a data prevista (`previewDueOn` diferente de `due_on`): `WConfirmDialogMolecule` "Reestimar SGM-17?" / "A data prevista vai de 09/10 para 14/10. Confirmar?" (`dueChangeText`, F3 §04); o `onSetHours` só sai no _Reestimar_.
    - Sem data, ou data que não muda: `onSetHours` direto.
    - Campo vazio com horas existentes: "Limpar as horas previstas de SGM-17?" e `onSetHours(null)`.
    - O mesmo valor: fecha o campo sem gravar.
- **Desenvolvedor** (`propose_hours`) sem proposta pendente: _Propor outro valor_ abre o campo "Proposta de horas" → `onPropose`.
- Valor ≤ 0, inválido ou vazio onde não se pode limpar: "Informe horas maiores que zero", sem chamar nada.
- `busy`: botões desabilitados, `aria-busy`, `Loader2` no botão que disparou. O campo fecha ao gravar; se chegar `errors.estimated_hours` (ou `errors.hours`), ele reabre com o valor digitado e o erro.

`hoursSummary(detail)`: "16 h previstas · 11,5 h lançadas · faltam 4,5 h · proposta de 24 h"; sem horas, "Sem horas previstas" (com "· 3 h lançadas" se já houver lançamento).

## Props

| Prop         | Tipo                                                                                                                                                                     | Padrão  | Obrigatória | Para quê                                         |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------- | ----------- | ------------------------------------------------ |
| `detail`     | `Pick<TaskDetail, 'key' \| 'estimated_hours' \| 'proposed_hours' \| 'proposed_by' \| 'logged_hours' \| 'due_on' \| 'development_started_on' \| 'developer_daily_hours'>` |         | sim         | A ficha (ou só esses campos)                     |
| `abilities`  | `Pick<TaskAbilities, 'set_hours' \| 'propose_hours' \| 'decide_hours'>`                                                                                                  |         | sim         | `detail.abilities`                               |
| `busy`       | `boolean`                                                                                                                                                                | `false` | não         | Uma gravação da ficha está rodando               |
| `errors`     | `Record<string, string>`                                                                                                                                                 |         | não         | `estimated_hours` (definir) e `hours` (propor)   |
| `onSetHours` | `(hours: number \| null) => void`                                                                                                                                        |         | sim         | Definir ou reestimar; `null` limpa (P27)         |
| `onPropose`  | `(hours: number) => void`                                                                                                                                                |         | sim         | Proposta do dev (P27)                            |
| `onApprove`  | `() => void`                                                                                                                                                             |         | sim         | Aprovar a proposta: vira as horas e refaz a data |
| `onReject`   | `() => void`                                                                                                                                                             |         | sim         | Recusar: só limpa a proposta                     |

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Horas" summary={hoursSummary(detail)}>
    <WTaskHoursSectionOrganism
        detail={detail}
        abilities={detail.abilities}
        busy={processing}
        errors={errors}
        onSetHours={(hours) => router.patch(detail.url, { estimated_hours: hours })}
        onPropose={(hours) => router.post(`${detail.url}/horas/proposta`, { hours })}
        onApprove={() => router.post(`${detail.url}/horas/proposta/aprovar`)}
        onReject={() => router.post(`${detail.url}/horas/proposta/recusar`)}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- A prévia da data (`previewDueOn`) só escreve a frase da confirmação. A data que vale é a do `DueDateService` do servidor (P28, P29); se as duas contas divergirem, a da tela está errada.
- Aprovar a proposta também muda a data, mas não pede confirmação: o F3 §04 só pede a frase no definir e no reestimar.
- O dev só mexe direto nas horas enquanto o card está no `Backlog` (F3 §10.4, proposta): quem decide é o servidor, mandando `set_hours = true`.
- Lançar horas (a lista "Horas da tarefa") é outra peça (F5), fora desta seção.
