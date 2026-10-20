# WHoursTabOrganism

Organismo · `organisms/w-hours-tab-organism/w-hours-tab-organism.tsx` · story `Organisms/Indicadores/Horas`

## O que faz

Aba _Horas_ (F7 §03, §06; P27, P30, R17): por tarefa, Chave, Título, Dev, Etapa atual, Previstas, Lançadas, Diferença (lançadas − previstas; positiva em destaque = passou do previsto; "—" sem previstas) e Tempo em desenvolvimento (dias úteis em etapa de desenvolvimento × carga do dev, "estimado pelo quadro; não substitui o lançamento de horas"). A arquivada vem com a marca; as previstas dela ficam fora do total (F7 §10.5). Totais no fim. Vazia: "Nenhuma tarefa com horas previstas ou lançadas com esses filtros".

## Props

| Prop   | Tipo       | Obrigatória | Para quê                                         |
| ------ | ---------- | ----------- | ------------------------------------------------ |
| `data` | `HoursTab` | sim         | linhas e totais, como o `EffortIndicators` manda |

## Cuidados

- `differenceCell()` é exportada para a aba usar nos totais com o mesmo destaque.
