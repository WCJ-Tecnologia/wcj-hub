# WArchivedCardsTableOrganism

Organismo · `organisms/w-archived-cards-table-organism/w-archived-cards-table-organism.tsx` · story `Organisms/Archived Cards Table`

## O que faz

A lista de arquivados do quadro (F3 §03; P26), quando o filtro _Arquivados_ está ligado: Chave, Título, Tipo, Arquivado em e Motivo, do mais recente para o mais antigo. A chave e o título abrem a ficha, onde se desarquiva. Tabela no desktop e card no celular, pelo `WDataTableOrganism`.

## Props

| Prop         | Tipo             | Para quê                                                                            |
| ------------ | ---------------- | ----------------------------------------------------------------------------------- |
| `rows`       | `ArchivedCard[]` | a prop `archived` do quadro (`TaskPresenter::archivedRow`)                          |
| `emptyState` | `ReactNode`      | "Nenhum card arquivado", ou "Nenhum card com esses filtros" com outro filtro ligado |

## Exemplo

Ver `pages/w-project-board-page/w-project-board-page.tsx`.

## Cuidados

A ordem é a do servidor; a tela não reordena. Sem tipo, a coluna mostra "—" (o card rápido nasce sem tipo, P17).
