# WListBodyMolecule

Molécula · `molecules/w-list-body-molecule/w-list-body-molecule.tsx` · sem story (aparece dentro das stories das telas)

## O que faz

O corpo de uma listagem com os estados de **erro** e **carregando** (`.ai/stacks/ui.md` §2), lidos do `useListVisit()` da tela. No erro (rede caída, resposta que não é Inertia), mostra o aviso "Não foi possível carregar…" com **Tentar de novo** (`router.reload()`). Enquanto a visita GET roda, a lista esmaece, fica `aria-busy` e um `role="status"` oculto anuncia o texto de carregando.

Veio do B1 (`components/list-body.tsx`, CR-1-09 e CR-1-22 do code review); lista nova não reescreve erro e carregando.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `list` | `ReturnType<typeof useListVisit>` | o hook da tela: `loading`, `failed`, `retry` |
| `failedTitle` | `string` | "Não foi possível carregar os usuários" |
| `loadingText` | `string` | "Carregando usuários…" (só leitor de tela) |
| `children` | `ReactNode` | a tabela (`W*TableOrganism`) |

## Exemplo

```tsx
const list = useListVisit();
<WListBodyMolecule list={list} failedTitle="Não foi possível carregar os usuários" loadingText="Carregando usuários…">
    <WUserTableOrganism rows={users.data} actions={rowActions} emptyState={…} />
</WListBodyMolecule>
```

## Cuidados

Só as visitas GET (busca, filtro, página) contam: a escrita já tem o próprio botão carregando e o toast.
