# WCardCountersMolecule

Molécula · `molecules/w-card-counters-molecule/w-card-counters-molecule.tsx` · story `Molecules/Card Counters`

## O que faz

Os contadores do rodapé do card do quadro (F3 §03), cada um só quando há valor:

| Item              | Ícone           | Quando                 | Leitor de tela              | Regra    |
| ----------------- | --------------- | ---------------------- | --------------------------- | -------- |
| Data prevista     | `Calendar`      | `dueOn` não nulo       | "Data prevista: 09/10/2026" | P28 (F5) |
| Nº de anexos      | `Paperclip`     | `attachmentsCount > 0` | "3 anexos"                  | P24      |
| Nº de comentários | `MessageSquare` | `commentsCount > 0`    | "5 comentários"             | P24      |

Números e data em Space Mono; a data passa por `dateBr()` (sem `Date`, para não perder um dia no fuso). Com `late`, a data fica em `text-status-rejected`; a marca "Atrasado" (ícone + texto) vem do `WTaskMarksMolecule`, então a cor não é a única pista.

Sem nenhum valor, não renderiza nada.

## Props

| Prop               | Tipo             | Padrão  | Obrigatória | Para quê                                |
| ------------------ | ---------------- | ------- | ----------- | --------------------------------------- |
| `dueOn`            | `string \| null` |         | sim         | `card.due_on`, `AAAA-MM-DD`             |
| `attachmentsCount` | `number`         |         | sim         | `card.attachments_count`                |
| `commentsCount`    | `number`         |         | sim         | `card.comments_count`                   |
| `late`             | `boolean`        | `false` | não         | `card.late`: data no vermelho de status |
| `className`        | `string`         |         | não         |                                         |

## Exemplo

```tsx
import { WCardCountersMolecule } from '@/components/molecules/w-card-counters-molecule/w-card-counters-molecule';

<WCardCountersMolecule
    dueOn={card.due_on}
    attachmentsCount={card.attachments_count}
    commentsCount={card.comments_count}
    late={card.late}
/>;
```

## Cuidados

- O texto para leitor de tela está em `sr-only`, não em `aria-label`: `aria-label` num `li`/`span` sem papel é proibido pelo ARIA 1.2 (regra `aria-prohibited-attr` do axe).
- A data prevista é referência (P28): nada trava quando passa.
