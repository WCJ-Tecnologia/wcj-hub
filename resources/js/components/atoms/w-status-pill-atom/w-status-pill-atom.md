# WStatusPillAtom

Átomo · `atoms/w-status-pill-atom/w-status-pill-atom.tsx` · story `Atoms/Status Pill`

## O que faz

Pílula de status com ícone e texto, feita sobre o `Badge` do shadcn (variantes `success`, `warning`, `info`, `danger`). Recebe o **valor técnico** (`true`, `'active'`…) e traduz pelo dicionário da entidade (`kind`). O valor técnico nunca aparece na tela.

**Use** para status de registro numa lista ou num card.
**Não use** para rótulo livre (papel, contagem): para isso, use o `Badge` direto.

## Props

| Prop        | Tipo                                                                                 | Padrão      | Obrigatória | Para quê                                                                  |
| ----------- | ------------------------------------------------------------------------------------ | ----------- | ----------- | ------------------------------------------------------------------------- |
| `status`    | `string \| boolean \| null \| undefined`                                             |             | sim         | Valor técnico. Booleano vira `active`/`inactive`. Nulo não renderiza nada |
| `kind`      | `'user' \| 'client' \| 'contact' \| 'audit' \| 'project' \| 'criterion' \| 'sprint'` |             | sim         | Dicionário que traduz o status (tabela abaixo)                            |
| `label`     | `string`                                                                             |             | não         | Substitui o rótulo do dicionário                                          |
| `size`      | `'default' \| 'sm'`                                                                  | `'default'` | não         | `sm` para card de celular                                                 |
| `className` | `string`                                                                             |             | não         |                                                                           |

## Dicionários (`kind`)

| `kind`      | Valores e rótulos                                                                 | Regra            |
| ----------- | --------------------------------------------------------------------------------- | ---------------- |
| `user`      | `active` Ativo, `inactive` Desativado                                             | F1 §02           |
| `client`    | `active` Ativo, `inactive` Desativado                                             | F1 §02, N11      |
| `contact`   | `active` Ativo, `inactive` Desativado, `anonymized` Anonimizado                   | F1 §02, N11, N12 |
| `audit`     | `success` Ok, `denied` Negado                                                     | F1 §03, G3       |
| `project`   | `active` Aberto, `inactive` Encerrado                                             | F2 §02, P7       |
| `criterion` | `pending` A testar (pendente), `approved` Aprovado, `failed` Reprovado (vermelho) | F3 §02, P36      |
| `sprint`    | `active` Aberta, `inactive` Encerrada                                             | F5 §02, P45, P50 |

## Exemplo

```tsx
import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';

<WStatusPillAtom kind="user" status={row.active} />
<WStatusPillAtom kind="user" status={row.active} size="sm" />
<WStatusPillAtom kind="criterion" status={criterion.result} size="sm" />
```

## Status novo

Entidade nova com status: acrescente um dicionário (`const PROJECT: Record<string, Entry>`) e registre em `SETS`. Os tons são `pending`, `approved`, `rejected` e `neutral`. Status fora do dicionário aparece neutro, com o `label` ou com o próprio valor.

## Cuidados

Nunca comunique status só por cor: o ícone e o texto vêm sempre juntos.
