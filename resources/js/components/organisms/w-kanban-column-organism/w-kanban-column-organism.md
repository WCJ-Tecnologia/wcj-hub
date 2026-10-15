# WKanbanColumnOrganism

Organismo · `organisms/w-kanban-column-organism/w-kanban-column-organism.tsx` · story `Organisms/Kanban Column`

## O que faz

Uma coluna do quadro (F3 §03), na largura fixa de 18 rem (`w-72`) e na altura do quadro, com rolagem vertical própria:

| Parte          | O que mostra                                                                                                                  | Quando                                                               |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| Cabeçalho (h2) | Nome da etapa e a contagem (`Badge secondary`, número em Space Mono, "3 cards" para leitor de tela)                           | sempre                                                               |
| Cabeçalho      | Ícone (i) com a dica "Mostra a sprint aberta e a última encerrada; os mais antigos pelo filtro Mostrar prontos antigos" (P13) | coluna de papel `done`                                               |
| Topo           | `WQuickCreateMolecule` (P17, R4)                                                                                              | `role === 'queue'`, `abilities.create_card` e `onCreate`             |
| Topo           | Botão _Novo card de suporte_ (P52)                                                                                            | `role === 'support'`, `abilities.create_support` e `onCreateSupport` |
| Corpo          | Os cards (`WTaskCardOrganism`) numa lista, cada um arrastável                                                                 | sempre                                                               |
| Corpo          | Área tracejada para soltar; só a contagem 0 fala da coluna vazia (F3 §03)                                                     | sem card                                                             |

Arrastar e soltar (dnd-kit): o corpo inteiro é área de soltar (`useDroppable`, id `stage-<id>`, `data = { type: 'stage', stage_id }`), para receber card mesmo vazia. Os cards ficam num `SortableContext` (id `card-<id>`, `data = { type: 'card', card, stage_id }`). Em qualquer ordem os vizinhos abrem espaço (`verticalListSortingStrategy`): o card cai onde for solto (F3 §04, 14/10).

## Props

| Prop                                                                          | Tipo                      | Padrão  | Obrigatória | Para quê                                                                  |
| ----------------------------------------------------------------------------- | ------------------------- | ------- | ----------- | ------------------------------------------------------------------------- |
| `stage`                                                                       | `BoardStage`              |         | sim         | A etapa                                                                   |
| `cards`                                                                       | `BoardCard[]`             |         | sim         | Os cards desta coluna, **já ordenados** (`sortCards`)                     |
| `abilities`                                                                   | `BoardAbilities`          |         | sim         | `create_card`, `create_support`; `move` liga o arrastar e o _Mover para…_ |
| `stages`, `areas`, `members`                                                  |                           |         | sim         | Repassados ao card (menu ⋯ e _+_ de participante)                         |
| `highlighted`                                                                 | `boolean`                 | `false` | não         | O card arrastado está sobre esta coluna: borda e fundo azuis              |
| `onCreate`, `creating`, `createError`                                         |                           |         | não         | Criar rápido                                                              |
| `onCreateSupport`                                                             | `() => void`              |         | não         | Abre o diálogo de suporte                                                 |
| `onMove`, `onChangePriority`, `onChangeArea`, `onToggleParticipant`, `onOpen` | os do `WTaskCardOrganism` |         | não         | Repassados a cada card                                                    |

Também exporta `cardDndId`, `stageDndId` e o tipo `BoardDndData`, que o quadro lê no `onDragEnd`.

## Exemplo

Dentro do `DndContext` do `WKanbanBoardOrganism` (fora dele, envolva num `DndContext`, como na story):

```tsx
<WKanbanColumnOrganism
    stage={stage}
    cards={sortCards(cardsOfStage, order)}
    abilities={abilities}
    stages={stages}
    areas={options.areas}
    members={options.members}
    onCreate={createCard}
    onMove={menuMove}
/>
```

## Cuidados

- **O item da lista (`li`) é o que arrasta.** Os `attributes` do `useSortable` vão para ele **sem o `role="button"`**: o card tem link e botões dentro, e botão com controle dentro é `nested-interactive` no axe. Ficam `tabIndex`, `aria-describedby` (as instruções do dnd-kit) e `aria-roledescription="card arrastável"`.
- **Teclado.** O item é focável e é o único que ativa o arrastar por teclado (`setActivatorNodeRef` no próprio `li`): Espaço pega, setas levam a outra coluna, Espaço solta, Esc cancela. Enter no título abre a ficha, não pega o card. O caminho por teclado que recomendamos é o _Mover para…_ do menu ⋯ do card, que passa pelas mesmas regras.
- O lugar de onde o card saiu fica esmaecido; quem acompanha o ponteiro é o `DragOverlay` do quadro.
- No toque (celular), o card não arrasta (`touch-action: manipulation`, a rolagem vence): use o _Mover para…_.
- A coluna é o "poço" dos cards: `bg-muted` no claro e `dark:bg-background/60` no escuro, sempre mais escura que o card, para a sombra (claro) ou o contraste (escuro) dar relevo ao `WTaskCardOrganism`. Clarear a coluna deixa os cards chapados de novo.
- **O "+" do cabeçalho (14/10):** o criar rápido do Backlog e o _Novo card de suporte_ do Support saíram de baixo do título e viraram um botão "+" ao lado dele, para os cards de todas as colunas começarem na mesma altura. No Backlog o "+" abre o campo (com foco; Esc ou sair vazio fecha; segue aberto depois de criar, para o próximo título); no Support, abre o diálogo de suporte direto.
