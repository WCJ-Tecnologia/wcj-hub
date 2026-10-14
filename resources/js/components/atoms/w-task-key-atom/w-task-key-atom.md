# WTaskKeyAtom

Átomo · `atoms/w-task-key-atom/w-task-key-atom.tsx` · story `Atoms/Task Key`

## O que faz

Mostra a chave do card (`SGM-17` = prefixo do projeto + número, P16) em Space Mono (`font-mono`), como todo rótulo e número da marca (ADR-005).

- Com `href`: link do Inertia para a ficha, em `text-primary-text` (azul de texto; nunca `text-primary`).
- Sem `href`: texto em `text-muted-foreground`, discreto.

## Props

| Prop        | Tipo                | Padrão      | Obrigatória | Para quê                                                    |
| ----------- | ------------------- | ----------- | ----------- | ----------------------------------------------------------- |
| `taskKey`   | `string`            |             | sim         | A chave (`key` é reservada no React)                        |
| `href`      | `string`            |             | não         | URL da ficha; vira link                                     |
| `title`     | `string`            |             | não         | Dica ao passar o mouse (ex.: o título do card pai)          |
| `size`      | `'sm' \| 'default'` | `'default'` | não         | `sm` = `text-xs` (linha do card pai), `default` = `text-sm` |
| `className` | `string`            |             | não         |                                                             |

## Exemplo

```tsx
import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';

<WTaskKeyAtom taskKey={card.key} />
<WTaskKeyAtom taskKey={card.parent.key} href={card.parent.url} title={card.parent.title} size="sm" />
```

## Cuidados

- A chave não é gravada no banco: o servidor monta `prefixo-número` (T4); a tela só mostra.
- No card do quadro a chave principal é texto (o título ao lado já é o link para a ficha): dois links para o mesmo lugar só somam parada de Tab.
