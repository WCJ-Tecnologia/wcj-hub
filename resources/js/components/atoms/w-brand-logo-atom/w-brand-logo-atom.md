# WBrandLogoAtom

Átomo · `atoms/w-brand-logo-atom/w-brand-logo-atom.tsx` · story `Atoms/Brand Logo`

## O que faz

Mostra o logo e, opcionalmente, o nome da marca do cliente. Lê `app.brand` das props compartilhadas do Inertia (`APP_BRAND_NAME` e `APP_BRAND_LOGO` no `.env`). Por padrão é um link.

**Use** no topo do menu lateral, na topbar (celular) e nas telas de autenticação.
**Não use** para o nome do produto (WCJ Hub). Para isso existe o `WProductWordmarkAtom`.

## Props

| Prop        | Tipo                   | Padrão | Obrigatória | Para quê                                                |
| ----------- | ---------------------- | ------ | ----------- | ------------------------------------------------------- |
| `size`      | `'sm' \| 'md' \| 'lg'` | `'md'` | não         | Altura da imagem (28, 32 ou 44 px)                      |
| `showText`  | `boolean`              | `true` | não         | Mostra o nome da marca ao lado                          |
| `href`      | `string \| false`      | `'/'`  | não         | Destino do link; `false` renderiza um `<span>` sem link |
| `className` | `string`               |        | não         | Vai no elemento externo (o link ou o span)              |

## Exemplo

```tsx
import { WBrandLogoAtom } from '@/components/atoms/w-brand-logo-atom/w-brand-logo-atom';

<WBrandLogoAtom showText={false} href="/admin/dashboard" />
<WBrandLogoAtom size="lg" href={false} />
```

## Cuidados

- O `alt` da imagem é o nome da marca, então o link nunca fica sem nome (regra `link-name` do axe).
- Para esconder em algum tamanho de tela, passe a classe em `className` (ex.: `lg:hidden`). Esconder só um filho deixa um link vazio visível.
- Fora do Inertia (Storybook), as props vêm de `.storybook/inertia-mock.tsx`.
