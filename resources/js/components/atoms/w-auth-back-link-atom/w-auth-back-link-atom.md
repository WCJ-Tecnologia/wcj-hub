# WAuthBackLinkAtom

Átomo · `atoms/w-auth-back-link-atom/w-auth-back-link-atom.tsx` · sem story (simples demais)

## O que faz

Link centralizado no rodapé das telas de autenticação, como "Voltar para o login". Usa o `Link` do Inertia.

## Props

| Prop       | Tipo        | Obrigatória | Para quê      |
| ---------- | ----------- | ----------- | ------------- |
| `href`     | `string`    | sim         | Destino       |
| `children` | `ReactNode` | sim         | Texto do link |

## Exemplo

```tsx
import { WAuthBackLinkAtom } from '@/components/atoms/w-auth-back-link-atom/w-auth-back-link-atom';

<WAuthBackLinkAtom href="/admin/login">Voltar para o login</WAuthBackLinkAtom>;
```
