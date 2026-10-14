# WBoardFiltersOrganism

Organismo · `organisms/w-board-filters-organism/w-board-filters-organism.tsx` · sem story própria (aparece em `Pages/Project Board`)

## O que faz

A barra acima do quadro (F3 §05), controlada (`filters` + `onChange`):

- **linha 1:** busca por chave (`SGM-17`) ou trecho do título, com o _Limpar busca_; _Só os meus_ (Switch: sou dev, QA ou participante, P58); à direita, o `WBoardOrderToggleMolecule`;
- **linha 2:** Selects Tipo, Área, Prioridade (com o chip do `WPriorityMarkAtom`, Urgente primeiro), Dev e QA, nessa ordem (decisão de 14/10); _Marcas_ num Popover com checkboxes (Atrasado, Em pausa, Com retrabalho, Suporte, Reestimativa proposta) e o número de marcas escolhidas no botão; _Mostrar prontos antigos_ e _Arquivados_ (Switches); _Limpar filtros_ quando algum filtro que esconde card está ligado.

Também exporta `clearBoardFilters(filters)` (zera tudo menos _Mostrar prontos antigos_ e _Arquivados_).

## Props

| Prop            | Tipo                                                  | Obrigatória | Para quê                                 |
| --------------- | ----------------------------------------------------- | ----------- | ---------------------------------------- |
| `filters`       | `BoardFilters`                                        | sim         | Valor atual                              |
| `onChange`      | `(filters: BoardFilters) => void`                     | sim         | O filtro inteiro, já com a mudança       |
| `options`       | `Pick<BoardOptions, 'members' \| 'types' \| 'areas'>` | sim         | Opções de Dev/QA (alocados), Tipo e Área |
| `order`         | `BoardOrder`                                          | sim         | O seletor de ordem                       |
| `onOrderChange` | `(order: BoardOrder) => void`                         | sim         |                                          |
| `className`     | `string`                                              | não         |                                          |

## Exemplo

```tsx
<WBoardFiltersOrganism filters={current} onChange={changeFilters} options={options} order={order} onOrderChange={changeOrder} />
```

## Cuidados

- **A filtragem é do servidor.** A barra só devolve o filtro; a página guarda, filtra no navegador para a resposta ser instantânea (`applyFilters` em `lib/quadro.ts`, "só para o Storybook e para o filtro instantâneo; o servidor é a fonte") e manda `GET /projetos/{id}/quadro?…` (a busca espera 300 ms).
- **Por que não o `WSearchInputMolecule`.** Ele navega sozinho (`router.get` com debounce) e não avisa a tela; aqui a busca precisa entrar no mesmo `onChange` dos outros filtros para o filtro instantâneo funcionar. O campo repete o visual dele (ícone, _Limpar busca_, `role="search"`).
- Marcas: o card aparece se tiver **qualquer uma** das marcas escolhidas. "Em pausa" é estar numa etapa de papel `paused` (P15).
- Fora da fatia do kit (F3 §05 lista, o servidor do B3 decide quando entram): Sprint, Pacote e Card pai.
