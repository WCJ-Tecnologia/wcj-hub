# WProductWordmarkAtom

Átomo · `atoms/w-product-wordmark-atom/w-product-wordmark-atom.tsx` · story `Atoms/Product Wordmark`

## O que faz

Escreve o nome do produto (`APP_NAME`, lido de `app.name` nas props do Inertia) em texto pequeno e discreto. Hoje aparece no rodapé do menu lateral.

## Props

| Prop        | Tipo     | Padrão | Obrigatória | Para quê                                              |
| ----------- | -------- | ------ | ----------- | ----------------------------------------------------- |
| `className` | `string` |        | não         | Cor e espaçamento (ex.: `text-sidebar-foreground/70`) |

## Exemplo

```tsx
import { WProductWordmarkAtom } from '@/components/atoms/w-product-wordmark-atom/w-product-wordmark-atom';

<WProductWordmarkAtom className="px-1 text-sidebar-foreground/70" />;
```
