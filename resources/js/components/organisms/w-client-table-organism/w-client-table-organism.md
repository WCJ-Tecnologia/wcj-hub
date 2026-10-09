# WClientTableOrganism

Organismo · `organisms/w-client-table-organism/w-client-table-organism.tsx` · sem story (aparece em `Pages/Clientes`)

## O que faz

A lista de clientes com as colunas do F1 §03 (Nome com link para a ficha, País, CNPJ / identificador, Situação, ações). Exporta `ClientRow` e `formatDocument()` (CNPJ alfanumérico com máscara; identificador fiscal como foi digitado).

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `rows` | `ClientRow[]` | `clients.data` |
| `actions` | `(row, labeled?) => ReactNode` | ações da linha ou do card |
| `emptyState` | `ReactNode` | estado vazio da tela |

## Exemplo

Ver `pages/w-clients-page/w-clients-page.tsx`.

## Cuidados

`open_projects` só vem para quem pode desativar: alimenta o texto da confirmação (F1 §04).
