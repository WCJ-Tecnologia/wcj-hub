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
- do Hub: `w-page-container-atom`, `w-auth-back-link-atom`, `w-list-body-molecule`, `w-show-inactive-toggle-molecule` e as tabelas, filtros e modais do Núcleo (`w-{user,client,contact,audit-log}-table-organism`, `w-audit-log-filters-organism`, `w-audit-log-detail-dialog-organism`, `w-{client,contact}-form-dialog-organism`, `w-anonymize-contact-dialog-organism`, `w-project-filters-organism`, `w-project-form-dialog-organism`), que aparecem dentro das stories das telas (`Pages/…`).

Regras da story:

- `title` com o nível: `Atoms/…`, `Molecules/…`, `Organisms/…`, `Templates/…`, `Pages/…`, `Shadcn/…`.
- `tags: ['autodocs']` e `argTypes` para toda prop que faz sentido mexer.
- Story de componente composto (Select, Dialog, Table) usa um componente `…Demo` com props simples, para os controles funcionarem.

## Kit do quadro (F3), sem backend

Os componentes do quadro kanban entraram antes do B3, como kit: o Cauã liga o backend neles. O contrato está em três arquivos, e nenhum componente inventa forma própria:

| Arquivo | O que é |
| --- | --- |
| `resources/js/types/quadro.ts` | O que o `TaskPresenter` do B3 manda (`BoardCard`, `BoardStage`, `BoardProject`, `BoardAbilities`, `BoardOptions`, `BoardFilters`) e o que a tela devolve (`MoveIntent`, `MovePayload`) |
| `resources/js/lib/quadro.ts` | Ordenar por prioridade ou manual (P13), `classifyMove` (qual diálogo abrir e qual recusa mostrar: P15, P39, P45, R5, P38), `hours()`, `applyFilters()` (só para o filtro instantâneo; o servidor é a fonte) |
| `.storybook/fixtures-quadro.ts` | Projeto, 8 etapas, cards com todas as zonas e marcas, opções e abilities de gestor e de dev |

Como se encaixam: `pages/w-project-board-page` monta `organisms/w-board-filters-organism` + `organisms/w-kanban-board-organism` (dnd-kit) e liga o `hooks/use-board-move.tsx`, que recebe o `MoveIntent` do arrastar (ou do *Mover para…* do card) e abre, na ordem, os diálogos que o movimento pede (`w-new-sprint-dialog`, `w-pause-reason-dialog`, `w-rework-dialog`, o aviso do Done pelo `WConfirmDialogMolecule`), devolvendo um `MovePayload` para o `router.post`. A coluna (`w-kanban-column-organism`) traz o criar rápido no Backlog e o *Novo card de suporte* no Support; o card (`w-task-card-organism`) é só apresentação e callbacks. As rotas que a página chama são proposta do kit, marcadas no topo do arquivo; o B3 confirma ou ajusta.

Regra do kit: o quadro e o card nunca decidem regra nem recusam movimento. `classifyMove` só escolhe o diálogo; quem recusa é o servidor, e a tela mostra o erro que vier.

## Storybook e Inertia

Não há Laravel atrás do Storybook. Por isso o `@inertiajs/react` é trocado por `.storybook/inertia-mock.tsx`:

- `usePage()` devolve as props de `parameters.inertia` mescladas sobre um admin de exemplo:
    ```ts
    parameters: { inertia: { url: '/admin/users', props: { auth: { user: null } } } }
    ```
- `Link`, `router` e o `submit` do `useForm` não saem do navegador: aparecem no painel **Actions**.
- Dados de exemplo das telas: `.storybook/fixtures.ts`.
- O tema (claro/escuro) troca na barra do Storybook. O painel **Accessibility** usa a mesma régua do e2e (WCAG 2.0/2.1 A e AA).
