# Componentes

Decisão em `docs/adr/003-componentes-atomicos-e-storybook.md`. Catálogo visual: `npm run storybook` (http://localhost:6006).

## Onde cada coisa mora

| Pasta        | O que vai                                                                        | Exemplo                                           |
| ------------ | -------------------------------------------------------------------------------- | ------------------------------------------------- |
| `shadcn/`    | Primitivos do shadcn/Radix, **planos** (sem subpasta), um arquivo por componente | `shadcn/button.tsx`, `shadcn/select.tsx`          |
| `atoms/`     | Peça pequena do Hub, sem estado de tela, que não se divide                       | `w-status-pill-atom`                              |
| `molecules/` | Junção de átomos/primitivos com um propósito                                     | `w-form-field-molecule`, `w-pagination-molecule`  |
| `organisms/` | Bloco de interface completo, com regra de exibição própria                       | `w-data-table-organism`, `w-app-sidebar-organism` |
| `templates/` | Estrutura da página (shell), sem dado de negócio                                 | `w-app-layout-template`                           |
| `pages/`     | A tela inteira, com as props do controller                                       | `w-users-page`                                    |

As telas do Inertia continuam em `resources/js/pages/<nome>.tsx`, porque é o nome usado no `Inertia::render`. Esse arquivo tem **uma linha** e só reexporta a tela:

```ts
export { WUsersPage as default } from '@/components/pages/w-users-page/w-users-page';
```

## Nome e arquivos

- Pasta `w-<nome>-<nível>/`, em kebab-case: `w-search-input-molecule/`.
- Dentro dela, arquivos com o mesmo nome da pasta:
    - `w-search-input-molecule.tsx`: o componente (obrigatório);
    - `w-search-input-molecule.md`: o manual, com o que faz, props, exemplo e cuidados (obrigatório);
    - `w-search-input-molecule.stories.tsx`: a story, quando há o que ver (ver abaixo).
- Export **nomeado** em PascalCase (`WSearchInputMolecule`), props em `WSearchInputMoleculeProps`.
- A raiz do componente leva `data-slot="w-search-input-molecule"`.
- Import pelo caminho completo, sem barrel (`index.ts`):
    ```ts
    import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
    import { Button } from '@/components/shadcn/button';
    ```

Na dúvida entre dois níveis, escolha o mais baixo que ainda faça sentido sozinho. Componente de um domínio só (ex.: o formulário de usuário) é organismo do Hub, com o domínio no nome.

## Primitivo novo do shadcn

```bash
npm run shadcn -- add accordion
```

O `components.json` (`aliases.ui = @/components/shadcn`) manda o arquivo para `shadcn/`. O script `scripts/shadcn.mjs`:

- troca o `import { cn } from "cn"` do registro novo pelo `@/lib/utils`;
- tira o pacote `cn`, se o CLI o instalou;
- formata com o prettier.

Ao rodar:

- **Responda não** quando o CLI perguntar se pode sobrescrever um arquivo que já existe. `dialog`, `sheet`, `badge`, `tooltip`, `select` e outros têm ajustes do Hub.
- **Desfaça** qualquer mudança em `resources/css/app.css`: ele é gerado por `npm run theme` a partir de `design/tokens.json`.

## Quando criar story

Crie story quando o componente tem visual ou estados que alguém precisa ver ou testar: variantes, erro, vazio, carregando, celular.

Não crie para peça só estrutural ou simples demais. Hoje ficam sem story:

- do shadcn: `label`, `separator`, `skeleton`, `textarea`, `avatar`, `sheet`, `sidebar`, `sonner`, `calendar` e `popover` (os quatro últimos aparecem dentro do menu lateral, do layout e do `w-date-picker-molecule`);
- do Hub: `w-page-container-atom` e `w-auth-back-link-atom`.

Regras da story:

- `title` com o nível: `Atoms/…`, `Molecules/…`, `Organisms/…`, `Templates/…`, `Pages/…`, `Shadcn/…`.
- `tags: ['autodocs']` e `argTypes` para toda prop que faz sentido mexer.
- Story de componente composto (Select, Dialog, Table) usa um componente `…Demo` com props simples, para os controles funcionarem.

## Storybook e Inertia

Não há Laravel atrás do Storybook. Por isso o `@inertiajs/react` é trocado por `.storybook/inertia-mock.tsx`:

- `usePage()` devolve as props de `parameters.inertia` mescladas sobre um admin de exemplo:
    ```ts
    parameters: { inertia: { url: '/admin/users', props: { auth: { user: null } } } }
    ```
- `Link`, `router` e o `submit` do `useForm` não saem do navegador: aparecem no painel **Actions**.
- Dados de exemplo das telas: `.storybook/fixtures.ts`.
- O tema (claro/escuro) troca na barra do Storybook. O painel **Accessibility** usa a mesma régua do e2e (WCAG 2.0/2.1 A e AA).
