# WAppSidebarOrganism

Organismo · `organisms/w-app-sidebar-organism/w-app-sidebar-organism.tsx` · story `Organisms/App Sidebar`

## O que faz

O menu lateral do backoffice, montado sobre o **Sidebar do shadcn** (`shadcn/sidebar.tsx`):

- **Desktop (≥ 1024 px, `lg`):** fixo à esquerda, com 17rem. Recolhe para **só os ícones** (`collapsible="icon"`, 3rem) pelo botão da topbar ou por **Ctrl/⌘ + B**. A escolha fica no cookie `sidebar_state` e é respeitada na próxima tela.
- **Recolhido:**
    - cada item mostra o nome num tooltip à direita;
    - os títulos dos grupos somem;
    - o logo troca do horizontal para o símbolo da marca (`WBrandLogoAtom variant="symbol"`), pela regra de tamanho;
    - o rodapé vira as iniciais do usuário.
- **Celular e tablet (< 1024 px):** vira uma gaveta (`Sheet`, Radix), com trava de foco, ESC e retorno do foco ao botão que abriu. Fecha sozinha ao navegar. Fecha pelo botão "Fechar menu" (X no cabeçalho, só na gaveta), pelo ESC, tocando fora ou ao navegar.

A navegação é **declarada em dado** (`NAV`, no mesmo arquivo). Cada item pode ter `visible(abilities)`, e um grupo sem item visível some.

Os títulos de grupo são rótulos em Space Mono (Brand Board). O logo aberto é o horizontal na versão `fundo-escuro`.

## Props

| Prop  | Tipo          | Padrão | Obrigatória | Para quê                                                |
| ----- | ------------- | ------ | ----------- | ------------------------------------------------------- |
| `nav` | `WNavGroup[]` | `NAV`  | não         | Trocar a navegação (story, teste). No app, use o padrão |

### `WNavGroup` / `WNavItem`

```ts
interface WNavGroup {
    title: string;
    items: WNavItem[];
}
interface WNavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Sem `visible`, todo usuário logado vê. */
    visible?: (abilities: Abilities) => boolean;
}
```

## Tela nova no menu

Acrescente um item em `NAV`:

```ts
{ label: 'Projetos', href: '/admin/projects', icon: FolderKanban, visible: (a) => a.view_projects },
```

Ability nova (`a.view_projects`) segue a regra do `CLAUDE.md`: `User`, `HandleInertiaRequests`, `types/index.d.ts` e Policy no mesmo commit. Esconder no menu não protege a rota.

## Depende de

- `SidebarProvider`, que precisa estar acima dele. O `WAppLayoutTemplate` já monta; na story, um decorator monta.
- `WBrandLogoAtom`, `WProductWordmarkAtom`.
- `usePage()`: `url` para marcar o item ativo e `auth.user` para nome, papel e abilities. Sem usuário, não renderiza.

## Acessibilidade

- Os itens ficam dentro de `<nav aria-label="Menu principal">`. O e2e (`e2e/pages/admin.page.ts`) usa a primeira `navigation` da página como o menu: mantenha o `<nav>` antes de qualquer outro na ordem do DOM.
- O item ativo tem `aria-current="page"`.
