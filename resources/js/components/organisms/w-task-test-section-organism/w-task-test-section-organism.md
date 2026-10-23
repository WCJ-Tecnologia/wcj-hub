# WTaskTestSectionOrganism

Organismo · `organisms/w-task-test-section-organism/w-task-test-section-organism.tsx` · story `Organisms/Task Test Section`

## O que faz

O conteúdo da seção _Teste_ da ficha do card (F4 §01 a §04; P34 a P39; B4). A página o envolve no `WCollapsibleSectionMolecule`, com `testSummary(crossTested, criteria)` no cabeçalho recolhido ("Teste cruzado pendente · 4 de 7 aprovados · 1 reprovado").

Dois blocos, com o eyebrow em Space Mono:

- **Teste cruzado.** A situação ("Testado por Fulano em DD/MM/AAAA às HH:MM" ou "Ainda não marcado"). Com `canCrossTest` e sem marca, _Marquei: testado por mim_ (o dev da tarefa não vê o botão, P34). Com `canUndoCrossTest` e com marca, _Desfazer a marca_, que confirma "Desfazer a marca de teste cruzado de Fulano?" (F4 §04, §10.2).
- **Roteiro de QA.** A linha "Roteiro: 7 itens · 4 aprovados · 1 reprovado · 2 a testar" e um item por critério, na ordem `CA_1, CA_2…`, com os subitens logo abaixo do pai, recuados (cada um marcado à parte, F4 §10.4). Em cada item: o número em Space Mono, o texto (só leitura: editar é na seção _Critérios de aceite_), o resultado, a última marcação ("Fulano, DD/MM/AAAA às HH:MM" ou "—"), as evidências ("2 evidências", que abre a lista com os links e o _Excluir_) e _Anexar evidência_. Item reprovado fica em destaque (F4 §03).
    - Com `canRunQa`, o resultado é um grupo de três botões (_A testar_, _Aprovado_, _Reprovado_, `aria-pressed` no atual); sem, a pílula `WStatusPillAtom kind="criterion"`.
    - Vazio: "Esta tarefa não tem critérios de aceite." e, com `canEdit`, _Novo critério_ (`onNewCriterion`; a página abre a seção de critérios com o campo do critério novo).

Os dois blocos têm _Devolver para desenvolvimento_ quando `canReturn`: a página abre o diálogo de retrabalho do F3 com destino `To do` e a origem "Teste cruzado" (bloco do teste cruzado) ou "QA" (roteiro), P39.

## Props

| Prop               | Tipo                                                     | Padrão  | Obrigatória | Para quê                                                                                                 |
| ------------------ | -------------------------------------------------------- | ------- | ----------- | -------------------------------------------------------------------------------------------------------- |
| `criteria`         | `AcceptanceCriterion[]`                                  |         | sim         | Os itens do roteiro, com `result` e `marked`                                                             |
| `crossTested`      | `TaskDetail['cross_tested']`                             |         | sim         | Quem fez o teste cruzado e quando, ou null                                                               |
| `attachments`      | `TaskAttachment[]`                                       |         | sim         | Os anexos do card; as evidências são os de `criterion_id` igual ao do item                               |
| `canCrossTest`     | `boolean`                                                |         | sim         | `abilities.cross_test`: _Marquei: testado por mim_                                                       |
| `canUndoCrossTest` | `boolean`                                                |         | sim         | `abilities.undo_cross_test`: _Desfazer a marca_                                                          |
| `canRunQa`         | `boolean`                                                |         | sim         | `abilities.run_qa`: os botões de resultado                                                               |
| `canAttach`        | `boolean`                                                |         | sim         | `abilities.attach`: _Anexar evidência_ e _Excluir_ da evidência                                          |
| `canEdit`          | `boolean`                                                |         | sim         | `abilities.update`: o _Novo critério_ do roteiro vazio                                                   |
| `canReturn`        | `boolean`                                                |         | sim         | `abilities.move` e a etapa é Code-review, In test ou Done (`REWORK_FROM`): o atalho _Devolver_           |
| `busy`             | `boolean`                                                | `false` | não         | Uma gravação da ficha rodando: tudo desabilitado; spinner no botão que disparou; `aria-busy`             |
| `errors`           | `Record<string, string>`                                 |         | não         | `result`, `criterion_id`, `files`/`files.N`: aparecem no item que agiu (`role="alert"`)                  |
| `onMarkCrossTest`  | `() => void`                                             |         | sim         | `POST …/teste-cruzado`                                                                                   |
| `onUndoCrossTest`  | `() => void`                                             |         | sim         | Depois da confirmação: `POST …/teste-cruzado/desfazer`                                                   |
| `onSetResult`      | `(criterionId: number, result: CriterionResult) => void` |         | sim         | `POST …/criterios/{id}/resultado`; clicar no resultado atual não manda nada                              |
| `onUploadEvidence` | `(criterionId: number, files: File[]) => void`           |         | sim         | `POST …/anexos` com `files[]` e `criterion_id` (multipart)                                               |
| `onRemoveEvidence` | `(attachmentId: number) => void`                         |         | sim         | Depois da confirmação "Excluir o arquivo X? Não dá para desfazer.": `DELETE …/anexos/{id}`               |
| `onReturn`         | `(origin: 'cross_test' \| 'qa') => void`                 |         | sim         | Abre o diálogo de retrabalho com a origem escolhida (`useBoardMove().start(intent, { reworkOriginId })`) |
| `onNewCriterion`   | `() => void`                                             |         | sim         | O roteiro vazio: a criação de critério do F3                                                             |

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Teste" summary={testSummary(task.cross_tested, task.criteria)}>
    <WTaskTestSectionOrganism
        criteria={task.criteria}
        crossTested={task.cross_tested}
        attachments={task.attachments}
        canCrossTest={task.abilities.cross_test}
        canUndoCrossTest={task.abilities.undo_cross_test}
        canRunQa={task.abilities.run_qa}
        canAttach={task.abilities.attach}
        canEdit={task.abilities.update}
        canReturn={canReturn}
        busy={processing}
        errors={errors}
        onMarkCrossTest={() => post('/teste-cruzado')}
        onUndoCrossTest={() => post('/teste-cruzado/desfazer')}
        onSetResult={(id, result) => post(`/criterios/${id}/resultado`, { result })}
        onUploadEvidence={(id, files) => post('/anexos', { files, criterion_id: id }, { forceFormData: true })}
        onRemoveEvidence={(id) => destroy(`/anexos/${id}`)}
        onReturn={returnToDevelopment}
        onNewCriterion={openCriteria}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- **Nada grava aqui.** Quem recusa o dev da tarefa marcando, a marca repetida e o card arquivado é o servidor; o botão escondido é conveniência.
- **Reprovar não move o card** (premissa de Projetos): a devolução é sempre o atalho ou o arrastar, com o diálogo de retrabalho.
- **Evidência.** Um `<input type="file">` só, escondido; o item que pediu fica guardado até o arquivo ser escolhido. A lista de evidências do item abre sozinha depois do envio.
- **Datas.** `crossTested.at` e `marked.at` chegam em `AAAA-MM-DD HH:MM` no fuso de São Paulo e viram "DD/MM/AAAA às HH:MM" sem passar por `Date` (`dateAtTimeBr`).
