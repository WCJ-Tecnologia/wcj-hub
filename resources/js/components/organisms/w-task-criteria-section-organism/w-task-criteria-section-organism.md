# WTaskCriteriaSectionOrganism

Organismo · `organisms/w-task-criteria-section-organism/w-task-criteria-section-organism.tsx` · story `Organisms/Task Criteria Section`

## O que faz

O conteúdo da seção _Critérios de aceite_ da ficha do card (F3 §02 "Critério de aceite", §03, §04; P17, P36, P37, T1, T9). A página o envolve no `WCollapsibleSectionMolecule`, com `criteriaSummary(criteria)` no cabeçalho recolhido.

- Lista `CA_1, CA_2…` (`ul`), com os subitens `CA_n.m` logo abaixo do pai, recuados com uma linha à esquerda (um nível só).
- Cada item: o número em Space Mono, a descrição (com as quebras de linha) e o resultado do QA em pílula (`WStatusPillAtom kind="criterion"`: A testar, Aprovado, Reprovado; P36). O resultado é só leitura aqui: quem marca é o QA (F4).
- Com `canEdit`, em cada item:
    - _Editar_: troca a descrição por um campo em linha, com _Salvar_ e _Cancelar_. Critério já testado mostra antes do _Salvar_ o aviso "Este critério volta para A testar" (`CRITERION_RETEST_TEXT`, `role="status"`, P37);
    - _Subitem_: só no critério de 1º nível; abre o campo no fim dos subitens dele (T9);
    - _Excluir_: só quando `criterionDeletable(criterion)` (o critério e cada subitem A testar, T1); testado, o botão não aparece (10.9). Confirma pelo `WConfirmDialogMolecule` destrutivo: "Excluir CA_3? O número não será reaproveitado." e, quando há subitens, "Os subitens saem junto.".
- Abaixo da lista, _Adicionar critério_ abre o campo do `CA_n` novo no fim.
- Vazio: "Nenhum critério." e, com `canEdit`, o link _Adicionar critério_ (F3 §03).
- No campo: Enter salva, Shift+Enter quebra a linha, Esc cancela. Descrição vazia: "Informe a descrição do critério.".

Um campo aberto por vez (editar, subitem ou critério novo): abrir outro fecha o anterior. Assim o `errors.description` do servidor tem um dono só.

## Props

| Prop       | Tipo                                                                    | Padrão  | Obrigatória | Para quê                                                                           |
| ---------- | ----------------------------------------------------------------------- | ------- | ----------- | ---------------------------------------------------------------------------------- |
| `criteria` | `AcceptanceCriterion[]`                                                 |         | sim         | Na ordem `CA_1, CA_2…`, subitens em `children` (o `number` vem pronto do servidor) |
| `canEdit`  | `boolean`                                                               |         | sim         | `abilities.update`: _Editar_, _Subitem_, _Excluir_ e _Adicionar critério_          |
| `busy`     | `boolean`                                                               | `false` | não         | Uma gravação da ficha rodando: desabilita Salvar, Adicionar e Excluir; `aria-busy` |
| `errors`   | `Record<string, string>`                                                |         | não         | Erro do servidor em `description`, mostrado no campo aberto                        |
| `onAdd`    | `(payload: { description: string; parent_id: number \| null }) => void` |         | sim         | Critério novo (`parent_id` null) ou subitem do pai (P17, T9)                       |
| `onUpdate` | `(id: number, description: string) => void`                             |         | sim         | Nova descrição (o servidor volta o resultado para A testar, P37)                   |
| `onDelete` | `(id: number) => void`                                                  |         | sim         | Depois da confirmação; os subitens saem junto no servidor                          |

`criteriaSummary(criteria)` devolve "1 aprovado · 2 reprovados · 4 a testar", contando os subitens; contagem zero sai da frase; sem critério, "Nenhum critério".

## Exemplo

```tsx
import {
    criteriaSummary,
    WTaskCriteriaSectionOrganism,
} from '@/components/organisms/w-task-criteria-section-organism/w-task-criteria-section-organism';

<WCollapsibleSectionMolecule title="Critérios de aceite" summary={criteriaSummary(task.criteria)}>
    <WTaskCriteriaSectionOrganism
        criteria={task.criteria}
        canEdit={task.abilities.update}
        busy={processing}
        errors={errors}
        onAdd={(payload) => post(`${task.url}/criterios`, payload)}
        onUpdate={(id, description) => patch(`${task.url}/criterios/${id}`, { description })}
        onDelete={(id) => destroy(`${task.url}/criterios/${id}`)}
    />
</WCollapsibleSectionMolecule>;
```

## Cuidados

- **Nada grava aqui.** O organismo devolve a intenção; a página manda ao servidor. Quem recusa excluir critério testado é o servidor (T1): o botão escondido é conveniência.
- **Quando o campo fecha.** Depois de _Salvar_ ou _Adicionar_, o campo fica aberto até a página trocar `criteria` com a gravação feita (a descrição nova no critério, ou um item a mais). Com erro, o texto fica e o erro aparece. O diálogo de excluir fecha quando o critério some de `criteria`. A troca é detectada durante a renderização (padrão "ajustar estado quando a prop muda"), sem `useEffect`.
- **Número.** Quem numera é o servidor; o organismo nunca calcula `CA_n` (excluir não renumera, T9).
- **Spinner.** Aparece só no botão que disparou a gravação em curso (`busy` e a última ação deste organismo).
