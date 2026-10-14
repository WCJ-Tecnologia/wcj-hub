# WTaskAttributesSectionOrganism

Organismo · `organisms/w-task-attributes-section-organism/w-task-attributes-section-organism.tsx` · story `Organisms/Task Attributes Section`

## O que faz

Tipo, área e prioridade da ficha do card (F3 §02, P18, P19) e, no card de suporte, o pedido do cliente (P52).

- **Com permissão** (`canEdit`): três `Select` em grade (`sm:grid-cols-3`).
    - **Tipo:** as opções de `types` (lista geral ativa). Se o tipo atual foi desativado e não está na lista, ele entra como opção com "(desativado)": some da escolha e continua no card que já o usa (G7).
    - **Área:** "Nenhuma" + as áreas do projeto. Projeto sem área: o campo diz "Nenhuma área no projeto" e fica desabilitado (F3 §02, P18).
    - **Prioridade:** Baixa, Normal, Alta, Urgente, cada uma com o `WPriorityMarkAtom variant="chip"`, como no menu do card.
- Cada troca chama `onChange` com **só o campo trocado**: `{ type_id }`, `{ area_id: number | null }` ou `{ priority }`. Escolher a mesma opção não chama.
- **Sem permissão:** os três valores em texto (`dl`).
- **Card de suporte** (`support` preenchido): bloco "Pedido do cliente" com Quem pediu, Por onde e Quando (`dateTimeBr`), sempre só leitura.

Só renderiza o conteúdo: a página envolve no `WCollapsibleSectionMolecule`, com `attributesSummary(type, area, priority)` no `summary`.

## Props

| Prop        | Tipo                                                                              | Padrão  | Obrigatória | Para quê                                                        |
| ----------- | --------------------------------------------------------------------------------- | ------- | ----------- | --------------------------------------------------------------- |
| `type`      | `TaskTypeRef`                                                                     |         | sim         | o tipo atual do card                                            |
| `area`      | `AreaRef \| null`                                                                 |         | sim         | a área atual, ou nenhuma                                        |
| `priority`  | `PrioritySlug`                                                                    |         | sim         | a prioridade atual (P19)                                        |
| `support`   | `SupportRequest \| null`                                                          |         | sim         | o pedido do cliente no card de suporte (P52); null nos demais   |
| `types`     | `TaskTypeRef[]`                                                                   |         | sim         | `options.types`: tipos ativos (P18)                             |
| `areas`     | `AreaRef[]`                                                                       |         | sim         | `options.areas`: áreas do projeto                               |
| `canEdit`   | `boolean`                                                                         |         | sim         | `abilities.update` (F3 §09)                                     |
| `busy`      | `boolean`                                                                         | `false` | não         | gravação rodando: os três campos travados, `aria-busy` no bloco |
| `errors`    | `Record<string, string>`                                                          |         | não         | erros do servidor em `type_id`, `area_id`, `priority`           |
| `onChange`  | `(changes: Pick<TaskPatchPayload, 'type_id' \| 'area_id' \| 'priority'>) => void` |         | sim         | a página manda no `PATCH` da ficha                              |
| `className` | `string`                                                                          |         | não         | classes extras na raiz                                          |

`attributesSummary(type, area, priority)`: "Melhoria · Portal do cidadão · Alta"; sem área, "Melhoria · Alta".

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Tipo, área e prioridade" summary={attributesSummary(task.type, task.area, task.priority)}>
    <WTaskAttributesSectionOrganism
        type={task.type}
        area={task.area}
        priority={task.priority}
        support={task.support}
        types={options.types}
        areas={options.areas}
        canEdit={task.abilities.update}
        busy={saving}
        errors={errors}
        onChange={(changes) => patch(changes)}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- **Premissa do kit:** trocar o tipo para Suporte na ficha exige os campos do pedido (quem pediu, por onde, quando; F3 §02), que este organismo não pede. O Select deixa trocar e o servidor devolve o erro em `type_id`, que aparece embaixo do campo. Quando o B3 decidir o diálogo do pedido, ele entra na página, não aqui.
- O pedido do cliente é só leitura aqui; contato anonimizado já vem do servidor como "removido a pedido do titular" (N12).
- Uma área que saiu da lista do projeto, mas continua no card, entra como opção até alguém trocar (mesma ideia do G7).
- Nada aqui decide regra: a lista de opções vem do servidor, e quem recusa é o servidor (inegociável 9).
