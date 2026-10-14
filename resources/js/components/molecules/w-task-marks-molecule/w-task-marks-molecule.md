# WTaskMarksMolecule

Molécula · `molecules/w-task-marks-molecule/w-task-marks-molecule.tsx` · story `Molecules/Task Marks`

## O que faz

As marcas do card do quadro (F3 §03, zona Marcas, e a marca de atraso do rodapé). Cada uma é um `Badge` pequeno com ícone + texto curto (status nunca só por cor) e o detalhe no `Tooltip` e num `sr-only`:

| Marca        | Quando                            | Ícone           | Texto                  | Detail (tooltip)                                          | Badge     | Regra    |
| ------------ | --------------------------------- | --------------- | ---------------------- | --------------------------------------------------------- | --------- | -------- |
| Atraso       | `card.late`                       | `AlertTriangle` | "Atrasado"             | "Passou da data prevista (DD/MM/AAAA) sem chegar ao Done" | `danger`  | P53, R16 |
| Pausa        | a etapa atual é de papel `paused` | `PauseCircle`   | "Em pausa"             | o `pause_reason`, quando houver                           | `warning` | P15      |
| Retrabalho   | `rework_count > 0`                | `RotateCcw`     | "1 volta" / "2 voltas" | "Retrabalho: voltou 2 vezes"                              | `outline` | P39      |
| Suporte      | `type.slug === 'support'`         | `LifeBuoy`      | "Suporte"              | "Pedido por Marcos Vieira", quando há `support_requester` | `info`    | P52      |
| Reestimativa | `proposed_hours !== null`         | `Scale`         | "Proposta: 24 h"       | "Reestimativa proposta pelo dev, aguardando o gestor"     | `outline` | P27      |

A ordem é a da tabela: o que pede atenção primeiro. `taskMarks(card, inPausedStage)` é exportada para quem precisar só da lista (ex.: um filtro).

## Props

| Prop            | Tipo        | Padrão | Obrigatória | Para quê                                                        |
| --------------- | ----------- | ------ | ----------- | --------------------------------------------------------------- |
| `card`          | `BoardCard` |        | sim         | O card inteiro, como o `TaskPresenter` manda                    |
| `inPausedStage` | `boolean`   |        | sim         | `stage.role === 'paused'`: a marca de pausa só aparece aí (P15) |
| `className`     | `string`    |        | não         |                                                                 |

## Exemplo

```tsx
import { WTaskMarksMolecule } from '@/components/molecules/w-task-marks-molecule/w-task-marks-molecule';

<WTaskMarksMolecule card={card} inPausedStage={stage.role === 'paused'} />;
```

## Cuidados

- O atraso é conta do servidor (descontada a pausa, P53/R16): a tela só lê `card.late`.
- A marca de pausa depende da etapa, não do `pause_reason`: um card que saiu da pausa não mostra a marca, mesmo que o servidor mande o motivo antigo.
- Contato anonimizado chega como "removido a pedido do titular" (N12) no `support_requester`; a marca mostra como veio.
- Nada aqui trava: "Atrasado" e "Proposta" são referência (premissa de Projetos).
