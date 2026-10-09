# WContactTableOrganism

Organismo · `organisms/w-contact-table-organism/w-contact-table-organism.tsx` · sem story (aparece em `Pages/Contatos` e `Pages/Ficha do cliente`)

## O que faz

Contatos com as colunas do F1 §03 (Nome, E-mail, Telefone, Cliente opcional, Situação, ações), na lista geral (`showClient`) e na ficha do cliente. Exporta `ContactRow` e `contactStatus()` (`anonymized` vence `active`).

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `rows` | `ContactRow[]` | as linhas |
| `actions` | `(row, labeled?) => ReactNode` | ações da linha ou do card (`useContactActions().rowActions`) |
| `emptyState` | `ReactNode` | estado vazio |
| `caption` | `string` | "Lista de contatos" ou "Contatos de WCJ Tecnologia" |
| `showClient` | `boolean` | a coluna Cliente, só na lista geral |

## Exemplo

Ver `pages/w-contacts-page/w-contacts-page.tsx` e `hooks/use-contact-actions.tsx`.

## Cuidados

Telefone é texto como digitado (D3 do B1): sem máscara do Brasil.
