# WAuthLayoutTemplate

Template · `templates/w-auth-layout-template/w-auth-layout-template.tsx` · story `Templates/Auth Layout`

## O que faz

A tela dividida das páginas sem login (login, esqueci a senha, nova senha, primeiro acesso):

- **desktop:** painel da marca à esquerda (logo, nome, aviso de acesso restrito e rodapé com o ano) e o conteúdo à direita;
- **celular:** só o conteúdo, com o logo pequeno no canto.

Ele já põe o `<Head title>`. O `<h1>` fica no conteúdo da página.

## Props

| Prop       | Tipo        | Obrigatória | Para quê                                       |
| ---------- | ----------- | ----------- | ---------------------------------------------- |
| `title`    | `string`    | sim         | Título da aba do navegador                     |
| `children` | `ReactNode` | sim         | Cabeçalho (`<h1>`), formulário e links da tela |

## Exemplo

```tsx
import { WAuthBackLinkAtom } from '@/components/atoms/w-auth-back-link-atom/w-auth-back-link-atom';
import { WAuthLayoutTemplate } from '@/components/templates/w-auth-layout-template/w-auth-layout-template';

<WAuthLayoutTemplate title="Esqueci minha senha">
    <h1 className="font-heading text-2xl font-bold tracking-tight">Esqueci minha senha</h1>
    {/* formulário */}
    <WAuthBackLinkAtom href="/admin/login">Voltar para o login</WAuthBackLinkAtom>
</WAuthLayoutTemplate>;
```
