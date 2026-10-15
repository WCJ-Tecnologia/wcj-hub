# WTaskOriginSectionOrganism

Organismo · `organisms/w-task-origin-section-organism/w-task-origin-section-organism.tsx` · story `Organisms/Task Origin Section`

## O que faz

Tarefa de origem da ficha (F3 §02, P25) e, no card original, os bugs de produção ligados (F3 §03).

- **Tarefa de origem:** a dica "Preenchida, este card é um bug de produção da tarefa indicada (P25)." e a busca `WTaskPickerMolecule mode="origin"` (chave ou título; qualquer card do projeto, menos o próprio, que `options.cards` já não traz). Com valor, a chave e o título com link, e _Trocar_ / _Limpar_ com permissão.
- **Bugs de produção ligados** (só quando há): lista com chave e título, os dois com link para a ficha do bug.

Escolher ou limpar chama `onChangeOrigin(card | null)`; a página manda `{ origin_task_id: card?.id ?? null }` no `PATCH`.

Só renderiza o conteúdo: a página envolve no `WCollapsibleSectionMolecule`, com `originSummary(originTask, productionBugs)` no `summary`.

## Props

| Prop             | Tipo                              | Padrão  | Obrigatória | Para quê                                                        |
| ---------------- | --------------------------------- | ------- | ----------- | --------------------------------------------------------------- |
| `originTask`     | `TaskRef \| null`                 |         | sim         | a tarefa original, quando este card é bug de produção           |
| `productionBugs` | `TaskRef[]`                       |         | sim         | no card original, os bugs que apontam para ele                  |
| `cards`          | `TaskPickRef[]`                   |         | sim         | `options.cards`: os cards do projeto, sem arquivados e sem este |
| `canEdit`        | `boolean`                         |         | sim         | `abilities.update` (F3 §09)                                     |
| `busy`           | `boolean`                         | `false` | não         | gravação rodando: busca travada, `aria-busy` no bloco           |
| `errors`         | `Record<string, string>`          |         | não         | erro do servidor em `origin_task_id`                            |
| `onChangeOrigin` | `(card: TaskRef \| null) => void` |         | sim         | a tarefa escolhida, ou null ao limpar                           |
| `className`      | `string`                          |         | não         | classes extras na raiz                                          |

`originSummary(originTask, productionBugs)`: "Bug de produção de SGM-17" / "2 bugs de produção ligados" / "Sem tarefa de origem". Com as duas coisas (raro), junta com " · ".

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Tarefa de origem" summary={originSummary(task.origin_task, task.production_bugs)}>
    <WTaskOriginSectionOrganism
        originTask={task.origin_task}
        productionBugs={task.production_bugs}
        cards={options.cards}
        canEdit={task.abilities.update}
        busy={saving}
        errors={errors}
        onChangeOrigin={(card) => patch({ origin_task_id: card?.id ?? null })}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- O bug de produção é outro card: não volta a original e conta como qualidade do projeto, não como retrabalho do dev (P25). Nada disso é decidido aqui.
- "Mesmo projeto" (R15) e "não aceita o próprio card" são do servidor; a busca só oferece `options.cards`, e o erro volta em `errors.origin_task_id`.
- Premissa do kit: sem permissão e sem tarefa de origem, o bloco mostra "—" em vez de uma busca desabilitada vazia.
