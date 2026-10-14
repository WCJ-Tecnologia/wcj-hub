# WKanbanBoardOrganism

Organismo · `organisms/w-kanban-board-organism/w-kanban-board-organism.tsx` · story `Organisms/Kanban Board`

## O que faz

O quadro kanban (F3 §01, §03, §04): uma `WKanbanColumnOrganism` por etapa, lado a lado, numa região com rolagem horizontal (`role="region"`, "Colunas do quadro", focável; no celular encaixa uma coluna por vez com `snap-x`). Agrupa os cards por `stage_id` e ordena cada coluna com `sortCards(…, order)`.

Arrastar e soltar com o dnd-kit:

- `PointerSensor` com `activationConstraint: { distance: 5 }`: clique no título, no menu ⋯ e no _+_ continuam cliques, e o card solto não abre a ficha;
- `KeyboardSensor` com `sortableKeyboardCoordinates`;
- colisão: o card sob o ponteiro, senão a coluna sob o ponteiro (inclusive vazia), senão o mais perto (teclado);
- `DragOverlay` com o card `dragging`, sem animação de volta (o card só muda de coluna quando a página confirma);
- a coluna sob o card arrastado fica destacada;
- anúncios em português no `DndContext` ("Pegou SGM-17. Use as setas para mover entre colunas e Espaço para soltar.", "SGM-17 sobre Doing.", "SGM-17 solto em Doing.", "Movimento cancelado. SGM-17 ficou onde estava.") e as instruções para leitor de tela.

**O que vira `onMove(intent)`:**

| Gesto                                           | `MoveIntent`                                                                           |
| ----------------------------------------------- | -------------------------------------------------------------------------------------- |
| Soltar em outra coluna (sobre card ou no vazio) | `from`, `to`; `index` = onde caiu; `sequence` = a coluna de destino na ordem que ficou |
| Soltar na mesma coluna (qualquer ordem)         | `from === to`, `index` e `sequence` (só se a posição mudou)                            |
| Soltar com a ordem em _Prioridade_              | o mesmo, e antes `onOrderChange('manual')`: a prioridade desfaria o soltar (14/10)     |
| Soltar fora das colunas, Esc                    | nada                                                                                   |
| _Mover para…_ do menu ⋯ do card                 | `from` = etapa atual, `to` = a escolhida, `index` e `sequence` nulos (entra no fim)    |

**O quadro não abre diálogo nem decide regra.** Ele só emite o `MoveIntent`; a página passa ao `useBoardMove` (`start`), que recusa (toast), abre os diálogos ou manda ao servidor.

Acima das colunas:

- projeto encerrado: a faixa "Projeto encerrado: não recebe card novo; mover, comentar e anexar seguem." (P7);
- nenhum card: `WEmptyStateMolecule` "Nenhum card ainda" / "Escreva o título no topo do Backlog e tecle Enter." (F3 §03); com `filtered`, "Nenhum card com esses filtros" (F3 §05). As colunas continuam aparecendo.

## Props

| Prop                                                                | Tipo                                       | Padrão  | Obrigatória | Para quê                                               |
| ------------------------------------------------------------------- | ------------------------------------------ | ------- | ----------- | ------------------------------------------------------ |
| `stages`                                                            | `BoardStage[]`                             |         | sim         | Etapas ativas, na ordem de F2                          |
| `cards`                                                             | `BoardCard[]`                              |         | sim         | Os cards a mostrar (já filtrados)                      |
| `order`                                                             | `BoardOrder`                               |         | sim         | Ordem das colunas e o que o arraste vertical faz (P13) |
| `project`                                                           | `BoardProject`                             |         | sim         | `finished` mostra a faixa                              |
| `abilities`                                                         | `BoardAbilities`                           |         | sim         | Repassadas às colunas                                  |
| `options`                                                           | `Pick<BoardOptions, 'areas' \| 'members'>` |         | sim         | Menu ⋯ e _+_ dos cards                                 |
| `onMove`                                                            | `(intent: MoveIntent) => void`             |         | sim         | Arrastar e _Mover para…_                               |
| `filtered`                                                          | `boolean`                                  | `false` | não         | Troca a mensagem do vazio                              |
| `onCreate`, `creating`, `createError`, `onCreateSupport`            |                                            |         | não         | Repassados às colunas Backlog e Support                |
| `onChangePriority`, `onChangeArea`, `onToggleParticipant`, `onOpen` | os do `WTaskCardOrganism`                  |         | não         | Repassados aos cards                                   |
| `className`                                                         | `string`                                   |         | não         |                                                        |

## Exemplo

```tsx
const move = useBoardMove({ project, abilities, options, today, onCommit, onDenied: (reason) => toast.error(reason) });

<WKanbanBoardOrganism
    stages={stages}
    cards={visible}
    order={order}
    project={project}
    abilities={abilities}
    options={options}
    onMove={move.start}
    onCreate={createCard}
    onCreateSupport={openSupport}
    onChangePriority={changePriority}
/>;
{
    move.dialogs;
}
```

## Cuidados

- **Teclado.** O caminho por teclado para mover é o _Mover para…_ do menu ⋯ do card (mesmas regras do arrastar). O `KeyboardSensor` também funciona com o item da lista focado (Espaço, setas, Espaço), mas a primeira seta às vezes só "acha" a coluna atual e é preciso uma segunda.
- **Rolagem.** A região das colunas é `relative`: os textos `sr-only` (absolutos) das colunas ficam presos nela. Sem isso, a página inteira ganhava rolagem horizontal.
- A altura da região é a da janela menos o cabeçalho e os filtros (`100dvh - 14rem`, mínimo 24 rem); cada coluna rola sozinha.
- **Premissa de Projetos.** Nada aqui recusa movimento por falta de dev, horas, PR ou parecer; só a permissão (`abilities.move` liga o arrastar; sair ou voltar ao Backlog é o `classifyMove` da página).
- **Rolagem automática ao arrastar** (`autoScroll` do `DndContext`): pelo ponteiro, zona de 12 % da borda, 6 px a cada 10 ms (cerca de uma coluna por segundo). O padrão do dnd-kit (10 px a cada 5 ms, medido pelo retângulo do card) passava de todas as colunas num celular, porque o card arrastado tem quase a largura da tela. Durante o arraste o `snap-x snap-mandatory` do celular é desligado: com ele, cada passo da rolagem pulava uma coluna inteira.
- **Soltar na mesma coluna**: sobre o próprio card não muda nada; sobre outro card, o card vai para a posição daquele (`arrayMove` do dnd-kit, a mesma ordem que a lista mostrou durante o arraste); no vazio da coluna, para o fim. Em outra coluna, entra no lugar do card em que foi solto, ou no fim. Antes, largar no lugar jogava o card para o fim (o próprio card era lido como "vazio da coluna").
