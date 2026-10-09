# WDataTableOrganism

Organismo · `organisms/w-data-table-organism/w-data-table-organism.tsx` · story `Organisms/Data Table`

## O que faz

Toda listagem do sistema passa por aqui. A tabela é **declarativa**: você descreve as colunas e ele monta:

- **desktop (≥ 768 px, `md`):** `Table` do shadcn, com `<caption>` (lido por leitor de tela, oculto na tela) e `scope="col"`;
- **celular:** lista de cards. Se você passar `mobileCard`, cada linha usa o seu card. Senão, cada coluna vira um par rótulo/valor;
- **vazio:** quando `rows` está vazio e há `emptyState`, mostra só o estado vazio.

Ordenação, filtro e paginação ficam fora: os filtros acima da tabela e o `WPaginationMolecule` abaixo, com os dados vindo paginados do servidor.

## Props

| Prop         | Tipo                             | Obrigatória | Para quê                                                 |
| ------------ | -------------------------------- | ----------- | -------------------------------------------------------- |
| `columns`    | `WColumn<Row>[]`                 | sim         | As colunas, na ordem                                     |
| `rows`       | `Row[]`                          | sim         | Normalmente `paginator.data`                             |
| `rowKey`     | `(row: Row) => string \| number` | sim         | Chave única da linha (`row.id`)                          |
| `caption`    | `string`                         | sim         | Nome da tabela para leitor de tela ("Lista de usuários") |
| `mobileCard` | `(row: Row) => ReactNode`        | não         | Card próprio por linha no celular                        |
| `emptyState` | `ReactNode`                      | não         | Normalmente um `WEmptyStateMolecule`                     |
| `className`  | `string`                         | não         | Vai no `Card` externo                                    |

### `WColumn<Row>`

| Campo          | Tipo                      | Obrigatório | Para quê                                                              |
| -------------- | ------------------------- | ----------- | --------------------------------------------------------------------- |
| `key`          | `string`                  | sim         | Chave da coluna; sem `cell`, mostra `row[key]`                        |
| `header`       | `ReactNode`               | sim         | Cabeçalho. Coluna de ações: `<span className="sr-only">Ações</span>`  |
| `cell`         | `(row: Row) => ReactNode` | não         | Conteúdo customizado (Badge, StatusPill, ações)                       |
| `className`    | `string`                  | não         | Classes do `<th>` e do `<td>` (ex.: `'text-right'`, `'tabular-nums'`) |
| `hideOnMobile` | `boolean`                 | não         | Fora do card padrão do celular (ignorado com `mobileCard`)            |

## Exemplo

```tsx
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';

const columns: WColumn<UserRow>[] = [
    { key: 'name', header: 'Nome', className: 'font-medium' },
    { key: 'role_label', header: 'Papel', cell: (row) => <Badge variant="info">{row.role_label}</Badge> },
    { key: 'active', header: 'Status', cell: (row) => <WStatusPillAtom kind="user" status={row.active} /> },
    { key: 'actions', header: <span className="sr-only">Ações</span>, className: 'text-right', cell: (row) => rowActions(row) },
];

<WDataTableOrganism
    caption="Lista de usuários"
    columns={columns}
    rows={users.data}
    rowKey={(row) => row.id}
    emptyState={<WEmptyStateMolecule title="Nenhum usuário encontrado" description="…" />}
    mobileCard={(row) => (/* nome, status, badges e <WRowActionsMolecule labeled /> */)}
/>
<WPaginationMolecule meta={users} />
```

## Cuidados

- A linha inteira não é clicável. Ação de linha é botão (`WRowActionsMolecule`) ou link dentro de uma célula.
- O card de celular precisa repetir o essencial da linha **e** as ações (com `labeled`).
- Listagem pesada: os dados vêm paginados do servidor (`.ai/core/performance.md`). A tabela não pagina no cliente.
