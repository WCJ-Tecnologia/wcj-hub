# WPeopleAvatarsMolecule

Molécula · `molecules/w-people-avatars-molecule/w-people-avatars-molecule.tsx` · story `Molecules/People Avatars`

## O que faz

As pessoas do card (F3 §03, rodapé; P20, P58):

- avatares em linha (`Avatar` do shadcn + `initials()`), na ordem dev, QA, participantes, sem repetir pessoa (a mesma pessoa pode ser dev e QA: "Bruno Lima · Dev e QA");
- dev e QA destacados com anel `ring-primary`; os avatares ficam lado a lado com um respiro pequeno (sobrepostos, as iniciais se escondiam atrás do anel do vizinho);
- o nome (e o papel no card) ao passar o mouse, pelo `Tooltip`, e no `aria-label` do avatar (`role="img"`), para leitor de tela;
- acima de `max` (padrão 4), um "+N" com os nomes que sobraram no tooltip;
- com `onToggleParticipant`, o botão _+_ (`aria-label="Adicionar ou remover participante"`) abre um `Popover` com as pessoas alocadas (`members`), uma caixa por pessoa. Dev e QA aparecem marcados e desabilitados, com a nota "Dev · já está no card": participante é quem acompanha sem ser dev nem QA (P58).

Sem ninguém e sem `onToggleParticipant`, não renderiza nada.

## Props

| Prop                  | Tipo                                            | Padrão      | Obrigatória | Para quê                                                  |
| --------------------- | ----------------------------------------------- | ----------- | ----------- | --------------------------------------------------------- |
| `developer`           | `PersonRef \| null`                             |             | sim         | Dev do card                                               |
| `qa`                  | `PersonRef \| null`                             |             | sim         | QA do card                                                |
| `participants`        | `PersonRef[]`                                   |             | sim         | Participantes (P58)                                       |
| `members`             | `PersonRef[]`                                   | `[]`        | não         | Opções do _+_: alocados e ativos (`BoardOptions.members`) |
| `onToggleParticipant` | `(person: PersonRef, checked: boolean) => void` |             | não         | Liga o _+_; `checked=true` põe, `false` tira              |
| `max`                 | `number`                                        | `4`         | não         | Avatares antes do "+N"                                    |
| `size`                | `'sm' \| 'default'`                             | `'default'` | não         | `sm` = 24 px (card do quadro), `default` = 32 px (ficha)  |
| `className`           | `string`                                        |             | não         |                                                           |

## Exemplo

```tsx
import { WPeopleAvatarsMolecule } from '@/components/molecules/w-people-avatars-molecule/w-people-avatars-molecule';

<WPeopleAvatarsMolecule
    developer={card.developer}
    qa={card.qa}
    participants={card.participants}
    members={options.members}
    onToggleParticipant={(person, checked) => toggleParticipant(card, person, checked)}
    size="sm"
/>;
```

## Cuidados

- A molécula só devolve a intenção: quem grava (e audita) é a página, pela rota do B3. A caixa marcada segue as props: muda quando o servidor devolve o card novo (componente controlado).
- Participante não recebe aviso nem entra na capacidade (F3 §11, item 4): nada aqui deve sugerir isso.
- Dev e QA se trocam na ficha, não aqui.
