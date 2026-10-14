# WTaskCardOrganism

Organismo · `organisms/w-task-card-organism/w-task-card-organism.tsx` · story `Organisms/Task Card`

## O que faz

O card do quadro kanban, nas zonas do F3 §03 (decisão de 13/10, D-GER-32), sobre o `Card` do shadcn, na largura da coluna:

| Zona      | O que mostra                                                                                                                 | Quando          | Peça                                              |
| --------- | ---------------------------------------------------------------------------------------------------------------------------- | --------------- | ------------------------------------------------- |
| Borda     | Faixa de prioridade, com "Prioridade: Alta" em `sr-only` (P19)                                                               | sempre          | `WPriorityMarkAtom`                               |
| Cabeçalho | Área como etiqueta (`Badge info`, P18); Tipo em Space Mono maiúsculo (P18); menu ⋯                                           | área: quando há | `Badge`, `DropdownMenu`                           |
| Cabeçalho | Chave (P16) e título em até 2 linhas; o título é o link para a ficha                                                         | sempre          | `WTaskKeyAtom`, `Link`                            |
| Cabeçalho | "↳ SGM-10": a chave do pai, com link e o título no tooltip (P57)                                                             | no card filho   | `WTaskKeyAtom`                                    |
| Marcas    | Atrasado, Em pausa (com motivo), voltas de retrabalho, Suporte (quem pediu), reestimativa proposta (P15, P27, P39, P52, P53) | quando há       | `WTaskMarksMolecule`                              |
| Corpo     | Capa (`<img alt="">`, 7 rem, `object-cover`, P59); horas previstas (P27)                                                     | quando há       |                                                   |
| Rodapé    | Pessoas, com o _+_ de participante (P20, P58); data prevista, anexos e comentários (P24, P28)                                | quando há       | `WPeopleAvatarsMolecule`, `WCardCountersMolecule` |
| Rodapé    | "2 · 1 · 3" e a barra dos filhos (P57)                                                                                       | no card pai     | `WChildProgressMolecule`                          |

**Profundidade.** O card tem sombra de repouso (`shadow-md`) e sobe 1 px com sombra maior no hover; a coluna é o "poço" (`bg-muted`), então o card branco se destaca dela. No escuro a sombra não aparece: a coluna fica mais escura que o card (`dark:bg-background/60` na coluna), e o relevo vem do contraste. Não há componente de sombra no shadcn; é sombra do Tailwind mais contraste de camadas.

**Onde ficam as marcas:** logo abaixo do título, antes da capa. Atraso e pausa são o que mais pede atenção, e ali ficam junto do nome do card, sem se perder entre os contadores do rodapé. A marca de atraso (rodapé no F3 §03) entra nesse grupo pelo mesmo motivo; no rodapé, a data prevista fica no vermelho de status quando o card está atrasado.

**Menu ⋯** (`aria-label="Ações de SGM-17"`):

- _Abrir ficha_: `onOpen(card)` ou, sem ele, link para `card.url`;
- _Copiar link_: a URL absoluta da ficha na área de transferência e o toast "Link copiado" (ou "Não foi possível copiar o link");
- _Mover para…_: as etapas de `stages` menos a atual; `onMove(card, etapa)`;
- _Prioridade_: as 4, Urgente no topo, a atual marcada; `onChangePriority(card, slug)`;
- _Área_: as `areas` e "Nenhuma", a atual marcada; `onChangeArea(card, área | null)`. Sem área no projeto: item desabilitado "Nenhuma área no projeto".

Cada item de edição só aparece com o callback dele (a página esconde o que a pessoa não pode; quem decide é o servidor). Escolher a opção que já está marcada não chama o callback.

Card só com título (o criado pelo rápido) fica baixo: cabeçalho com o tipo, chave e título. Sem pessoa nem contador, o rodapé não aparece, nem só para o _+_ de participante: o primeiro participante entra pela ficha, ou pelo _+_ assim que o card tiver dev, QA, data, anexo ou comentário.

## Props

| Prop                  | Tipo                                                  | Padrão  | Obrigatória | Para quê                                                                  |
| --------------------- | ----------------------------------------------------- | ------- | ----------- | ------------------------------------------------------------------------- |
| `card`                | `BoardCard`                                           |         | sim         | O card, como o `TaskPresenter` manda                                      |
| `stage`               | `BoardStage`                                          |         | sim         | Etapa atual: marca de pausa (`role === 'paused'`) e fora do _Mover para…_ |
| `stages`              | `BoardStage[]`                                        | `[]`    | não         | Etapas ativas do projeto                                                  |
| `areas`               | `AreaRef[]`                                           | `[]`    | não         | Áreas do projeto                                                          |
| `members`             | `PersonRef[]`                                         | `[]`    | não         | Opções do _+_ de participante                                             |
| `onMove`              | `(card, to: BoardStage) => void`                      |         | não         | _Mover para…_                                                             |
| `onChangePriority`    | `(card, priority: PrioritySlug) => void`              |         | não         | _Prioridade_                                                              |
| `onChangeArea`        | `(card, area: AreaRef \| null) => void`               |         | não         | _Área_                                                                    |
| `onToggleParticipant` | `(card, person: PersonRef, checked: boolean) => void` |         | não         | Liga o _+_ do rodapé                                                      |
| `onOpen`              | `(card) => void`                                      |         | não         | Abre a ficha sem navegar; sem ele, o título e _Abrir ficha_ navegam       |
| `dragging`            | `boolean`                                             | `false` | não         | Visual de "sendo arrastado": sombra maior, leve rotação, contorno azul    |
| `compact`             | `boolean`                                             | `false` | não         | Uma linha: chave, título e menu, sem capa, marcas nem rodapé              |
| `ref`                 | `Ref<HTMLDivElement>`                                 |         | não         | React 19: prop comum. O quadro liga o `setNodeRef` do dnd-kit             |
| `...rest`             | `HTMLAttributes<HTMLDivElement>`                      |         | não         | `attributes`, `listeners` e `style` (transform) do dnd-kit, `className`   |

A raiz leva `data-slot="w-task-card-organism"`, `data-key="SGM-17"` e, arrastando, `data-dragging`.

## Exemplo

```tsx
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { WTaskCardOrganism } from '@/components/organisms/w-task-card-organism/w-task-card-organism';

const { setNodeRef, attributes, listeners, transform, transition, isDragging } = useSortable({ id: card.id });

<WTaskCardOrganism
    ref={setNodeRef}
    {...attributes}
    {...listeners}
    style={{ transform: CSS.Transform.toString(transform), transition }}
    dragging={isDragging}
    card={card}
    stage={stage}
    stages={stages}
    areas={options.areas}
    members={options.members}
    onMove={(card, to) => requestMove({ card, from: stage, to, index: null })}
    onChangePriority={changePriority}
    onChangeArea={changeArea}
    onToggleParticipant={toggleParticipant}
/>;
```

## Cuidados

- **Nada grava aqui.** O card devolve a intenção; a página manda ao servidor. _Mover para…_ passa pelo mesmo `classifyMove` do arrastar (diálogos de pausa, retrabalho, sprint e a recusa "Só o gestor do projeto e o Administrador põem card na sprint"): o menu não decide nada.
- **Teclado e leitor de tela no arrastar.** O card tem links e botões dentro (título, pai, menu, _+_). Os `attributes` do `useSortable` põem `role="button"` na raiz, e um botão com controles dentro é a violação `nested-interactive` do axe. Ao ligar o dnd-kit, tire o `role` (ou use uma alça com `setActivatorNodeRef`) e conte com o _Mover para…_ do menu como o caminho por teclado.
- **Clique depois de soltar.** O título é link: se o arrastar começar nele, o quadro deve impedir o clique ao soltar (ex.: `activationConstraint: { distance: 5 }` no `PointerSensor`). Link e capa levam `draggable={false}` para o arrastar nativo do navegador não competir com o do dnd-kit.
- **Toast.** _Copiar link_ usa o `toast` do `sonner`; o `Toaster` já está no `WAppLayoutTemplate`.
- **Premissa de Projetos.** "Atrasado", data prevista e horas são referência; nenhuma marca sugere trava.
