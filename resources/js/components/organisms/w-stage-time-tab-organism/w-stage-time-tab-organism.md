# WStageTimeTabOrganism

Organismo · `organisms/w-stage-time-tab-organism/w-stage-time-tab-organism.tsx` · story `Organisms/Indicadores/Tempo por etapa`

## O que faz

Aba _Tempo por etapa_ (F7 §03, §06; P11, P53): uma linha por papel de etapa, na ordem do quadro, com as etapas ativas que têm esse papel, quantas tarefas passaram, o tempo somado e a média por tarefa, em dias úteis com uma casa decimal. Soma pelo papel, não pelo nome: renomear a etapa não muda a conta. O papel pronto fica fora. Vazia: "Nenhuma tarefa saiu do Backlog com esses filtros".

## Props

| Prop   | Tipo           | Obrigatória | Para quê                                      |
| ------ | -------------- | ----------- | --------------------------------------------- |
| `data` | `StageTimeTab` | sim         | as linhas, como o `SituationIndicators` manda |

## Cuidados

- Os filtros (sprint, pessoa, tipo, área, busca) são do `WIndicatorsFiltersOrganism`, na página.
