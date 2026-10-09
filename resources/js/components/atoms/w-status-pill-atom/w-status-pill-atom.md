# WStatusPillAtom

Átomo · `atoms/w-status-pill-atom/w-status-pill-atom.tsx` · story `Atoms/Status Pill`

## O que faz

Pílula de status com ícone e texto, feita sobre o `Badge` do shadcn (variantes `success`, `warning`, `info`, `danger`). Recebe o **valor técnico** (`true`, `'active'`…) e traduz pelo dicionário da entidade (`kind`). O valor técnico nunca aparece na tela.

**Use** para status de registro numa lista ou num card.
**Não use** para rótulo livre (papel, contagem): para isso, use o `Badge` direto.

## Props

| Prop        | Tipo                                     | Padrão      | Obrigatória | Para quê                                                                  |
| ----------- | ---------------------------------------- | ----------- | ----------- | ------------------------------------------------------------------------- |
| `status`    | `string \| boolean \| null \| undefined` |             | sim         | Valor técnico. Booleano vira `active`/`inactive`. Nulo não renderiza nada |
| `kind`      | `'user' \| 'client'`                     |             | sim         | Dicionário que traduz o status                                            |
| `label`     | `string`                                 |             | não         | Substitui o rótulo do dicionário                                          |
| `size`      | `'default' \| 'sm'`                      | `'default'` | não         | `sm` para card de celular                                                 |
| `className` | `string`                                 |             | não         |                                                                           |

## Exemplo

```tsx
import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';

<WStatusPillAtom kind="user" status={row.active} />
<WStatusPillAtom kind="user" status={row.active} size="sm" />
```

## Status novo

Entidade nova com status: acrescente um dicionário (`const PROJECT: Record<string, Entry>`) e registre em `SETS`. Os tons são `pending`, `approved`, `rejected` e `neutral`. Status fora do dicionário aparece neutro, com o `label` ou com o próprio valor.

## Cuidados

Nunca comunique status só por cor: o ícone e o texto vêm sempre juntos.
