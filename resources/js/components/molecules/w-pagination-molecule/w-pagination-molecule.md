# WPaginationMolecule

Molécula · `molecules/w-pagination-molecule/w-pagination-molecule.tsx` · story `Molecules/Pagination`

## O que faz

Rodapé de listagem paginada: "Mostrando X–Y de Z" e os links de página. É feito sobre o `Pagination` do shadcn, com o `Link` do Inertia (`preserveScroll` + `preserveState`).

Recebe o paginator do Laravel do jeito que o controller devolve (`->paginate()`) e reconhece sozinho os links "Anterior", "Próxima" e as reticências. Com uma página só, não renderiza nada.

## Props

| Prop   | Tipo                       | Obrigatória | Para quê                                                                    |
| ------ | -------------------------- | ----------- | --------------------------------------------------------------------------- |
| `meta` | `Paginated<T>` (`@/types`) | sim         | O objeto paginado inteiro (usa `links`, `from`, `to`, `total`, `last_page`) |

## Exemplo

```tsx
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';

<WDataTableOrganism rows={users.data} … />
<WPaginationMolecule meta={users} />
```

## Cuidados

- O controller precisa manter a query string nos links (`->withQueryString()`), senão os filtros se perdem ao trocar de página.
- Página atual: `aria-current="page"`. Link desabilitado: `aria-disabled`.
