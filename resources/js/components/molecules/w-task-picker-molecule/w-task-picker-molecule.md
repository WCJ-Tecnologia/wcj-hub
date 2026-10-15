# WTaskPickerMolecule

Molécula · `molecules/w-task-picker-molecule/w-task-picker-molecule.tsx` · story `Molecules/Task Picker`

## O que faz

A busca de card da ficha (F3 §05) para o **card pai** (P57) e a **tarefa de origem** (P25): chave (`SGM-17`, `sgm-17`) ou trecho do título, sem diferença de acento nem maiúscula, sobre a lista `options.cards` que a página recebe (os cards do projeto, sem os arquivados e sem este card). Nada vai ao servidor para buscar.

- **Lista por teclado**: `role="combobox"` + `listbox`; setas, Enter escolhe, Esc limpa; mouse também.
- **Card pai**: os cards que a regra exclui aparecem **desabilitados, com o motivo** do F3 §04 ("SGM-10 já é pai de outros cards; um nível só." / "SGM-12 já é filho de SGM-10."). Assim a pessoa entende em vez de "não achar". A regra que vale é a do Service (R15).
- **Tarefa de origem**: qualquer card do projeto.
- **Com valor**: a chave com link (`WTaskKeyAtom`) e o título; _Trocar_ volta ao campo, _Limpar_ chama `onChange(null)`.
- Sem resultado: "Nenhum card com esse texto".

## Props

| Prop          | Tipo                              | Padrão            | Para quê                                   |
| ------------- | --------------------------------- | ----------------- | ------------------------------------------ |
| `cards`       | `TaskPickRef[]`                   |                   | `options.cards`                            |
| `mode`        | `'parent' \| 'origin'`            |                   | Que exclusões valem                        |
| `value`       | `TaskRef \| null`                 |                   | O card escolhido                           |
| `onChange`    | `(card: TaskRef \| null) => void` |                   | Escolher ou limpar; quem grava é a página  |
| `label`       | `string`                          |                   | `aria-label` do campo e da lista           |
| `id`          | `string`                          |                   | Id do campo                                |
| `placeholder` | `string`                          | `Chave ou título` |                                            |
| `disabled`    | `boolean`                         | `false`           | Só mostra (sem permissão ou gravando)      |
| `error`       | `string`                          |                   | Erro do servidor (`errors.parent_task_id`) |

A raiz leva `data-slot="w-task-picker-molecule"`.

## Exemplo

```tsx
<WTaskPickerMolecule
    cards={options.cards}
    mode="parent"
    value={detail.parent}
    label="Card pai"
    onChange={(card) => patch({ parent_task_id: card?.id ?? null })}
/>
```

## Cuidados

- A lista é a do servidor no carregamento da ficha: um card criado depois só aparece ao recarregar. Para a fatia 1 (projetos de dezenas de cards) basta; se crescer, o B3 troca por busca no servidor sem mudar a prop.
- As exclusões são a leitura do F3 na tela; o servidor recusa de novo e o erro chega em `error`.
