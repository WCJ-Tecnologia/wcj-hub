# WBoardOrderToggleMolecule

Molécula · `molecules/w-board-order-toggle-molecule/w-board-order-toggle-molecule.tsx` · story `Molecules/Board Order Toggle`

## O que faz

O seletor _Ordem: Prioridade | Manual_ do quadro (F3 §03, P13): o rótulo "Ordem" em Space Mono e dois botões segmentados (`role="group"` rotulado por "Ordem", cada botão com `aria-pressed`). O marcado fica `secondary`; o outro, `ghost`. Clicar no já marcado não chama nada.

## Props

| Prop        | Tipo                          | Padrão | Obrigatória | Para quê                   |
| ----------- | ----------------------------- | ------ | ----------- | -------------------------- |
| `value`     | `BoardOrder`                  |        | sim         | `'priority'` ou `'manual'` |
| `onChange`  | `(order: BoardOrder) => void` |        | sim         |                            |
| `className` | `string`                      |        | não         |                            |

## Exemplo

```tsx
<WBoardOrderToggleMolecule value={order} onChange={changeOrder} />
```

## Cuidados

- **Não guarda nada.** A escolha é de cada pessoa, lembrada no navegador (P13): quem grava é a página, no `localStorage` com a chave `hub.quadro.ordem`, dentro de `try/catch` (aba anônima ou bloqueio não quebram a tela).
- Soltar um card com a ordem em _Prioridade_ troca o seletor para _Manual_ (o `WKanbanBoardOrganism` chama `onOrderChange('manual')`): o card fica onde caiu, como no Trello (F3 §04, 14/10).
