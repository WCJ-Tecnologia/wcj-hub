# WSearchInputMolecule

Molécula · `molecules/w-search-input-molecule/w-search-input-molecule.tsx` · story `Molecules/Search Input`

## O que faz

Campo de busca de listagem. Enquanto a pessoa digita, ele espera 300 ms e navega com o Inertia (`router.get(url, { ...params, search })`), com `preserveState` e `replace`, sem poluir o histórico. Enter busca na hora, e o botão "Limpar busca" zera.

**Use** no topo de toda listagem com busca no servidor.
**Não use** para filtrar uma lista que já está inteira no cliente.

## Props

| Prop          | Tipo                                                    | Padrão      | Obrigatória | Para quê                                                     |
| ------------- | ------------------------------------------------------- | ----------- | ----------- | ------------------------------------------------------------ |
| `url`         | `string`                                                |             | sim         | Rota da listagem (ex.: `/admin/users`)                       |
| `value`       | `string \| null \| undefined`                           |             | sim         | Valor que veio do servidor (`filters.search`)                |
| `params`      | `Record<string, string \| number \| null \| undefined>` | `{}`        | não         | Os **outros** filtros da tela, para não se perderem na busca |
| `placeholder` | `string`                                                | `'Buscar…'` | não         |                                                              |
| `label`       | `string`                                                | `'Buscar'`  | não         | `aria-label` do campo                                        |
| `className`   | `string`                                                |             | não         |                                                              |

## Exemplo

```tsx
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';

<WSearchInputMolecule
    url="/admin/users"
    value={filters.search}
    params={{ role_id: filters.role_id }}
    placeholder="Buscar por nome ou e-mail…"
    label="Buscar usuários"
/>;
```

## Cuidados

- O controller lê `search` da query string e devolve em `filters.search`.
- Ao combinar com outro filtro (ex.: um Select de papel), o outro filtro também precisa mandar `search` adiante.
