# WAppLayoutTemplate

Template · `templates/w-app-layout-template/w-app-layout-template.tsx` · story `Templates/App Layout`

## O que faz

O shell de toda tela logada do backoffice. Monta, nesta ordem:

1. link "Ir para o conteúdo" (aparece ao focar com Tab);
2. `SidebarProvider` + `WAppSidebarOrganism` (menu lateral; gaveta no celular);
3. `WAppTopbarOrganism`;
4. `<main id="conteudo">` com `WPageContainerAtom` (largura) e `WPageHeaderMolecule` (o `<h1>`);
5. o seu conteúdo (`children`);
6. o `Toaster` do Sonner: **o flash do servidor vira toast sozinho** (`flash.success` → toast de sucesso, `flash.error` → toast de erro).

Toda tela nova logada começa com ele. Não crie outro layout por área sem ADR (`.ai/stacks/ui.md`).

## Props

| Prop       | Tipo                              | Padrão      | Obrigatória | Para quê                       |
| ---------- | --------------------------------- | ----------- | ----------- | ------------------------------ |
| `title`    | `string`                          |             | sim         | O `<h1>` da página             |
| `subtitle` | `string`                          |             | não         | Frase abaixo do título         |
| `eyebrow`  | `string`                          |             | não         | Grupo do menu, acima do título |
| `actions`  | `ReactNode`                       |             | não         | Botões à direita do título     |
| `width`    | `'narrow' \| 'default' \| 'wide'` | `'default'` | não         | Ver `WPageContainerAtom`       |
| `children` | `ReactNode`                       |             | sim         | O conteúdo da tela             |

## Exemplo

```tsx
import { Head } from '@inertiajs/react';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';

export function WProjectsPage({ projects }: WProjectsPageProps) {
    return (
        <WAppLayoutTemplate eyebrow="Projetos" title="Projetos" subtitle="Entregas e produtos contínuos" actions={newButton}>
            <Head title="Projetos" />
            {/* filtros, WDataTableOrganism, WPaginationMolecule */}
        </WAppLayoutTemplate>
    );
}
```

## Toast fora do flash

Para avisar algo que não veio do servidor, chame o Sonner direto:

```ts
import { toast } from 'sonner';
toast.success('Copiado.');
```

## Cuidados

- O `<Head title>` (aba do navegador) fica na página, não aqui.
- O flash é deduplicado pelo texto: a mesma mensagem lida no carregamento e no evento `success` do Inertia vira um toast só.
- O e2e acha o toast pela região "Avisos" (`e2e/pages/admin.page.ts`).
