# WAllocatePeopleDialogOrganism

Organismo · `organisms/w-allocate-people-dialog-organism/w-allocate-people-dialog-organism.tsx` · story `Organisms/Alocar pessoas`

## O que faz

O diálogo "Alocar pessoas" da aba Pessoas (F2 §02, P5): um campo só, lista de caixas com busca por nome (sem acento e sem caixa), escolha de uma ou várias. O servidor já manda só quem pode entrar (ativo, com papel Desenvolvedor, Gestor de projetos ou Administrador, fora do projeto). Abrir de novo zera a escolha e a busca.

## Props

| Prop                   | Tipo                          | Para quê                                         |
| ---------------------- | ----------------------------- | ------------------------------------------------ |
| `open`, `onOpenChange` |                               | controle do modal (não fecha enquanto `loading`) |
| `people`               | `PersonOption[]`              | `options.people` da tela                         |
| `loading`              | `boolean`                     | trava os botões e mostra o spinner               |
| `error`                | `string`                      | o primeiro erro de `user_ids` do servidor        |
| `onConfirm`            | `(userIds: number[]) => void` | a tela faz o `POST /projetos/{id}/pessoas`       |

## Exemplo

Ver `organisms/w-project-members-tab-organism/`.

## Cuidados

Lista vazia mostra "Todo mundo que pode entrar já está no projeto." e o botão fica desabilitado; o botão só habilita com alguém escolhido.
