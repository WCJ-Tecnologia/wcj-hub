# ADR-003: Componentes em Atomic Design, shadcn em pasta própria e Storybook

**Data:** 2026-10-07
**Estado:** proposta
**Decidido por:** pendente (Lucas Scholz)

## Contexto

O front (`resources/js`) veio do template com três tipos de componente misturados:

- primitivos shadcn em `components/ui/`;
- componentes de domínio soltos em `components/`;
- peças montadas à mão onde o shadcn tem equivalente:
  - a gaveta do menu no celular (sem focus trap, sem ESC, sem retorno de foco, contra o `.ai/stacks/ui.md` §6);
  - o toast;
  - a tabela;
  - a paginação;
  - o menu lateral.

O Select era nativo por regra do guia (`.ai/stacks/ui.md` L66). A lista aberta fica com o visual do sistema operacional, fora do tema. O ganho do nativo (seletor do sistema no celular) é pequeno num sistema interno usado quase todo no desktop.

Não havia catálogo visual dos componentes nem documentação de uso por componente. Quem usa IA para montar tela precisa ler o código para descobrir props e intenção.

O projeto kds da WCJ já usa a organização atômica com o shadcn em pasta própria e Storybook (`apps/kds-web`).

## Decisão

1. Os primitivos shadcn ficam planos em `resources/js/components/shadcn/`. O `components.json` aponta `aliases.ui` para `@/components/shadcn`, então `npx shadcn add` instala ali.
2. Os componentes do Hub ficam em Atomic Design, em `components/{atoms,molecules,organisms,templates,pages}/`.
   - Uma pasta por componente, `w-<nome>-<nível>/`, com `w-<nome>-<nível>.tsx`, `.md` (manual de uso) e `.stories.tsx` quando houver o que ver.
   - Export nomeado em PascalCase (`WFormFieldMolecule`) e `data-slot` com o nome da pasta.
3. `resources/js/layouts/` deixa de existir: os layouts são os templates.
4. As telas vivem em `components/pages/`. O arquivo em `resources/js/pages/` (o nome que o `Inertia::render` usa) só reexporta a tela.
5. O que era feito à mão passa a ser shadcn:
   - Sidebar (com a gaveta do celular em Sheet);
   - Sonner (toast);
   - Table;
   - Pagination;
   - **Select (Radix), no lugar do nativo**;
   - **Date Picker (Calendar + Popover), no lugar do `<input type="date">`**, que mostrava o calendário do navegador e o formato da língua dele (`mm/dd/yyyy`).
6. Storybook 10 (`@storybook/react-vite`) cataloga os componentes. O CI roda `npm run build-storybook`.

## Alternativas descartadas

- **Manter `components/ui/` e só documentar:** não resolve as peças sem trava de foco nem dá o catálogo.
- **Select nativo:** a lista aberta fica fora do tema. O Select do Radix tem teclado, foco e ARIA, e o uso no celular é residual.
- **Mover as telas para fora de `resources/js/pages/` e mudar o resolver:** o nome no `Inertia::render` deixaria de bater com o caminho do arquivo. A reexportação de uma linha mantém o controller como está.
- **Barrel (`index.ts`) por pasta:** o kds importa pelo caminho completo. Barrel esconde o nível do componente no import.

## Consequências

- Componente novo do Hub nasce na pasta do nível, com `.md`. Primitivo novo entra por `npx shadcn add`, que já cai em `components/shadcn/`.
- O CLI do shadcn pede para sobrescrever primitivos customizados (`dialog`, `sheet`, `badge`, `tooltip`): a resposta é sempre **não**.
- O CLI também tenta editar `resources/css/app.css`. Essa edição se descarta: o arquivo é gerado por `npm run theme`.
- `.ai/stacks/ui.md` e `.ai/stacks/laravel-inertia.md` são sincronizados do padrão e continuam citando `components/ui/`, `layouts/` e o Select nativo. Neste projeto vale este ADR, registrado no `CLAUDE.md`. Se a decisão for boa, ela sobe para o padrão (template `ai-systems`).
- O seletor de papel e os filtros usam um valor sentinela para "todos", porque o Radix não aceita item com valor vazio.
- O e2e do toast passa a ler o markup do Sonner.
- Storybook entra nas dependências de desenvolvimento e no CI, ganhando um passo a mais no build.

## Como verificar

- `grep -r "components/ui/\|@/layouts/" resources/js e2e` vazio.
- `npx shadcn@latest add <componente>` cria o arquivo em `resources/js/components/shadcn/`.
- `npm run typecheck && npm run lint && npm run build && npm run build-storybook` passam.
- `npx playwright test` passa, inclusive o axe do smoke em claro, escuro e com modal.
