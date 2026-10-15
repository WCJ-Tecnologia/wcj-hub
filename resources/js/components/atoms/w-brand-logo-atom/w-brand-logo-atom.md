# WBrandLogoAtom

Átomo · `atoms/w-brand-logo-atom/w-brand-logo-atom.tsx` · story `Atoms/Brand Logo`

## O que faz

Mostra o logo do **WCJ Hub** a partir dos **arquivos oficiais** em `public/brand/`, copiados sem alteração de `docs/brand/logo/produtos/wcj-hub/` (completo e horizontal) e de `docs/brand/logo/` (o símbolo, que é o da WCJ). Você escolhe a **versão** pelo tamanho e a **superfície** pelo fundo. Por padrão, o logo é um link.

Nunca use `<img src="/brand/...">` direto nem redesenhe ou recolora o logo (`docs/brand/MARCA.md`, "O que não fazer").

## Qual versão usar

A regra da marca vale pela altura do **desenho**:

| `variant`    | Desenho                         | Quando                                                   |
| ------------ | ------------------------------- | -------------------------------------------------------- |
| `full`       | símbolo + "wcj" + "hub" embaixo | 40 px de altura ou mais (painel do login)                |
| `horizontal` | símbolo + "wcj" + "hub" ao lado | de 16 a 40 px (sidebar aberta, topbar, login no celular) |
| `symbol`     | só o símbolo da WCJ             | menos de 16 px, ou pouco espaço (sidebar recolhida)      |

Os SVG já trazem a área de proteção (o raio de um nó em volta), então o desenho é menor que a caixa (`size`).

## Qual superfície usar

| `surface` | Arquivo                                                 | Fundo                                          |
| --------- | ------------------------------------------------------- | ---------------------------------------------- |
| `dark`    | versão `fundo-escuro` (símbolo `#4D90FF`, "wcj" branco) | sidebar, painel do login: sempre escuros       |
| `light`   | versão `fundo-claro` (símbolo `#1A6CF3`, "wcj" ink)     | fundo sempre claro                             |
| `auto`    | as duas; o tema esconde uma                             | sobre a página, que troca entre claro e escuro |

## Props

| Prop        | Tipo                                   | Padrão         | Obrigatória | Para quê                                       |
| ----------- | -------------------------------------- | -------------- | ----------- | ---------------------------------------------- |
| `variant`   | `'full' \| 'horizontal' \| 'symbol'`   | `'horizontal'` | não         | Versão do desenho                              |
| `surface`   | `'light' \| 'dark' \| 'auto'`          | `'auto'`       | não         | Cor para o fundo                               |
| `size`      | `'xs' \| 'sm' \| 'md' \| 'lg' \| 'xl'` | `'md'`         | não         | Altura da caixa: 20, 24, 28, 40, 56 px         |
| `href`      | `string \| false`                      | `'/'`          | não         | Destino do link; `false` renderiza um `<span>` |
| `className` | `string`                               |                | não         | Vai no elemento externo (o link ou o span)     |

## Exemplos

```tsx
import { WBrandLogoAtom } from '@/components/atoms/w-brand-logo-atom/w-brand-logo-atom';

// Sidebar aberta
<WBrandLogoAtom variant="horizontal" surface="dark" size="md" href="/admin/dashboard" />
// Sidebar recolhida
<WBrandLogoAtom variant="symbol" surface="dark" size="xs" href="/admin/dashboard" />
// Painel do login
<WBrandLogoAtom variant="full" surface="dark" size="xl" href={false} />
// Topbar no celular
<WBrandLogoAtom variant="horizontal" surface="auto" size="sm" className="lg:hidden" href="/admin/dashboard" />
```

## Cuidados

- O `alt` é "WCJ Hub", então o link nunca fica sem nome (regra `link-name` do axe). Em `auto`, a imagem escondida tem `display: none` e sai da árvore de acessibilidade.
- Para esconder em algum tamanho de tela, passe a classe em `className` (ex.: `lg:hidden`). Esconder só a imagem deixaria um link vazio.
- Trocar o logo é trocar o SVG em `public/brand/`, a partir do mestre da marca. A configuração `APP_BRAND_LOGO` deixou de existir (ADR-005).
- Com `href`, o nome acessível do link é "Início" (`aria-label`), não o alt da imagem: a WCJ também é cliente (N7), e o link da ficha dela se chama "WCJ Tecnologia".
