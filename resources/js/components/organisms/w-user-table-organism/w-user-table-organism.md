# WUserTableOrganism

Organismo · `organisms/w-user-table-organism/w-user-table-organism.tsx` · sem story (aparece em `Pages/Usuários`)

## O que faz

A lista de usuários com as colunas do F1 §03 (Nome, E-mail, Papel, Carga diária, Último acesso, Situação, ações), sobre o `WDataTableOrganism`: tabela no desktop, cards no celular. Desativado fica em cinza; "Trocar senha no próximo acesso" aparece como badge. Exporta o tipo `UserRow`, a linha como o `UserPresenter` manda (`can` decidido pela `UserPolicy`).

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `rows` | `UserRow[]` | `users.data` |
| `actions` | `(row, labeled?) => ReactNode` | o `WRowActionsMolecule` da linha (desktop) ou do card (`labeled`) |
| `emptyState` | `ReactNode` | o `WEmptyStateMolecule` da tela |

## Exemplo

Ver `pages/w-users-page/w-users-page.tsx`.

## Cuidados

A tela só esconde botão pelo `row.can.*`; a regra vale na Policy.
