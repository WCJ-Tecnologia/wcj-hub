# WTaskRequisitionSectionOrganism

Organismo · `organisms/w-task-requisition-section-organism/w-task-requisition-section-organism.tsx` · story `Organisms/Task Requisition Section`

## O que faz

O documento de requisição da ficha do card (F3 §02, P17): os quatro blocos **Sendo**, **Quero**, **Para** e **Informações adicionais**, as colunas `story_as`, `story_want`, `story_so_that` e `description` de `tasks`.

- **Leitura:** `dl` com os quatro blocos, rótulo em Space Mono maiúsculo e texto com as quebras de linha preservadas; bloco vazio mostra "—".
- **Vazio** nos quatro (`requisitionIsEmpty` de `lib/ficha.ts`): "Nenhuma requisição." e, com permissão, o link _Escrever_.
- **Edição** (só com `canEdit`): _Editar_ troca para o formulário (Sendo em `Input` até 200; os outros três em `Textarea`). _Salvar_ devolve os quatro campos por `onSave`, sem espaços nas pontas e com texto vazio como `null`. _Cancelar_ volta ao que veio.

Só renderiza o conteúdo: a página envolve no `WCollapsibleSectionMolecule`, com `requisitionSummary(requisition)` no `summary`.

## Props

| Prop             | Tipo                                                                                                        | Padrão  | Obrigatória | Para quê                                                                      |
| ---------------- | ----------------------------------------------------------------------------------------------------------- | ------- | ----------- | ----------------------------------------------------------------------------- |
| `requisition`    | `TaskDetail['requisition']`                                                                                 |         | sim         | os quatro campos como o servidor manda                                        |
| `canEdit`        | `boolean`                                                                                                   |         | sim         | `abilities.update` (F3 §09): sem ele, nada de _Editar_ nem _Escrever_         |
| `busy`           | `boolean`                                                                                                   | `false` | não         | gravação da ficha rodando: botões travados, spinner no _Salvar_               |
| `errors`         | `Record<string, string>`                                                                                    |         | não         | erros do servidor em `story_as`, `story_want`, `story_so_that`, `description` |
| `onSave`         | `(payload: Pick<TaskPatchPayload, 'story_as' \| 'story_want' \| 'story_so_that' \| 'description'>) => void` |         | sim         | a página manda no `PATCH` da ficha                                            |
| `defaultEditing` | `boolean`                                                                                                   | `false` | não         | começa no formulário (stories)                                                |
| `className`      | `string`                                                                                                    |         | não         | classes extras na raiz                                                        |

`requisitionSummary(requisition)`: a linha do cabeçalho recolhido, "Sendo contribuinte com IPTU em aberto, quero emitir a 2ª via…", em até 120 letras; só as informações adicionais quando as três frases estão vazias; "Sem requisição" sem nada.

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Requisição" summary={requisitionSummary(task.requisition)}>
    <WTaskRequisitionSectionOrganism
        requisition={task.requisition}
        canEdit={task.abilities.update}
        busy={saving}
        errors={errors}
        onSave={(payload) => patch(payload)}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- O formulário é montado só durante a edição: fechar (cancelar ou salvar) o desmonta, e o próximo _Editar_ começa do valor atual.
- O formulário fecha sozinho quando a gravação que ele disparou termina (`busy` vai de `true` a `false`) sem erro em nenhum dos quatro campos; com erro, fica aberto com o que foi digitado. Por isso a página **precisa** passar `busy` durante o `PATCH`.
- Validação na tela é só o `maxLength` de Sendo (200). O servidor decide (inegociável 9).
- Premissa do kit: o F3 não dá o texto do vazio da requisição; "Nenhuma requisição." + _Escrever_ é decisão do kit.
- Imagens não vão no texto: vão como anexo (dica do campo, F3 §02).
