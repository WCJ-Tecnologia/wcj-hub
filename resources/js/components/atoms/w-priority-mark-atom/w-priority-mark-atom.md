# WPriorityMarkAtom

Átomo · `atoms/w-priority-mark-atom/w-priority-mark-atom.tsx` · story `Atoms/Priority Mark`

## O que faz

A prioridade do card (P19: Baixa, Normal, Alta, Urgente), em dois formatos:

- `bar` (padrão): a faixa de 4 px na borda superior do card do quadro (F3 §03, zona Borda; decisão de Lucas Scholz em 14/10, no lugar da lateral esquerda). Posição `absolute`, de ponta a ponta do pai. Leva um `span.sr-only` "Prioridade: Alta" e o mesmo texto no `title`: a cor nunca é a única pista.
- `chip`: ícone + rótulo em linha, para o submenu _Prioridade_ do card e para os filtros.

| Prioridade | Faixa                    | Ícone (chip)                 |
| ---------- | ------------------------ | ---------------------------- |
| Baixa      | `bg-muted-foreground/40` | `ArrowDown`, cinza           |
| Normal     | `bg-primary/60`          | `Minus`, `text-primary-text` |
| Alta       | `bg-status-pending`      | `ArrowUp`, amarelo de status |
| Urgente    | `bg-status-rejected`     | `ChevronsUp`, vermelho       |

O rótulo vem de `PRIORITY_LABEL` (`lib/quadro.ts`).

## Props

| Prop        | Tipo              | Padrão  | Obrigatória | Para quê                                  |
| ----------- | ----------------- | ------- | ----------- | ----------------------------------------- |
| `priority`  | `PrioritySlug`    |         | sim         | `low`, `normal`, `high`, `urgent`         |
| `variant`   | `'bar' \| 'chip'` | `'bar'` | não         | Faixa na borda ou ícone + rótulo em linha |
| `className` | `string`          |         | não         |                                           |

## Exemplo

```tsx
import { WPriorityMarkAtom } from '@/components/atoms/w-priority-mark-atom/w-priority-mark-atom';

<div className="relative overflow-hidden rounded-md border pl-3">
    <WPriorityMarkAtom priority={card.priority} />
    …
</div>

<DropdownMenuRadioItem value="urgent">
    <WPriorityMarkAtom priority="urgent" variant="chip" />
</DropdownMenuRadioItem>
```

## Cuidados

- `bar` precisa de um pai com `relative` (e `overflow-hidden`, para a faixa seguir o canto arredondado).
- Prioridade é só ordem (F3 §03): nada aqui sugere trava.
