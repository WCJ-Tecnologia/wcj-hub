# WTaskParentSectionOrganism

Organismo · `organisms/w-task-parent-section-organism/w-task-parent-section-organism.tsx` · story `Organisms/Task Parent Section`

## O que faz

Card pai e cards filhos da ficha (F3 §02 "Card pai" e "Cards filhos", §04, §05; P57). Um nível só.

- **Card pai:** a busca `WTaskPickerMolecule mode="parent"` (chave ou título). Com pai, mostra a chave e o título do pai com link, e _Trocar_ / _Limpar_ com permissão. Na busca, card que já tem pai ou que tem filhos aparece desabilitado com o motivo (F3 §04), pelo `pickExclusionText` de `lib/ficha.ts`.
- **Card com filhos:** a busca do pai fica desabilitada, com a nota "Card com filhos não pode ter pai: um nível só (P57)."
- **Cards filhos** (só quando há): o resumo "2 a fazer · 2 em andamento · 3 concluídos" (`childrenSummaryText`), a barra (`WChildProgressMolecule`) e a lista com chave, título (os dois com link para a ficha do filho), coluna e dev ("sem dev" quando vazio). Filho arquivado não vem do servidor (P57).

Escolher ou limpar o pai chama `onChangeParent(card | null)`; a página manda `{ parent_task_id: card?.id ?? null }` no `PATCH`.

Só renderiza o conteúdo: a página envolve no `WCollapsibleSectionMolecule`, com `parentSummary(parent, children)` no `summary`.

## Props

| Prop             | Tipo                              | Padrão  | Obrigatória | Para quê                                                        |
| ---------------- | --------------------------------- | ------- | ----------- | --------------------------------------------------------------- |
| `taskKey`        | `string`                          |         | sim         | a chave deste card, no nome acessível da lista de filhos        |
| `parent`         | `ParentRef \| null`               |         | sim         | o pai atual, ou nenhum                                          |
| `children`       | `ChildCard[]`                     |         | sim         | só no card pai; `[]` no resto                                   |
| `cards`          | `TaskPickRef[]`                   |         | sim         | `options.cards`: os cards do projeto, sem arquivados e sem este |
| `canEdit`        | `boolean`                         |         | sim         | `abilities.update` (F3 §09): definir e limpar o pai             |
| `busy`           | `boolean`                         | `false` | não         | gravação rodando: busca travada, `aria-busy` no bloco           |
| `errors`         | `Record<string, string>`          |         | não         | erro do servidor em `parent_task_id`                            |
| `onChangeParent` | `(card: TaskRef \| null) => void` |         | sim         | o pai escolhido, ou null ao limpar                              |
| `className`      | `string`                          |         | não         | classes extras na raiz                                          |

`parentSummary(parent, children)`: "Filho de SGM-10" / "Pai de 7 cards · 2 a fazer · 2 em andamento · 3 concluídos" / "Sem card pai nem filhos".

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Card pai e filhos" summary={parentSummary(task.parent, task.children)}>
    <WTaskParentSectionOrganism
        taskKey={task.key}
        parent={task.parent}
        children={task.children}
        cards={options.cards}
        canEdit={task.abilities.update}
        busy={saving}
        errors={errors}
        onChangeParent={(card) => patch({ parent_task_id: card?.id ?? null })}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- A busca só orienta: card que já é pai ou já é filho fica marcado na lista, mas quem recusa é o servidor (inegociável 9), e o erro volta em `errors.parent_task_id` com a frase do F3 §04.
- O progresso dos filhos é só referência (P57): nada trava o pai pelos filhos nem os filhos pelo pai.
- Premissa do kit: sem permissão e sem pai, o bloco mostra "—" em vez de uma busca desabilitada vazia. O F3 não dá texto de vazio para os filhos: sem filhos, o bloco "Cards filhos" não aparece.
